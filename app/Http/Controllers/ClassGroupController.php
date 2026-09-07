<?php

namespace App\Http\Controllers;

use App\Models\ClassGroup;
use App\Models\Subject;
use Illuminate\Http\Request;

class ClassGroupController extends Controller
{
    /**
     * Display a listing of groups.
     */
    public function index()
    {
        $groups = ClassGroup::with([
            'subjects',
            'groupSubjectMappings.subject',
        ])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'groups' => $groups,
        ]);
    }

    /**
     * Store a newly created group.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'group_name' => 'required|string|max:255|unique:class_groups,group_name',

            // Existing Optional Subject
            'subject_ids' => 'nullable|array',
            'subject_ids.*' => 'exists:subjects,id',

            // New Group Subject
            'group_subject_ids' => 'nullable|array',
            'group_subject_ids.*' => 'exists:subjects,id',
        ]);

        $group = ClassGroup::create([
            'group_name' => $validated['group_name'],
        ]);

        // Existing functionality - unchanged
        if (!empty($validated['subject_ids'])) {
            $group->subjects()->sync($validated['subject_ids']);
        }

        // New Group Subject
        if (!empty($validated['group_subject_ids'])) {
            $group->groupSubjectMappings()->createMany(
                collect($validated['group_subject_ids'])
                    ->map(function ($subjectId) {
                        return [
                            'subject_id' => $subjectId,
                        ];
                    })
                    ->toArray()
            );
        }

        $group->load([
            'subjects',
            'groupSubjectMappings.subject',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Group created successfully.',
            'group' => $group,
        ], 201);
    }

    /**
     * Display the specified group.
     */
    public function show(ClassGroup $classGroup)
    {
        $classGroup->load([
            'subjects',
            'groupSubjectMappings.subject',
        ]);

        return response()->json([
            'success' => true,
            'group' => $classGroup,
        ]);
    }

    /**
     * Update the specified group.
     */
    public function update(Request $request, ClassGroup $classGroup)
    {
        $validated = $request->validate([
            'group_name' => 'required|string|max:255|unique:class_groups,group_name,' . $classGroup->id,

            // Existing Optional Subject
            'subject_ids' => 'nullable|array',
            'subject_ids.*' => 'exists:subjects,id',

            // New Group Subject
            'group_subject_ids' => 'nullable|array',
            'group_subject_ids.*' => 'exists:subjects,id',
        ]);

        $classGroup->update([
            'group_name' => $validated['group_name'],
        ]);

        // Existing functionality - unchanged
        $classGroup->subjects()->sync(
            $validated['subject_ids'] ?? []
        );

        // New Group Subject
        $classGroup->groupSubjectMappings()->delete();

        if (!empty($validated['group_subject_ids'])) {
            $classGroup->groupSubjectMappings()->createMany(
                collect($validated['group_subject_ids'])
                    ->map(function ($subjectId) {
                        return [
                            'subject_id' => $subjectId,
                        ];
                    })
                    ->toArray()
            );
        }

        $classGroup->load([
            'subjects',
            'groupSubjectMappings.subject',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Group updated successfully.',
            'group' => $classGroup,
        ]);
    }

    /**
     * Remove the specified group.
     */
    public function destroy(ClassGroup $classGroup)
    {
        $classGroup->delete();

        return response()->json([
            'success' => true,
            'message' => 'Group deleted successfully.',
        ]);
    }

    /**
     * Get all subjects for group creation.
     */
    public function subjects()
    {
        $subjects = Subject::orderBy('name')->get([
            'id',
            'name',
            'code',
        ]);

        return response()->json([
            'success' => true,
            'subjects' => $subjects,
        ]);
    }
}
