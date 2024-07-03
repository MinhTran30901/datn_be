<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Requests\SearchUsersRequest;
use Illuminate\Support\Facades\Cache;
use App\Models\React;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function signUp(StoreUserRequest $request)
    {
        $birthday = $request->birthday;
        $year_str = substr($birthday, -4);
        $year = (int)$year_str;
        
        $current_year = date('Y');
        $age = $current_year - $year;

        $user = User::create([
            'username' => $request->username,
            'email' => $request->email,
            'age' => $age,
            'birthday' => $request->birthday,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('authToken')->plainTextToken;

        return response()->json(
            [
                'access_token' => $token,
                'type_token' => 'Bearer',
            ],
            200
        );
    }

    public function login(Request $request)
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password, [])) {
            return response()->json(
                [
                    'message' => 'User not exist!',
                ],
                404
            );
        }

        $token = $user->createToken('authToken')->plainTextToken;

        return response()->json(
            [
                'access_token' => $token,
                'type_token' => 'Bearer',
            ],
            200
        );
    }

    public function index(Request $request)
    {
        return response()->json(
            [
                'data' => $request->user()->load(['images', 'interests']),
            ],
            200
        );
    }

    public function logout()
    {
        auth()->user()->tokens()->delete();

        return response()->json(
            $data = 'ok',
            $status = 200,
        );
    }

    public function changeInfo(UpdateUserRequest $request)
    {
        if ($request->file('image')) {
            $request['image_url'] = $this->upload($request);
        }
        unset($request['image']);
        if($request['image_url']){
            $data = [
                'description' => $request['description'] ?? null,
                'image_url' => $request['image_url'] ?? null,
                'latitude' => $request['latitude'] ?? null,
                'longitude' => $request['longitude'] ?? null,
                'height' => $request['height'] ?? null,
                'smoking' => $request['smoking'] ?? null, 
                'alcohol' => $request['alcohol'] ?? null,
            ];
        }else{
            $data = [
                'description' => $request['description'] ?? null,
                'latitude' => $request['latitude'] ?? null,
                'longitude' => $request['longitude'] ?? null,
                'height' => $request['height'] ?? null,
                'smoking' => $request['smoking'] ?? null, 
                'alcohol' => $request['alcohol'] ?? null,
            ];
        }
        

        return User::where('id', $request->user()->id)->update($data);
    }



    public function listAvailable(Request $request)
    {
        try {
            $perPage = 10; // Số lượng người dùng tối đa trả về
            $currentUser = $request->user();
            $latitude = $currentUser->latitude;
            $longitude = $currentUser->longitude;

            Log::info('User details', ['id' => $currentUser->id, 'latitude' => $latitude, 'longitude' => $longitude]);

            $cacheKey = 'suggest_users_' . $currentUser->id;

            // Check if the user has liked enough users
            $likedUsersIds = React::where('sender_id', $currentUser->id)
                ->where('status', 1)
                ->pluck('receiver_id');

            Log::info('Liked users found', ['likedUsersIds' => $likedUsersIds]);

            // Check if liked users count is less than 8, return random users
            if ($likedUsersIds->isEmpty() || $likedUsersIds->count() < 8) {
                $randomUsers = User::whereNotIn('id', $likedUsersIds->toArray())
                    ->where('id', '!=', $currentUser->id)
                    ->inRandomOrder()
                    ->take($perPage)
                    ->select('id', 'username', 'image_url', 'description', 'age', 'height', 'smoking', 'alcohol', 'latitude', 'longitude')
                    ->selectRaw("( 6371 * acos( cos( radians(?) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(?) ) + sin( radians(?) ) * sin( radians( latitude ) ) ) ) AS distance", [$latitude, $longitude, $latitude])
                    ->get();
            
                Log::info('Random users suggested', ['count' => $randomUsers->count()]);
                return response()->json($randomUsers);
            }
            

            // Calculate average criteria based on liked users
            $searchCriteria = [
                'min_age' => [],
                'max_age' => [],
                'min_height' => [],
                'max_height' => [],
                'smoking' => [],
                'alcohol' => [],
            ];

            $likedUsers = User::whereIn('id', $likedUsersIds)
                ->select('id', 'age', 'height', 'smoking', 'alcohol', 'latitude', 'longitude')
                ->get();

            foreach ($likedUsers as $user) {
                if ($user->age) {
                    $searchCriteria['min_age'][] = $user->age;
                    $searchCriteria['max_age'][] = $user->age;
                }
                if ($user->height) {
                    $searchCriteria['min_height'][] = $user->height;
                    $searchCriteria['max_height'][] = $user->height;
                }
                if ($user->smoking !== null) {
                    $searchCriteria['smoking'][] = $user->smoking;
                }
                if ($user->alcohol !== null) {
                    $searchCriteria['alcohol'][] = $user->alcohol;
                }
            }

            $avgMinAge = !empty($searchCriteria['min_age']) ? min($searchCriteria['min_age']) : null;
            $avgMaxAge = !empty($searchCriteria['max_age']) ? max($searchCriteria['max_age']) : null;
            $avgMinHeight = !empty($searchCriteria['min_height']) ? min($searchCriteria['min_height']) : null;
            $avgMaxHeight = !empty($searchCriteria['max_height']) ? max($searchCriteria['max_height']) : null;
            $smokingCriteria = array_count_values($searchCriteria['smoking']);
            $alcoholCriteria = array_count_values($searchCriteria['alcohol']);

            Log::info('Search criteria calculated', [
                'avgMinAge' => $avgMinAge,
                'avgMaxAge' => $avgMaxAge,
                'avgMinHeight' => $avgMinHeight,
                'avgMaxHeight' => $avgMaxHeight,
                'smokingCriteria' => $smokingCriteria,
                'alcoholCriteria' => $alcoholCriteria,
            ]);

            // Calculate max distance based on user's location
            $maxDistance = 50;
            if ($latitude && $longitude) {
                $maxDistanceUser = User::whereIn('id', $likedUsersIds)
                    ->selectRaw(
                        "( 6371 * acos( cos( radians(?) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(?) ) + sin( radians(?) ) * sin( radians( latitude ) ) ) ) AS distance",
                        [$latitude, $longitude, $latitude]
                    )
                    ->orderBy('distance', 'desc')
                    ->first();

                if ($maxDistanceUser) {
                    $maxDistance = $maxDistanceUser->distance;
                }
            }

            Log::info('Max distance calculated', ['maxDistance' => $maxDistance]);

            // Query suggested users based on calculated criteria and max distance
            $suggestedUsersQuery = User::query()
                ->select('id', 'username', 'image_url', 'description', 'age', 'height', 'smoking', 'alcohol', 'latitude', 'longitude');

            if ($avgMinAge !== null && $avgMaxAge !== null) {
                $suggestedUsersQuery->whereBetween('age', [$avgMinAge, $avgMaxAge]);
            }
            if ($avgMinHeight !== null && $avgMaxHeight !== null) {
                $suggestedUsersQuery->whereBetween('height', [$avgMinHeight, $avgMaxHeight]);
            }
            if (!empty($smokingCriteria)) {
                $mostCommonSmoking = array_search(max($smokingCriteria), $smokingCriteria);
                $suggestedUsersQuery->where('smoking', $mostCommonSmoking);
            }
            if (!empty($alcoholCriteria)) {
                $mostCommonAlcohol = array_search(max($alcoholCriteria), $alcoholCriteria);
                $suggestedUsersQuery->where('alcohol', $mostCommonAlcohol);
            }

            if ($latitude && $longitude) {
                $suggestedUsersQuery->selectRaw(
                    "( 6371 * acos( cos( radians(?) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(?) ) + sin( radians(?) ) * sin( radians( latitude ) ) ) ) AS distance",
                    [$latitude, $longitude, $latitude]
                )->having("distance", "<=", $maxDistance);
            }

            $suggestedUsers = $suggestedUsersQuery
                ->where('id', '!=', $currentUser->id)
                ->whereNotIn('id', $likedUsersIds)
                ->take($perPage)
                ->get();

            if ($suggestedUsers->isEmpty()) {
                // Trả về danh sách người dùng ngẫu nhiên nếu không có người dùng nào phù hợp
                $randomUsers = User::whereNotIn('id', $likedUsersIds->toArray())
                    ->where('id', '!=', $currentUser->id)
                    ->inRandomOrder()
                    ->take($perPage)
                    ->select('id', 'username', 'image_url', 'description', 'age', 'height', 'smoking', 'alcohol', 'latitude', 'longitude')
                    ->selectRaw("( 6371 * acos( cos( radians(?) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(?) ) + sin( radians(?) ) * sin( radians( latitude ) ) ) ) AS distance", [$latitude, $longitude, $latitude])
                    ->get();
            
                Log::info('Random users suggested', ['count' => $randomUsers->count()]);
                return response()->json($randomUsers);
            }

            Log::info('Suggested users returned', ['count' => $suggestedUsers->count()]);

            //Cache the suggested users
            Cache::remember($cacheKey, 600, function () use ($suggestedUsers) {
                return $suggestedUsers->toArray();
            });

            return response()->json($suggestedUsers);

        } catch (\Exception $e) {
            Log::error('Error in listAvailable', ['exception' => $e]);
            return response()->json(['error' => 'An error occurred'], 500);
        }
    }

    
    
        public function searchUsers(SearchUsersRequest $request)
    {
        $perPage = 10;
        $currentUser = $request->user();
        $userLatitude = $currentUser->latitude;
        $userLongitude = $currentUser->longitude;

        Log::info('SearchUsers request: ' . json_encode($request->all()));

        $likedUsersIds = React::where('sender_id', $currentUser->id)
            ->where('status', 1)
            ->pluck('receiver_id');
            
        $query = User::query();

        if ($request->filled('minAge') && $request->input('minAge') != 0 &&
            $request->filled('maxAge') && $request->input('maxAge') != 200) {
            $query->where('age', '>=', $request->input('minAge'))
                ->where('age', '<=', $request->input('maxAge'));

            Log::info('SearchUsers: Age condition applied');
        }

        if ($request->filled('minHeight') && $request->input('minHeight') != 0 &&
            $request->filled('maxHeight') && $request->input('maxHeight') != 500) {
            $query->where('height', '>=', $request->input('minHeight'))
                ->where('height', '<=', $request->input('maxHeight'));

            Log::info('SearchUsers: Height condition applied');
        }

        if ($request->filled('distance') && $request->input('distance') != 0) {
            $maxDistance = $request->input('distance');
            $query->selectRaw(
                "id, username, image_url, description, age, height, smoking, alcohol, latitude, longitude, 
                ( 6371 * acos( cos( radians(?) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(?) ) + sin( radians(?) ) * sin( radians( latitude ) ) ) ) AS distance",
                [$userLatitude, $userLongitude, $userLatitude]
            )->having("distance", "<=", $maxDistance);
        } else {
            $query->selectRaw(
                "id, username, image_url, description, age, height, smoking, alcohol, latitude, longitude, 
                ( 6371 * acos( cos( radians(?) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(?) ) + sin( radians(?) ) * sin( radians( latitude ) ) ) ) AS distance",
                [$userLatitude, $userLongitude, $userLatitude]
            );
        }
        
        $results = $query
        ->where('id', '!=', $currentUser->id)
        ->whereNotIn('id', $likedUsersIds)
        ->take($perPage)
        ->get();

        return response()->json($results);
    }







    public function upload($request)
    {
        $file = $request->file('image');

        try {
            return $this->uploadImage($file);
        } catch (Exception $e) {
            return false;
        }
    }

    public function uploadImage($file)
    {
        $fileName = $file->getClientOriginalName();
        $file->move(public_path('uploads'), $fileName);

        $imageUrl = url('uploads/'.$fileName);

        return $imageUrl;
    }

    public function prepairFolder()
    {
        $year = date('Y');
        $month = date('m');
        $storagePath = "$year/$month/";

        if (! file_exists($storagePath)) {
            mkdir($storagePath, 0755, true);
        }

        return $storagePath;
    }
}
