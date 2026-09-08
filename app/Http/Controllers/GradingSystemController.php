<?php

namespace App\Http\Controllers;

use App\Models\GradingSystem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class GradingSystemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $gradingSystems = GradingSystem::orderByDesc('min_percentage')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $gradingSystems,
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
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'grade' => [
                'required',
                'string',
                'max:10',
            ],

            'grade_point' => [
                'required',
                'numeric',
                'min:0',
                'max:5',
            ],

            'min_percentage' => [
                'required',
                'numeric',
                'min:0',
                'max:100',
            ],
        ]);

        $gradingSystem = GradingSystem::create([
            'grade' => strtoupper($validated['grade']),
            'grade_point' => $validated['grade_point'],
            'min_percentage' => $validated['min_percentage'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Grading system created successfully.',
            'data' => $gradingSystem,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(GradingSystem $gradingSystem): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $gradingSystem,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(GradingSystem $gradingSystem)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        Request $request,
        GradingSystem $gradingSystem
    ): JsonResponse {
        $validated = $request->validate([
            'grade' => [
                'required',
                'string',
                'max:10',
            ],

            'grade_point' => [
                'required',
                'numeric',
                'min:0',
                'max:5',
            ],

            'min_percentage' => [
                'required',
                'numeric',
                'min:0',
                'max:100',
            ],
        ]);

        $gradingSystem->update([
            'grade' => strtoupper($validated['grade']),
            'grade_point' => $validated['grade_point'],
            'min_percentage' => $validated['min_percentage'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Grading system updated successfully.',
            'data' => $gradingSystem->fresh(),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(
        GradingSystem $gradingSystem
    ): JsonResponse {
        $gradingSystem->delete();

        return response()->json([
            'success' => true,
            'message' => 'Grading system deleted successfully.',
        ]);
    }
}
