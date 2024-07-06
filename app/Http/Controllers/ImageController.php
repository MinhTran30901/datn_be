<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Image;
use App\Http\Requests\UploadImageRequest;
use App\Http\Requests\UploadSubImageRequest;

class ImageController extends Controller
{
    public function uploadProfileImage(UploadSubImageRequest $request) {
        try {
            // Upload ảnh và lấy URL
            $imageUrl = $this->upload($request, 'profile');
    
            // Tìm ảnh với vị trí đã tồn tại
            $existingImage = Image::where('user_id', $request->user()->id)
                                  ->where('position', $request->position)
                                  ->first();
    
            if ($existingImage) {
                // Nếu vị trí đã tồn tại, cập nhật URL ảnh
                $existingImage->image_url = $imageUrl;
                $existingImage->save();
            } else {
                // Nếu vị trí chưa tồn tại, tạo mới bản ghi ảnh
                $image = new Image();
                $image->user_id = $request->user()->id;
                $image->image_url = $imageUrl;
                $image->position = $request->position;
                $image->save();
            }
    
            return response()->json(['message' => 'Photo uploaded successfully', 'path' => $imageUrl], 200);
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
