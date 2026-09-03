<?php

namespace App\Http\Controllers;

use App\Models\ClssM;
use Illuminate\Http\Request;

class ClssMController extends Controller
{
    /**
     * Display a listing of the classes.
     */
    public function index()
    {
        $classes = ClssM::with('subjects')->latest()->get();

        return response()->json([
            'status' => true,
            'classes' => $classes
        ], 200);
    }

    /**
     * Show the form for creating a new class.
     */
    public function create()
    {
        // API-এর ক্ষেত্রে প্রয়োজন নেই
    }

    /**
     * Store a newly created class.
     */
    public function store(Request $request)
    {
        $request->validate([
            'class_name' => 'required|string|max:255',
            'subject_ids' => 'nullable|array',
            'subject_ids.*' => 'integer|exists:subjects,id',
        ]);

        // Class create
        $class = ClssM::create([
            'class_name' => $request->class_name,
        ]);

        // Selected subjects attach
        if ($request->has('subject_ids')) {
            $class->subjects()->sync($request->subject_ids);
        }

        // Subjects সহ fresh data
        $class->load('subjects');

        return response()->json([
            'status'  => true,
            'message' => 'Class Created Successfully',
            'class'   => $class
        ], 201);
    }

    /**
     * Display the specified class.
     */
    public function show(ClssM $clssM)
    {
        $clssM->load('subjects');

        return response()->json([
            'status' => true,
            'class'  => $clssM
        ], 200);
    }

    /**
     * Show the form for editing the specified class.
     */
    public function edit(ClssM $clssM)
    {
        // API-এর ক্ষেত্রে প্রয়োজন নেই
    }

    /**
     * Update the specified class.
     */
    public function update(Request $request, ClssM $clssM)
    {
        $request->validate([
            'class_name' => 'required|string|max:255',
            'subject_ids' => 'nullable|array',
            'subject_ids.*' => 'integer|exists:subjects,id',
        ]);

        // Class update
        $clssM->update([
            'class_name' => $request->class_name
        ]);

        // Subjects sync
        // Selected subjects থাকবে,
        // unselected subjects pivot table থেকে remove হবে।
        $clssM->subjects()->sync($request->subject_ids ?? []);

        // Subjects সহ fresh data
        $clssM->load('subjects');

        return response()->json([
            'status'  => true,
            'message' => 'Class Updated Successfully',
            'class'   => $clssM
        ], 200);
    }

    /**
     * Remove the specified class.
     */
    public function destroy(ClssM $clssM)
    {
        $clssM->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Class Deleted Successfully'
        ], 200);
    }
}
