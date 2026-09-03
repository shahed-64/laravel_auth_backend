<?php

namespace App\Http\Controllers;

use App\Models\Examination;
use Illuminate\Http\Request;

class ExaminationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $examination = Examination::orderBy('id', 'desc')->get();

        return response()->json([
            'status' => true,
           'data' => $examination
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'examination_type' => 'required|string|max:255',
            'examination_year' => 'required|string|max:255',
        ]);

        // একই বছরে একই পরীক্ষার নাম ইতিমধ্যে আছে কি না চেক করা
        $exists = Examination::where('examination_type', $request->examination_type)
            ->where('examination_year', $request->examination_year)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => "{$request->examination_year} সালের জন্য '{$request->examination_type}' পরীক্ষাটি ইতিমধ্যে এন্ট্রি করা আছে!"
            ], 422);
        }

        $exam = Examination::create([
            'examination_type' => $request->examination_type,
            'examination_year' => $request->examination_year
        ]);

        return response()->json([
            'status'    => true,
            'message'   => 'Examination Created Successfully!',
            'exam'      => $exam
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Examination $examination)
    {
        return response()->json([
            'status' => true,
            'data' => $examination
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Examination $examination)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Examination $examination)
    {
        $request->validate([
            'examination_type' => 'required|string|max:255',
            'examination_year' => 'required|string|max:255',
        ]);

        // আপডেট করার সময় নিজের আইডি বাদ দিয়ে অন্য কোনো রো-তে একই ডেটা আছে কি না চেক করা
        $exists = Examination::where('examination_type', $request->examination_type)
            ->where('examination_year', $request->examination_year)
            ->where('id', '!=', $examination->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => "{$request->examination_year} সালের জন্য '{$request->examination_type}' পরীক্ষাটি ইতিমধ্যে অন্য কোনো এন্ট্রিতে রয়েছে!"
            ], 422);
        }

        $examination->update([
            'examination_type' => $request->examination_type,
            'examination_year' => $request->examination_year
        ]);

        return response()->json([
            'status'    => true,
            'message'   => 'Examination Updated Successfully!',
            'exam'      => $examination
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Examination $examination)
    {
        $examination->delete();

        return response()->json([
            'status'    => true,
            'message'   => 'Examination Deleted Successfully!'
        ]);
    }
}
