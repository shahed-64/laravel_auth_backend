<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubjectController extends Controller
{
    /**
     * Display a listing of subjects.
     */
    public function index()
    {
        $subjects = Subject::latest()->get();

        return response()->json([
            'success' => true,
            'data' => $subjects,
        ]);
    }

    /**
     * Show the form for creating a new subject.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created subject.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],

            'code' => [
                'required',
                'string',
                'max:50',
                'unique:subjects,code',
            ],

            'full_mark' => [
                'required',
                'numeric',
                'min:1',
            ],
        ]);

        $subject = Subject::create([
            'name' => $validated['name'],
            'code' => strtoupper($validated['code']),
            'full_mark' => $validated['full_mark'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Subject created successfully.',
            'data' => $subject,
        ], 201);
    }

    /**
     * Display the specified subject.
     */
    public function show(Subject $subject)
    {
        return response()->json([
            'success' => true,
            'data' => $subject,
        ]);
    }

    /**
     * Show the form for editing the specified subject.
     */
    public function edit(Subject $subject)
    {
        //
    }

    /**
     * Update the specified subject.
     */
    public function update(Request $request, Subject $subject)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],

            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('subjects', 'code')
                    ->ignore($subject->id),
            ],

            'full_mark' => [
                'required',
                'numeric',
                'min:1',
            ],
        ]);

        $subject->update([
            'name' => $validated['name'],
            'code' => strtoupper($validated['code']),
            'full_mark' => $validated['full_mark'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Subject updated successfully.',
            'data' => $subject->fresh(),
        ]);
    }

    /**
     * Remove the specified subject.
     */
    public function destroy(Subject $subject)
    {
        $subject->delete();

        return response()->json([
            'success' => true,
            'message' => 'Subject deleted successfully.',
        ]);
    }
}
