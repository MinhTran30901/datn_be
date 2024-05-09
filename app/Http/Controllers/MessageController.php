<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Exception;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function historyMessage(Request $request)
    {
        $userId = $request['sender_id'];
        $receiverId = $request['receiver_id'];

        $messageSent = Message::where('sender_id', $userId)->where('receiver_id', $receiverId)->get()->toArray();
        $messageReceived = Message::where('sender_id', $receiverId)->where('receiver_id', $userId)->get()->toArray();

        return array_merge($messageSent, $messageReceived);
    }

    public function store(Request $request)
    {
        if ($request->file('image')) {
            $request['image_url'] = $this->upload($request);
        }
        unset($request['image']);

        $data = [
            'sender_id' => $request['sender_id'],
            'receiver_id' => $request['receiver_id'],
            'image_url' => $request['image_url'],
            'content' => $request['content'],
        ];

        return Message::create($data);
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
