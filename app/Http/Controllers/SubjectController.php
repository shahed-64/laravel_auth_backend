<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubjectController extends Controller
{
    public function index()
    {
        $subjects = Subject::latest()->get();

        return response()->json([
            'success' => true,
            'data' => $subjects,
        ]);
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:subjects,code'],
        ]);

        $subject = Subject::create([
            'name' => $validated['name'],
            'code' => strtoupper($validated['code']),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Subject created successfully.',
            'data' => $subject,
        ], 201);
    }

    public function show(Subject $subject)
    {
        return response()->json([
            'success' => true,
            'data' => $subject,
        ]);
    }

    public function edit(Subject $subject)
    {
        //
    }

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
        ]);

        $subject->update([
            'name' => $validated['name'],
            'code' => strtoupper($validated['code']),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Subject updated successfully.',
            'data' => $subject->fresh(),
        ]);
    }

    public function destroy(Subject $subject)
    {
        $subject->delete();

        return response()->json([
            'success' => true,
            'message' => 'Subject deleted successfully.',
        ]);
    }
}
