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
        // $userIds = React::where('receiver_id', $request->user()->id)->;
        $otherUserReactedMeIds = React::where('receiver_id', $request->user()->id)
            ->where('status', 1)->pluck('sender_id');

        $myReactedUserIds = React::where('sender_id', $request->user()->id)->pluck('receiver_id');

        $users = User::whereIn('id', $otherUserReactedMeIds)
            ->whereNotIn('id', $myReactedUserIds)
            ->whereNot('id', $request->user()->id)
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
}
