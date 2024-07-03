<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Image;
use App\Http\Requests\UploadImageRequest;

class ImageController extends Controller
{
    public function uploadProfileImage(UploadImageRequest $request){
        try {
            $imageUrl = $this->upload($request,'profile');
            $image = new Image();
            $image->user_id = $request->user()->id;
            $image->image_url = $imageUrl;
            $image->save();
            return response()->json(['message' => 'Photo uploaded successfully', 'path' => $image->image_url], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Photo upload failed'], 500);
        }

    }

    public function uploadAvatarImage(UploadImageRequest $request){
        try {
            $imageUrl = $this->upload($request);
            $user = $request->user();
            $user->image_url = $imageUrl;
            $user->save();
            return response()->json(['message' => 'Photo uploaded successfully', 'path' => $user->image_url], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Photo upload failed'], 500);
        }

    }

    public function upload($request, $path  = 'avatar' )
    {
        $file = $request->file('image');

        try {
            return $this->uploadImage($file, $path);
        } catch (Exception $e) {
            return false;
        }
    }

    public function uploadImage($file, $path)
    {
        $fileName = $file->getClientOriginalName();
        $fileName = $file->hashName();
        $file->move(public_path($path), $fileName);
            
        $imageUrl = url($path.'/'.$fileName);

        return $imageUrl;
    }
}
