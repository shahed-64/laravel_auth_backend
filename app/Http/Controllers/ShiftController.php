<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    /**
     * সব শিফটের তালিকা (টিচারসহ) দেখার জন্য
     */
    public function index()
    {
        try {
            $shifts = Shift::with('teachers')->latest()->get();

            return response()->json([
                'status'  => true,
                'message' => 'Shifts fetched successfully.',
                'data'    => $shifts
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to fetch shifts.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * নতুন শিফট তৈরি করার জন্য
     */
public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:shifts,name',
            'start_time' => 'required', // start_time ভ্যালিডেশন যোগ করা হলো
        ]);

        try {
            $shift = Shift::create([
                'name' => $request->name,
                'start_time' => $request->start_time // start_time সেভ করা হচ্ছে
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'Shift created successfully.',
                'data'    => $shift
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to create shift.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * নির্দিষ্ট শিফট দেখার জন্য
     */
    public function show(Shift $shift)
    {
        try {
            $shift->load('teachers');

            return response()->json([
                'status' => true,
                'data'   => $shift
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to fetch shift.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * শিফট আপডেট করার জন্য
     */
/**
     * শিফট আপডেট করার জন্য
     */
    public function update(Request $request, Shift $shift)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:shifts,name,' . $shift->id,
            'start_time' => 'required', // start_time ভ্যালিডেশন যোগ করা হলো
        ]);

        try {
            $shift->update([
                'name' => $request->name,
                'start_time' => $request->start_time // start_time আপডেট করা হচ্ছে
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'Shift updated successfully.',
                'data'    => $shift
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to update shift.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
    /**
     * শিফট ডিলিট করার জন্য
     */
    public function destroy(Shift $shift)
    {
        try {
            $shift->delete();

            return response()->json([
                'status'  => true,
                'message' => 'Shift deleted successfully.'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to delete shift.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
