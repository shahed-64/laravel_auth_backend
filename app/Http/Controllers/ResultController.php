<?php

namespace App\Http\Controllers;

use App\Models\Result;
use App\Models\Student;
use App\Models\ClassGroup;
use App\Models\GroupSubjectMapping;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class ResultController extends Controller
{
    /**
     * Display results + students with:
     *
     * Student
     *   ↓
     * Class Subjects
     *   +
     * Class Group
     *   ↓
     * Existing Group Subjects (Additional Subject)
     *   +
     * Group Subject Mappings (NEW)
     */
public function index()
{
    /*
    |--------------------------------------------------------------------------
    | Results
    |--------------------------------------------------------------------------
    */

    $results = Result::with([
        'student.classInfo',
        'student.classGroup',
        'resultSubjects.subject'
    ])
        ->latest()
        ->get();


    /*
    |--------------------------------------------------------------------------
    | Students
    |--------------------------------------------------------------------------
    | Existing relations are kept.
    */

    $students = Student::with([
        'classInfo.subjects',
        'classGroup.subjects'
    ])->get();


    /*
    |--------------------------------------------------------------------------
    | ALL GROUP MAPPED SUBJECT IDS
    |--------------------------------------------------------------------------
    |
    | এগুলো group_subject_mappings table থেকে আসবে।
    |
    | Example:
    |
    | Science:
    | Physics
    | Chemistry
    | Biology
    |
    | Commerce:
    | Accounting
    | Finance
    |
    */

    $allMappedSubjectIds = GroupSubjectMapping::pluck('subject_id')
        ->map(fn($id) => (int) $id)
        ->unique()
        ->values()
        ->toArray();


    /*
    |--------------------------------------------------------------------------
    | ALL ADDITIONAL SUBJECT IDS
    |--------------------------------------------------------------------------
    |
    | group_subjects table-এ যেসব subject Additional হিসেবে
    | assigned আছে, সেগুলো এখানে নেওয়া হচ্ছে।
    |
    | IMPORTANT:
    | কোনো subject যদি Additional Subject হয়,
    | তাহলে সেটা অন্য group-এর Main Subject হবে না।
    |
    */

    $allAdditionalSubjectIds = DB::table('group_subjects')
        ->pluck('subject_id')
        ->map(fn($id) => (int) $id)
        ->unique()
        ->values()
        ->toArray();


    /*
    |--------------------------------------------------------------------------
    | Attach Group Information + Subject Mapping
    |--------------------------------------------------------------------------
    */

    $students->each(function ($student) use (
        $allMappedSubjectIds,
        $allAdditionalSubjectIds
    ) {

        /*
        |--------------------------------------------------------------------------
        | Student's Group
        |--------------------------------------------------------------------------
        */

        $group = $student->classGroup;


        /*
        |--------------------------------------------------------------------------
        | Group Name
        |--------------------------------------------------------------------------
        */

        $student->setAttribute(
            'group_name',
            $group?->group_name
        );


        /*
        |--------------------------------------------------------------------------
        | COMMON CLASS SUBJECTS
        |--------------------------------------------------------------------------
        |
        | classInfo.subjects থেকে:
        |
        | 1. Group mapped subjects বাদ যাবে
        | 2. Additional subjects বাদ যাবে
        |
        | ফলে এগুলো আর ভুল করে Main Subject হিসেবে আসবে না।
        |
        */

        if ($student->classInfo) {

            $commonSubjects = $student->classInfo->subjects
                ->filter(function ($subject) use (
                    $allMappedSubjectIds,
                    $allAdditionalSubjectIds
                ) {

                    $subjectId = (int) $subject->id;


                    // Group mapping-এর subject হলে
                    // সাধারণ class subject হিসেবে দেখাবে না।

                    if (in_array(
                        $subjectId,
                        $allMappedSubjectIds,
                        true
                    )) {
                        return false;
                    }


                    // কোনো group-এর Additional Subject হলে
                    // সাধারণ Main Subject হিসেবে দেখাবে না।

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


        /*
        |--------------------------------------------------------------------------
        | Replace Class Subjects With Filtered Subjects
        |--------------------------------------------------------------------------
        */

        if ($student->classInfo) {

            $student->classInfo->setRelation(
                'subjects',
                $commonSubjects
            );

        }


        /*
        |--------------------------------------------------------------------------
        | EXISTING GROUP SUBJECTS
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        | এই logic আগের মতোই রাখা হয়েছে।
        |
        | এগুলো Current Student-এর Additional Subject।
        |
        */

        if ($group) {

            $groupSubjects = $group->subjects
                ->map(function ($subject) {

                    return [
                        'id' => $subject->id,
                        'name' => $subject->name,
                        'code' => $subject->code,

                        // Existing Additional Subject
                        'is_additional' => true,
                    ];

                })
                ->values();

        } else {

            $groupSubjects = collect();

        }


        /*
        |--------------------------------------------------------------------------
        | Send Existing Group Subjects
        |--------------------------------------------------------------------------
        */

        $student->setAttribute(
            'group_subjects',
            $groupSubjects
        );


        /*
        |--------------------------------------------------------------------------
        | CURRENT STUDENT'S GROUP MAPPING
        |--------------------------------------------------------------------------
        |
        | শুধু Student-এর নিজের group-এর mapped subjects আসবে।
        |
        | এগুলো Main Subject।
        |
        */

        if ($group) {

            $mappedGroupSubjects = GroupSubjectMapping::with('subject')
                ->where('class_group_id', $group->id)
                ->get()
                ->map(function ($mapping) {

                    return [
                        'id' => $mapping->subject?->id,
                        'name' => $mapping->subject?->name,
                        'code' => $mapping->subject?->code,

                        // Mapping Subject = Main Subject
                        'is_additional' => false,

                        'class_group_id' => $mapping->class_group_id,
                    ];

                })
                ->filter(function ($subject) {

                    return !empty($subject['id']);

                })
                ->values();

        } else {

            $mappedGroupSubjects = collect();

        }


        /*
        |--------------------------------------------------------------------------
        | REMOVE ADDITIONAL SUBJECT FROM MAPPED MAIN SUBJECT
        |--------------------------------------------------------------------------
        |
        | যদি current group-এর mapping-এ এমন কোনো subject থাকে
        | যেটা Additional Subject হিসেবে assigned,
        | তাহলে সেটা Main হিসেবে দেখাবে না।
        |
        | Current group-এর Additional Subject অবশ্যই
        | group_subjects-এর মাধ্যমে Additional section-এ থাকবে।
        |
        */

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


        /*
        |--------------------------------------------------------------------------
        | Send Mapped Group Subjects
        |--------------------------------------------------------------------------
        */

        $student->setAttribute(
            'mapped_group_subjects',
            $mappedGroupSubjects
        );
    });


    /*
    |--------------------------------------------------------------------------
    | Response
    |--------------------------------------------------------------------------
    */

    return response()->json([
        'status' => true,
        'results' => $results,
        'students' => $students
    ]);
}


    /**
     * Store a newly created result.
     */
    public function store(Request $request): JsonResponse
    {
        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */
        $validated = $request->validate([

            'student_id' => [
                'required',
                'exists:students,id',

                Rule::unique('results')->where(function ($query) use ($request) {

                    return $query
                        ->where('exam_year', $request->exam_year)
                        ->where('exam_type', $request->exam_type);
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


        /*
        |--------------------------------------------------------------------------
        | Find Student
        |--------------------------------------------------------------------------
        */
        $student = Student::with([
            'classInfo.subjects',
            'classGroup.subjects'
        ])->findOrFail($validated['student_id']);


        /*
        |--------------------------------------------------------------------------
        | CLASS SUBJECTS
        |--------------------------------------------------------------------------
        |
        | Existing logic
        |
        */
        $classSubjectIds = $student->classInfo
            ? $student->classInfo->subjects
                ->pluck('id')
                ->map(fn($id) => (int) $id)
                ->toArray()
            : [];


        /*
        |--------------------------------------------------------------------------
        | GROUP
        |--------------------------------------------------------------------------
        */
        $group = $student->classGroup;


        /*
        |--------------------------------------------------------------------------
        | EXISTING GROUP SUBJECT IDS
        |--------------------------------------------------------------------------
        |
        | Existing Additional Subject logic.
        |
        */
        $groupSubjectIds = $group
            ? $group->subjects
                ->pluck('id')
                ->map(fn($id) => (int) $id)
                ->toArray()
            : [];


        /*
        |--------------------------------------------------------------------------
        | NEW: GROUP SUBJECT MAPPING IDS
        |--------------------------------------------------------------------------
        |
        | Student-এর group অনুযায়ী শুধু mapped subjects নেওয়া হচ্ছে।
        |
        */
        $mappedGroupSubjectIds = $group
            ? GroupSubjectMapping::where(
                'class_group_id',
                $group->id
            )
                ->pluck('subject_id')
                ->map(fn($id) => (int) $id)
                ->toArray()
            : [];


        /*
        |--------------------------------------------------------------------------
        | ALL ASSIGNED SUBJECT IDS
        |--------------------------------------------------------------------------
        |
        | Existing:
        | Class Subjects
        | +
        | Existing Group Subjects
        |
        | New:
        | Group Subject Mappings
        |
        */
        $assignedSubjectIds = array_values(
            array_unique(
                array_merge(
                    $classSubjectIds,
                    $groupSubjectIds,
                    $mappedGroupSubjectIds
                )
            )
        );


        /*
        |--------------------------------------------------------------------------
        | Validate Submitted Subjects
        |--------------------------------------------------------------------------
        */
        foreach ($validated['subjects'] as $subjectData) {

            $subjectId = (int) $subjectData['subject_id'];

            /*
            | Subject must belong to one of:
            |
            | 1. Class Subjects
            | 2. Existing Group Subjects
            | 3. Group Subject Mappings
            |
            */
            if (!in_array($subjectId, $assignedSubjectIds)) {

                return response()->json([
                    'success' => false,
                    'message' =>
                        'One or more selected subjects are not assigned to this student.'
                ], 422);
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Create Result + Result Subjects
        |--------------------------------------------------------------------------
        */
        $result = DB::transaction(function () use ($validated) {

            /*
            |--------------------------------------------------------------------------
            | Main Result
            |--------------------------------------------------------------------------
            */
            $result = Result::create([

                'student_id' =>
                    $validated['student_id'],

                'exam_year' =>
                    $validated['exam_year'],

                'exam_type' =>
                    $validated['exam_type'],
            ]);


            /*
            |--------------------------------------------------------------------------
            | Subject Marks
            |--------------------------------------------------------------------------
            */
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


        /*
        |--------------------------------------------------------------------------
        | Load Relations
        |--------------------------------------------------------------------------
        */
        $result->load([
            'student.classInfo',
            'student.classGroup',
            'resultSubjects.subject'
        ]);


        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */
        return response()->json([

            'success' => true,

            'message' =>
                'Result successfully stored!',

            'data' =>
                $result

        ], 201);
    }


    /**
     * Display the specified result.
     */
    public function show($id): JsonResponse
    {
        /*
        |--------------------------------------------------------------------------
        | Load Result
        |--------------------------------------------------------------------------
        */
        $result = Result::with([
            'student.classInfo',
            'student.classGroup.subjects',
            'resultSubjects.subject'
        ])->findOrFail($id);


        /*
        |--------------------------------------------------------------------------
        | Grade + Point Calculator
        |--------------------------------------------------------------------------
        */
        $getGradeAndPoint = function ($marks) {

            if ($marks === null) {
                return null;
            }

            $m = (float) $marks;

            if ($m >= 80) {
                return [
                    'grade' => 'A+',
                    'point' => 5.00
                ];
            }

            if ($m >= 70) {
                return [
                    'grade' => 'A',
                    'point' => 4.00
                ];
            }

            if ($m >= 60) {
                return [
                    'grade' => 'A-',
                    'point' => 3.50
                ];
            }

            if ($m >= 50) {
                return [
                    'grade' => 'B',
                    'point' => 3.00
                ];
            }

            if ($m >= 40) {
                return [
                    'grade' => 'C',
                    'point' => 2.00
                ];
            }

            if ($m >= 33) {
                return [
                    'grade' => 'D',
                    'point' => 1.00
                ];
            }

            return [
                'grade' => 'F',
                'point' => 0.00
            ];
        };


        /*
        |--------------------------------------------------------------------------
        | Dynamic Subjects
        |--------------------------------------------------------------------------
        */
        $subjects = [];

        $totalPoints = 0;

        $subjectCount = 0;

        $hasFailed = false;


        /*
        |--------------------------------------------------------------------------
        | Student
        |--------------------------------------------------------------------------
        */
        $student = $result->student;


        /*
        |--------------------------------------------------------------------------
        | GROUP
        |--------------------------------------------------------------------------
        */
        $group = $student?->classGroup;


        /*
        |--------------------------------------------------------------------------
        | Group Subject IDs
        |--------------------------------------------------------------------------
        |
        | Existing Additional Subject logic.
        |
        */
        $groupSubjectIds = $group
            ? $group->subjects
                ->pluck('id')
                ->map(fn($id) => (int) $id)
                ->toArray()
            : [];


        /*
        |--------------------------------------------------------------------------
        | Process Result Subjects
        |--------------------------------------------------------------------------
        */
        foreach ($result->resultSubjects as $resultSubject) {

            $marks = $resultSubject->marks;


            /*
            |--------------------------------------------------------------------------
            | Marks null হলে result list-এ দেখানো হবে না
            |--------------------------------------------------------------------------
            */
            if ($marks === null) {
                continue;
            }


            $gradePoint =
                $getGradeAndPoint($marks);


            /*
            |--------------------------------------------------------------------------
            | Additional Subject
            |--------------------------------------------------------------------------
            |
            | Existing logic unchanged.
            |
            */
            $isAdditional = in_array(
                (int) $resultSubject->subject_id,
                $groupSubjectIds
            );


            /*
            |--------------------------------------------------------------------------
            | Subject Data
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
            | GPA Calculation Data
            |--------------------------------------------------------------------------
            */
            $totalPoints +=
                $gradePoint['point'];

            $subjectCount++;


            if ($gradePoint['point'] == 0) {

                $hasFailed = true;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | GPA Calculation
        |--------------------------------------------------------------------------
        */
        $finalGpa = 0.00;

        if (
            $subjectCount > 0 &&
            !$hasFailed
        ) {

            $finalGpa =
                $totalPoints / $subjectCount;

            $finalGpa =
                min(
                    5.00,
                    $finalGpa
                );
        }


        /*
        |--------------------------------------------------------------------------
        | REAL GROUP NAME
        |--------------------------------------------------------------------------
        */
        $groupName =
            $group?->group_name;


        /*
        |--------------------------------------------------------------------------
        | Student Information
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

                /*
                |--------------------------------------------------------------------------
                | Course Name
                |--------------------------------------------------------------------------
                */
                'course_name' =>
                    $student->course_name
                    ?? null,

                /*
                |--------------------------------------------------------------------------
                | REAL GROUP NAME
                |--------------------------------------------------------------------------
                */
                'group_name' =>
                    $groupName,

                /*
                |--------------------------------------------------------------------------
                | Class Name
                |--------------------------------------------------------------------------
                */
                'class_name' =>
                    $student->classInfo->class_name
                    ?? 'N/A',

                /*
                |--------------------------------------------------------------------------
                | Exam Type
                |--------------------------------------------------------------------------
                */
                'type' =>
                    $result->exam_type
                    ?? '[TYPE]',

                /*
                |--------------------------------------------------------------------------
                | Exam Year
                |--------------------------------------------------------------------------
                */
                'year' =>
                    $result->exam_year,

                /*
                |--------------------------------------------------------------------------
                | GPA
                |--------------------------------------------------------------------------
                */
                'gpa' =>
                    number_format(
                        $finalGpa,
                        2
                    ),

                /*
                |--------------------------------------------------------------------------
                | GPA Without Additional
                |--------------------------------------------------------------------------
                */
                'gpa_without_additional' =>
                    number_format(
                        $finalGpa,
                        2
                    ),

                /*
                |--------------------------------------------------------------------------
                | Publication Date
                |--------------------------------------------------------------------------
                */
                'publication_date' =>
                    $result->created_at
                        ? $result->created_at->format('d F Y')
                        : null,

                /*
                |--------------------------------------------------------------------------
                | Dynamic Subject List
                |--------------------------------------------------------------------------
                */
                'subjects' =>
                    $subjects,

                /*
                |--------------------------------------------------------------------------
                | Additional Subject
                |--------------------------------------------------------------------------
                */
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


    /**
     * Edit
     */
    public function edit(Result $result)
    {
        //
    }


    /**
     * Update
     */
    public function update(
        Request $request,
        Result $result
    ) {
        //
    }


    /**
     * Delete
     */
    public function destroy(Result $result)
    {
        //
    }
}
