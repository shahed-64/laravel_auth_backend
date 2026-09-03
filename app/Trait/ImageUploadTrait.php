<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait ImageUploadTrait
{
    /**
     * Handle single image upload safely.
     */
    public function uploadImage($file, $folder = 'images', $oldImage = null, $disk = 'public')
    {
        if (!$file) {
            return null;
        }

        // আগের ছবি থাকলে মুছে ফেলা
        if ($oldImage && Storage::disk($disk)->exists($oldImage)) {
            Storage::disk($disk)->delete($oldImage);
        }

        // ইউনিক নাম তৈরি
        $extension = $file->getClientOriginalExtension();
        $filename = time() . '_' . Str::random(10) . '.' . $extension;

        // সেভ করে প্যাথ রিটার্ন করা
        return $file->storeAs($folder, $filename, $disk);
    }

    /**
     * Delete an image safely.
     */
    public function deleteImage($path, $disk = 'public')
    {
        if ($path && Storage::disk($disk)->exists($path)) {
            return Storage::disk($disk)->delete($path);
        }

        return false;
    }
}
