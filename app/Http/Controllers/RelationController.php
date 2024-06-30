<?php

namespace App\Http\Controllers;

use App\Models\React;
use App\Models\User;
use Illuminate\Http\Request;

class RelationController extends Controller
{
    public function sendRequest(Request $request)
    {
        return React::create($request->all());
    }

    public function listReceivedRequest(Request $request)
    {   
        $user = $request->user();
        $latitude = $user->latitude;
        $longitude = $user->longitude;

        
        $otherUserReactedMeIds = React::where('receiver_id', $request->user()->id)
            ->where('status', 1)->pluck('sender_id');

        $myReactedUserIds = React::where('sender_id', $request->user()->id)->pluck('receiver_id');

        $users = User::whereIn('id', $otherUserReactedMeIds)
            ->whereNotIn('id', $myReactedUserIds)
            ->where('id', '!=', $request->user()->id)
            ->select('id', 'username', 'image_url', 'description', 'age', 'height', 'smoking', 'alcohol', \DB::raw("
                (6371 * acos(
                    cos(radians($latitude)) * cos(radians(latitude)) *
                    cos(radians(longitude) - radians($longitude)) +
                    sin(radians($latitude)) * sin(radians(latitude))
                )) AS distance
            "))
            ->get();

        return $users;
    }

    public function listConnected(Request $request)
    {
        $otherUserLikedMeIds = React::where('receiver_id', $request->user()->id)
            ->where('status', 1)->pluck('sender_id');

        $myLikedUserIds = React::where('sender_id', $request->user()->id)
            ->where('status', 1)->pluck('receiver_id');

        $users = User::whereIn('id', $otherUserLikedMeIds)
            ->whereIn('id', $myLikedUserIds)
            ->whereNot('id', $request->user()->id)
            ->get();

        return $users;
    }

    public function relationDelete(Request $request, $friendId)
    {
        $currentUser = $request->user();
        $myLikedUserIds = React::where('sender_id', $request->user()->id)
            ->where('status', 1)->pluck('receiver_id');
        if($friendId && !$myLikedUserIds->contains($friendId))
        {
            return response()->json(['error' => 'Mối quan hệ không tồn tại.'], 400);
        }
        
        React::where(function ($query) use ($currentUser, $friendId) 
        {
            $query->where('sender_id', $currentUser->id)
                  ->Where('receiver_id', $friendId);
        })->orWhere(function ($query) use ($currentUser, $friendId) 
        {
            $query->where('receiver_id', $currentUser->id)
                  ->Where('sender_id', $friendId);
        })
            ->where('status', 1)->delete();

        return response()->json(
            $message = 'oke'
        );
    }
}
