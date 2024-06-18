<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\React;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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
                'data' => $request->user(),
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
        $data = [
            'description' => $request['description'] ?? null,
            'image_url' => $request['image_url'] ?? null,
            'latitude' => $request['latitude'] ?? null,
            'longitude' => $request['longitude'] ?? null,

        ];

        return User::where('id', $request->user()->id)->update($data);
    }

    public function listAvailable(Request $request)
    {
        $myUserReactedIds = React::where('sender_id', $request->user()->id)->pluck('receiver_id');
        $otherUserReactedMeIds = React::where('receiver_id', $request->user()->id)->pluck('sender_id');

        $users = User::whereNotIn('id', $myUserReactedIds)
            ->whereNotIn('id', $otherUserReactedMeIds)
            ->whereNot('id', $request->user()->id)
            ->get();

        return $users;
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
