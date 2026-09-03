<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TeacherController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // shifts রিলেশনসহ টিচার ফেচ করা
        $teachers = Teacher::with('shifts')->latest()->get()->map(function ($teacher) {
            if ($teacher->image) {
                $teacher->image = asset('storage/' . $teacher->image);
            }
            return $teacher;
        });

        return response()->json([
            'status' => true,
            'data'   => $teachers
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // ১. ভ্যালিডেশন
        $validated = $request->validate([
            'full_name'    => 'required|string|max:255',
            'designation'  => 'required|string|max:255',
            'department'   => 'required|string|max:255',
            'qualification'=> 'nullable|string|max:255',
            'phone'        => 'nullable|string|max:20',
            'email'        => 'required|email|unique:teachers,email',
            'joining_date' => 'required|date',
            'salary'       => 'nullable|numeric',
            'image'        => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'shift_ids'    => 'nullable|array', // মাল্টিপল শিফট আইডির অ্যারে
            'shift_ids.*'  => 'exists:shifts,id',
        ]);

        // ২. ইমেজ আপলোড
        $imagePath = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
            $imagePath = $file->storeAs('teachers', $filename, 'public');
        }

        // ৩. Teacher ID Generate
        $lastTeacher = Teacher::latest('id')->first();
        if ($lastTeacher && $lastTeacher->teacher_id) {
            $lastNumber = (int) str_replace('TCH-', '', $lastTeacher->teacher_id);
            $teacherId = 'TCH-' . ($lastNumber + 1);
        } else {
            $teacherId = 'TCH-1001';
        }

        // ৪. ডাটা সেভ
        $teacher = Teacher::create([
            'teacher_id'   => $teacherId,
            'full_name'    => $request->full_name,
            'designation'  => $request->designation,
            'department'   => $request->department,
            'qualification'=> $request->qualification,
            'phone'        => $request->phone,
            'email'        => $request->email,
            'join_date' => $request->joining_date ?? now()->toDateString(),
            'salary'       => $request->salary ?? 0,
            'image'        => $imagePath,
        ]);

        // ৫. শিফটগুলো সিঙ্ক করা (Pivot Table এ সেভ হবে)
        $teacher->shifts()->sync($request->shift_ids ?? []);

        // শিফট রিলেশনসহ ডাটা লোড করা
        $teacher->load('shifts');

        if ($teacher->image) {
            $teacher->image = asset('storage/' . $teacher->image);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Teacher added successfully!',
            'data'    => $teacher
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Teacher $teacher)
    {
        $teacher->load('shifts');

        if ($teacher->image) {
            $teacher->image = asset('storage/' . $teacher->image);
        }

        return response()->json([
            'status' => true,
            'data'   => $teacher
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Teacher $teacher)
    {
        $validated = $request->validate([
            'full_name'    => 'required|string|max:255',
            'designation'  => 'required|string|max:255',
            'department'   => 'required|string|max:255',
            'qualification'=> 'required|string|max:255',
            'phone'        => 'required|string|max:20',
            'email'        => 'required|email|unique:teachers,email,' . $teacher->id,
            'joining_date' => 'nullable|date',
            'join_date'    => 'nullable|date',
            'salary'       => 'nullable|numeric',
            'image'        => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'shift_ids'    => 'nullable|array', // মাল্টিপল শিফট আইডির অ্যারে
            'shift_ids.*'  => 'exists:shifts,id',
        ]);

        $imagePath = $teacher->image; // Keep existing image path by default

        // Custom Image Update Logic
        if ($request->hasFile('image')) {
            if ($teacher->image && Storage::disk('public')->exists($teacher->image)) {
                Storage::disk('public')->delete($teacher->image);
            }

            $file = $request->file('image');
            $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
            $imagePath = $file->storeAs('teachers', $filename, 'public');
        }

        // ডাটা আপডেট
        $teacher->update([
            'full_name'    => $request->full_name,
            'designation'  => $request->designation,
            'department'   => $request->department,
            'qualification'=> $request->qualification,
            'phone'        => $request->phone,
            'email'        => $request->email,
            'join_date' => $request->joining_date ?? now()->toDateString(),
            'salary'       => $request->salary ?? $teacher->salary,
            'image'        => $imagePath,
        ]);

        // শিফটগুলো সিঙ্ক করা
        $teacher->shifts()->sync($request->shift_ids ?? []);

        // শিফট রিলেশনসহ ডাটা রিলোড করা
        $teacher->load('shifts');

        if ($teacher->image) {
            $teacher->image = asset('storage/' . $teacher->image);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Teacher updated successfully!',
            'data'    => $teacher
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Teacher $teacher)
    {
        // Delete image file from storage if exists
        if ($teacher->image && Storage::disk('public')->exists($teacher->image)) {
            Storage::disk('public')->delete($teacher->image);
        }

        // পিভট টেবিল থেকে রিলেশন ডিলিট করা
        $teacher->shifts()->detach();

        $teacher->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Teacher deleted successfully!'
        ], 200);
    }
}
