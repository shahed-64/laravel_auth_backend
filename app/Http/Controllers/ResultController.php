<?php

namespace App\Http\Controllers;

use App\Models\Result;
use App\Models\Student;
use App\Models\ClassGroup;
use App\Models\GroupSubjectMapping;
use App\Models\GradingSystem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class ResultController extends Controller
{
    public function index()
    {
        $results = Result::with([
            'student.classInfo',
            'student.classGroup',
            'resultSubjects.subject'
        ])
            ->latest()
            ->get();

        $students = Student::with([
            'classInfo.subjects',
            'classGroup.subjects'
        ])->get();

        $allMappedSubjectIds = GroupSubjectMapping::pluck('subject_id')
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->toArray();

        $allAdditionalSubjectIds = DB::table('group_subjects')
            ->pluck('subject_id')
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->toArray();

        $students->each(function ($student) use (
            $allMappedSubjectIds,
            $allAdditionalSubjectIds
        ) {
            $group = $student->classGroup;

            $student->setAttribute(
                'group_name',
                $group?->group_name
            );

            if ($student->classInfo) {
                $commonSubjects = $student->classInfo->subjects
                    ->filter(function ($subject) use (
                        $allMappedSubjectIds,
                        $allAdditionalSubjectIds
                    ) {
                        $subjectId = (int) $subject->id;

                        if (in_array(
                            $subjectId,
                            $allMappedSubjectIds,
                            true
                        )) {
                            return false;
                        }

                        if (in_array(
                            $subjectId,
                            $allAdditionalSubjectIds,
                            true
                        )) {
                            return false;
                        }

                        return true;
                    })
                    ->values();
            } else {
                $commonSubjects = collect();
            }

            if ($student->classInfo) {
                $student->classInfo->setRelation(
                    'subjects',
                    $commonSubjects
                );
            }

            if ($group) {
                $groupSubjects = $group->subjects
                    ->map(function ($subject) {
                        return [
                            'id' => $subject->id,
                            'name' => $subject->name,
                            'code' => $subject->code,
                            'is_additional' => true,
                        ];
                    })
                    ->values();
            } else {
                $groupSubjects = collect();
            }

            $student->setAttribute(
                'group_subjects',
                $groupSubjects
            );

            if ($group) {
                $mappedGroupSubjects = GroupSubjectMapping::with('subject')
                    ->where(
                        'class_group_id',
                        $group->id
                    )
                    ->get()
                    ->map(function ($mapping) {
                        return [
                            'id' => $mapping->subject?->id,
                            'name' => $mapping->subject?->name,
                            'code' => $mapping->subject?->code,
                            'is_additional' => false,
                            'class_group_id' =>
                                $mapping->class_group_id,
                        ];
                    })
                    ->filter(function ($subject) {
                        return !empty($subject['id']);
                    })
                    ->values();
            } else {
                $mappedGroupSubjects = collect();
            }

            $currentAdditionalSubjectIds = $groupSubjects
                ->pluck('id')
                ->map(fn($id) => (int) $id)
                ->unique()
                ->values()
                ->toArray();

            $mappedGroupSubjects = $mappedGroupSubjects
                ->filter(function ($subject) use (
                    $currentAdditionalSubjectIds
                ) {
                    return !in_array(
                        (int) $subject['id'],
                        $currentAdditionalSubjectIds,
                        true
                    );
                })
                ->values();

            $student->setAttribute(
                'mapped_group_subjects',
                $mappedGroupSubjects
            );
        });

        return response()->json([
            'status' => true,
            'results' => $results,
            'students' => $students
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'student_id' => [
                'required',
                'exists:students,id',
                Rule::unique('results')->where(function ($query) use ($request) {
                    return $query
                        ->where(
                            'exam_year',
                            $request->exam_year
                        )
                        ->where(
                            'exam_type',
                            $request->exam_type
                        );
                }),
            ],

            'exam_year' => 'required|string|max:255',

            'exam_type' => 'required|string|max:255',

            'subjects' => 'required|array|min:1',

            'subjects.*.subject_id' => [
                'required',
                'integer',
                'exists:subjects,id',
            ],

            'subjects.*.marks' => [
                'nullable',
                'numeric',
                'between:0,999.99',
            ],

            'subjects.*.is_additional' => [
                'nullable',
                'boolean',
            ],
        ], [
            'student_id.unique' =>
                'This student already has a result entered for this exam type and year!',

            'subjects.required' =>
                'At least one subject is required.',

            'subjects.min' =>
                'At least one subject is required.',
        ]);

        $student = Student::with([
            'classInfo.subjects',
            'classGroup.subjects'
        ])->findOrFail(
            $validated['student_id']
        );

        $classSubjectIds = $student->classInfo
            ? $student->classInfo->subjects
                ->pluck('id')
                ->map(fn($id) => (int) $id)
                ->toArray()
            : [];

        $group = $student->classGroup;

        $groupSubjectIds = $group
            ? $group->subjects
                ->pluck('id')
                ->map(fn($id) => (int) $id)
                ->toArray()
            : [];

        $mappedGroupSubjectIds = $group
            ? GroupSubjectMapping::where(
                'class_group_id',
                $group->id
            )
                ->pluck('subject_id')
                ->map(fn($id) => (int) $id)
                ->toArray()
            : [];

        $assignedSubjectIds = array_values(
            array_unique(
                array_merge(
                    $classSubjectIds,
                    $groupSubjectIds,
                    $mappedGroupSubjectIds
                )
            )
        );

        foreach ($validated['subjects'] as $subjectData) {
            $subjectId = (int) $subjectData['subject_id'];

            if (!in_array(
                $subjectId,
                $assignedSubjectIds
            )) {
                return response()->json([
                    'success' => false,
                    'message' =>
                        'One or more selected subjects are not assigned to this student.'
                ], 422);
            }
        }

        $result = DB::transaction(function () use ($validated) {

            $result = Result::create([
                'student_id' =>
                    $validated['student_id'],

                'exam_year' =>
                    $validated['exam_year'],

                'exam_type' =>
                    $validated['exam_type'],
            ]);

            foreach ($validated['subjects'] as $subjectData) {
                $result->resultSubjects()->create([
                    'subject_id' =>
                        $subjectData['subject_id'],

                    'marks' =>
                        $subjectData['marks'] ?? null,
                ]);
            }

            return $result;
        });

        $result->load([
            'student.classInfo',
            'student.classGroup',
            'resultSubjects.subject'
        ]);

        return response()->json([
            'success' => true,

            'message' =>
                'Result successfully stored!',

            'data' =>
                $result

        ], 201);
    }

    public function show($id): JsonResponse
    {
        $result = Result::with([
            'student.classInfo',
            'student.classGroup.subjects',
            'resultSubjects.subject'
        ])->findOrFail($id);

        /*
        |--------------------------------------------------------------------------
        | Dynamic Grade Calculation
        |--------------------------------------------------------------------------
        |
        | Percentage = Obtained Marks / Full Mark * 100
        |
        | Grade and Grade Point are now loaded dynamically
        | from the grading_systems table.
        |
        */

        $getGradeAndPoint = function (
            $marks,
            $fullMark = 100
        ) {
            if ($marks === null) {
                return null;
            }

            $obtainedMarks = (float) $marks;

            $maximumMarks = (float) (
                $fullMark ?: 100
            );

            if ($maximumMarks <= 0) {
                return null;
            }

            /*
            |--------------------------------------------------------------------------
            | Calculate Percentage
            |--------------------------------------------------------------------------
            */

            $percentage =
                ($obtainedMarks / $maximumMarks) * 100;

            /*
            |--------------------------------------------------------------------------
            | Get Grade From Database
            |--------------------------------------------------------------------------
            |
            | Example:
            |
            | 92% -> A+
            | 75% -> A
            | 65% -> A-
            |
            */

            $grading = GradingSystem::where(
                'min_percentage',
                '<=',
                $percentage
            )
                ->orderByDesc('min_percentage')
                ->first();

            /*
            |--------------------------------------------------------------------------
            | Fallback
            |--------------------------------------------------------------------------
            |
            | If no grading rule exists, return F.
            |
            */

            if (!$grading) {
                return [
                    'grade' => 'F',
                    'point' => 0.00
                ];
            }

            return [
                'grade' => $grading->grade,

                'point' => (float) $grading->grade_point
            ];
        };

        $subjects = [];

        $totalPoints = 0;

        $subjectCount = 0;

        $hasFailed = false;

        $student = $result->student;

        $group = $student?->classGroup;

        $groupSubjectIds = $group
            ? $group->subjects
                ->pluck('id')
                ->map(fn($id) => (int) $id)
                ->toArray()
            : [];

        foreach (
            $result->resultSubjects
            as $resultSubject
        ) {
            $marks = $resultSubject->marks;

            if ($marks === null) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Get Subject Full Mark
            |--------------------------------------------------------------------------
            */

            $fullMark =
                $resultSubject->subject?->full_mark
                ?? 100;

            /*
            |--------------------------------------------------------------------------
            | Calculate Dynamic Grade
            |--------------------------------------------------------------------------
            */

            $gradePoint = $getGradeAndPoint(
                $marks,
                $fullMark
            );

            if ($gradePoint === null) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Check Additional Subject
            |--------------------------------------------------------------------------
            */

            $isAdditional = in_array(
                (int) $resultSubject->subject_id,
                $groupSubjectIds
            );

            /*
            |--------------------------------------------------------------------------
            | Subject Response
            |--------------------------------------------------------------------------
            */

            $subjects[] = [
                'id' =>
                    $resultSubject->subject_id,

                'subject_id' =>
                    $resultSubject->subject_id,

                'subject_name' =>
                    $resultSubject->subject->name
                    ?? 'Unknown Subject',

                'subject_code' =>
                    $resultSubject->subject->code
                    ?? null,

                'marks' =>
                    $marks,

                /*
                |--------------------------------------------------------------------------
                | Full Mark
                |--------------------------------------------------------------------------
                */

                'full_mark' =>
                    $fullMark,

                /*
                |--------------------------------------------------------------------------
                | Dynamic Grade
                |--------------------------------------------------------------------------
                */

                'grade' =>
                    $gradePoint['grade'],

                'point' =>
                    number_format(
                        $gradePoint['point'],
                        2
                    ),

                'is_additional' =>
                    $isAdditional,
            ];

            /*
            |--------------------------------------------------------------------------
            | GPA Calculation
            |--------------------------------------------------------------------------
            */

            $totalPoints +=
                $gradePoint['point'];

            $subjectCount++;

            if (
                $gradePoint['point'] == 0
            ) {
                $hasFailed = true;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Final GPA
        |--------------------------------------------------------------------------
        */

        $finalGpa = 0.00;

        if (
            $subjectCount > 0
            && !$hasFailed
        ) {
            $finalGpa =
                $totalPoints / $subjectCount;

            $finalGpa =
                min(5.00, $finalGpa);
        }

        $groupName =
            $group?->group_name;

        /*
        |--------------------------------------------------------------------------
        | Final Response
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'status' => true,

            'result' => [

                'student_name' =>
                    $student->full_name
                    ?? $student->name
                    ?? '[STUDENT NAME]',

                'father_name' =>
                    $student->fathers_name
                    ?? '[FATHER NAME]',

                'mother_name' =>
                    $student->mothers_name
                    ?? '[MOTHER NAME]',

                'institution_name' =>
                    $student->institution_name
                    ?? '[INSTITUTION NAME]',

                'roll' =>
                    $student->roll
                    ?? $student->student_id
                    ?? '[ROLL NO]',

                'reg_no' =>
                    $student->reg_no
                    ?? '[REGISTRATION NO]',

                'course_name' =>
                    $student->course_name
                    ?? null,

                'group_name' =>
                    $groupName,

                'class_name' =>
                    $student->classInfo->class_name
                    ?? 'N/A',

                'type' =>
                    $result->exam_type
                    ?? '[TYPE]',

                'year' =>
                    $result->exam_year,

                'gpa' =>
                    number_format(
                        $finalGpa,
                        2
                    ),

                'gpa_without_additional' =>
                    number_format(
                        $finalGpa,
                        2
                    ),

                'publication_date' =>
                    $result->created_at
                        ? $result->created_at
                            ->format('d F Y')
                        : null,

                'subjects' =>
                    $subjects,

                'additional_subject' =>
                    collect($subjects)
                        ->where(
                            'is_additional',
                            true
                        )
                        ->values()
                        ->all(),
            ]
        ]);
    }

    public function edit(Result $result)
    {
    }

    public function update(
        Request $request,
        Result $result
    ) {
    }

    public function destroy(Result $result)
    {
    }
}
