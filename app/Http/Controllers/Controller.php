<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

abstract class Controller
{
    /**
     * কমন ইমেজ আপলোড মেথড (যেকোনো Controller থেকে ব্যবহার করা যাবে)
     */
    protected function uploadImage($file, $folder = 'images', $oldImage = null, $disk = 'public')
    {
        if (!$file) {
            return null;
        }

        // পুরনো ছবি থাকলে ডিলিট করবে
        if ($oldImage && Storage::disk($disk)->exists($oldImage)) {
            Storage::disk($disk)->delete($oldImage);
        }

        // ইউনিক নাম তৈরি করে সেভ করা
        $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();

        return $file->storeAs($folder, $filename, $disk);
    }

    /**
     * কমন ইমেজ ডিলিট মেথড
     */
    protected function deleteImage($path, $disk = 'public')
    {
        if ($path && Storage::disk($disk)->exists($path)) {
            return Storage::disk($disk)->delete($path);
        }

        return false;
    }
}
