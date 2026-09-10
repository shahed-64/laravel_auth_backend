<?php

namespace App\Http\Controllers;

use App\Models\FinalResult;
use App\Models\Student;
use App\Models\Result;
use App\Models\Examination;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FinalResultController extends Controller
{
    /**
     * Display all final result configurations.
     */
    public function index(Request $request): JsonResponse
    {
        $year = $request->query('year');

        $finalResults = FinalResult::with('examination')
            ->latest()
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Year-wise Percentage Totals
        |--------------------------------------------------------------------------
        */
        $yearTotals = $finalResults
            ->groupBy(function ($item) {
                return $item->examination?->examination_year;
            })
            ->map(function ($items) {
                return round(
                    $items->sum(function ($item) {
                        return (float) $item->percentage;
                    }),
                    2
                );
            });

        /*
        |--------------------------------------------------------------------------
        | Selected Year Total
        |--------------------------------------------------------------------------
        */
        $totalPercentage = 0;

        if ($year !== null && $year !== '') {
            $totalPercentage = (float) (
                $yearTotals[(string) $year] ?? 0
            );
        }

        return response()->json([
            'status' => true,
            'data' => $finalResults,

            'total_percentage' =>
                round($totalPercentage, 2),

            'remaining_percentage' =>
                round(
                    max(
                        0,
                        100 - $totalPercentage
                    ),
                    2
                ),

            'is_complete' =>
                round($totalPercentage, 2) === 100.00,

            'year_totals' =>
                $yearTotals,
        ]);
    }

    /**
     * Store a new final result examination configuration.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'examination_id' => [
                'required',
                'integer',
                'exists:examinations,id',
                'unique:final_results,examination_id',
            ],

            'percentage' => [
                'required',
                'numeric',
                'min:0.01',
                'max:100',
            ],
        ], [
            'examination_id.required' =>
                'Please select an examination.',

            'examination_id.exists' =>
                'The selected examination does not exist.',

            'examination_id.unique' =>
                'This examination has already been added to the final result configuration.',

            'percentage.required' =>
                'Percentage is required.',

            'percentage.numeric' =>
                'Percentage must be a number.',

            'percentage.min' =>
                'Percentage must be greater than 0.',

            'percentage.max' =>
                'Percentage cannot be greater than 100.',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Get Examination
        |--------------------------------------------------------------------------
        */
        $examination = Examination::find(
            $validated['examination_id']
        );

        if (!$examination) {
            return response()->json([
                'status' => false,
                'message' =>
                    'The selected examination does not exist.',
            ], 404);
        }

        $year = $examination->examination_year;

        /*
        |--------------------------------------------------------------------------
        | Current Percentage For This Year Only
        |--------------------------------------------------------------------------
        */
        $currentPercentage = (float) FinalResult::whereHas(
            'examination',
            function ($query) use ($year) {
                $query->where(
                    'examination_year',
                    $year
                );
            }
        )->sum('percentage');

        /*
        |--------------------------------------------------------------------------
        | New Total
        |--------------------------------------------------------------------------
        */
        $newTotal =
            $currentPercentage +
            (float) $validated['percentage'];

        /*
        |--------------------------------------------------------------------------
        | Prevent More Than 100%
        |--------------------------------------------------------------------------
        */
        if ($newTotal > 100) {
            return response()->json([
                'status' => false,

                'message' =>
                    "Total percentage for {$year} cannot exceed 100%.",

                'year' =>
                    $year,

                'current_percentage' =>
                    round($currentPercentage, 2),

                'requested_percentage' =>
                    (float) $validated['percentage'],

                'remaining_percentage' =>
                    round(
                        max(
                            0,
                            100 - $currentPercentage
                        ),
                        2
                    ),
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Create Configuration
        |--------------------------------------------------------------------------
        */
        $finalResult = FinalResult::create([
            'examination_id' =>
                $validated['examination_id'],

            'percentage' =>
                $validated['percentage'],
        ]);

        $finalResult->load('examination');

        /*
        |--------------------------------------------------------------------------
        | Updated Year Total
        |--------------------------------------------------------------------------
        */
        $totalPercentage = (float) FinalResult::whereHas(
            'examination',
            function ($query) use ($year) {
                $query->where(
                    'examination_year',
                    $year
                );
            }
        )->sum('percentage');

        $totalPercentage =
            round($totalPercentage, 2);

        return response()->json([
            'status' => true,

            'message' =>
                'Final result examination configuration added successfully.',

            'data' =>
                $finalResult,

            'year' =>
                $year,

            'total_percentage' =>
                $totalPercentage,

            'remaining_percentage' =>
                round(
                    max(
                        0,
                        100 - $totalPercentage
                    ),
                    2
                ),

            'is_complete' =>
                $totalPercentage === 100.00,
        ], 201);
    }

    /**
     * Display a specific final result configuration.
     */
    public function show(
        FinalResult $finalResult
    ): JsonResponse {
        $finalResult->load('examination');

        return response()->json([
            'status' => true,
            'data' => $finalResult,
        ]);
    }

    /**
     * Update final result examination percentage.
     */
    public function update(
        Request $request,
        FinalResult $finalResult
    ): JsonResponse {
        $validated = $request->validate([
            'percentage' => [
                'required',
                'numeric',
                'min:0.01',
                'max:100',
            ],
        ], [
            'percentage.required' =>
                'Percentage is required.',

            'percentage.numeric' =>
                'Percentage must be a number.',

            'percentage.min' =>
                'Percentage must be greater than 0.',

            'percentage.max' =>
                'Percentage cannot be greater than 100.',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Get Examination Year
        |--------------------------------------------------------------------------
        */
        $finalResult->load('examination');

        $year =
            $finalResult->examination?->examination_year;

        /*
        |--------------------------------------------------------------------------
        | Current Year Total Without This Record
        |--------------------------------------------------------------------------
        */
        $currentTotal = (float) FinalResult::whereHas(
            'examination',
            function ($query) use ($year) {
                $query->where(
                    'examination_year',
                    $year
                );
            }
        )
            ->where(
                'id',
                '!=',
                $finalResult->id
            )
            ->sum('percentage');

        /*
        |--------------------------------------------------------------------------
        | New Total
        |--------------------------------------------------------------------------
        */
        $newTotal =
            $currentTotal +
            (float) $validated['percentage'];

        /*
        |--------------------------------------------------------------------------
        | Prevent More Than 100%
        |--------------------------------------------------------------------------
        */
        if ($newTotal > 100) {
            return response()->json([
                'status' => false,

                'message' =>
                    "Total percentage for {$year} cannot exceed 100%.",

                'year' =>
                    $year,

                'current_percentage' =>
                    round($currentTotal, 2),

                'requested_percentage' =>
                    (float) $validated['percentage'],

                'remaining_percentage' =>
                    round(
                        max(
                            0,
                            100 - $currentTotal
                        ),
                        2
                    ),
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Update
        |--------------------------------------------------------------------------
        */
        $finalResult->update([
            'percentage' =>
                $validated['percentage'],
        ]);

        $finalResult->load('examination');

        /*
        |--------------------------------------------------------------------------
        | Updated Year Total
        |--------------------------------------------------------------------------
        */
        $totalPercentage = (float) FinalResult::whereHas(
            'examination',
            function ($query) use ($year) {
                $query->where(
                    'examination_year',
                    $year
                );
            }
        )->sum('percentage');

        $totalPercentage =
            round($totalPercentage, 2);

        return response()->json([
            'status' => true,

            'message' =>
                'Final result percentage updated successfully.',

            'data' =>
                $finalResult,

            'year' =>
                $year,

            'total_percentage' =>
                $totalPercentage,

            'remaining_percentage' =>
                round(
                    max(
                        0,
                        100 - $totalPercentage
                    ),
                    2
                ),

            'is_complete' =>
                $totalPercentage === 100.00,
        ]);
    }

    /**
     * Remove an examination from final result configuration.
     */
    public function destroy(
        FinalResult $finalResult
    ): JsonResponse {
        /*
        |--------------------------------------------------------------------------
        | Get Year Before Delete
        |--------------------------------------------------------------------------
        */
        $finalResult->load('examination');

        $year =
            $finalResult->examination?->examination_year;

        /*
        |--------------------------------------------------------------------------
        | Delete
        |--------------------------------------------------------------------------
        */
        $finalResult->delete();

        /*
        |--------------------------------------------------------------------------
        | Recalculate Percentage For That Year
        |--------------------------------------------------------------------------
        */
        $totalPercentage = (float) FinalResult::whereHas(
            'examination',
            function ($query) use ($year) {
                $query->where(
                    'examination_year',
                    $year
                );
            }
        )->sum('percentage');

        $totalPercentage =
            round($totalPercentage, 2);

        return response()->json([
            'status' => true,

            'message' =>
                'Final result examination removed successfully.',

            'year' =>
                $year,

            'total_percentage' =>
                $totalPercentage,

            'remaining_percentage' =>
                round(
                    max(
                        0,
                        100 - $totalPercentage
                    ),
                    2
                ),

            'is_complete' =>
                $totalPercentage === 100.00,
        ]);
    }

    /**
     * ==========================================================
     * Generate Final Result For A Single Student
     * ==========================================================
     *
     * URL:
     * GET /api/final-results/student/{studentId}?year=2026
     */
    public function studentFinalResult(
        Request $request,
        $studentId
    ): JsonResponse {
        /**
         * |--------------------------------------------------------------------------
         * | Validate Year
         * |--------------------------------------------------------------------------
         */
        $year = $request->query('year');

        if (!$year) {
            return response()->json([
                'status' => false,
                'message' =>
                    'Examination year is required.',
            ], 422);
        }

        /**
         * |--------------------------------------------------------------------------
         * | Find Student
         * |--------------------------------------------------------------------------
         */
        $student = Student::with([
            'classInfo',
            'classGroup',
            'section',
            'shift',
        ])->find($studentId);

        if (!$student) {
            return response()->json([
                'status' => false,
                'message' =>
                    'Student not found.',
            ], 404);
        }

        /**
         * |--------------------------------------------------------------------------
         * | Final Result Configuration For Selected Year
         * |--------------------------------------------------------------------------
         */
        $finalResults = FinalResult::with('examination')
            ->whereHas('examination', function ($query) use ($year) {
                $query->where(
                    'examination_year',
                    $year
                );
            })
            ->get();

        if ($finalResults->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' =>
                    "No final result configuration found for {$year}.",
            ], 404);
        }

        /**
         * |--------------------------------------------------------------------------
         * | Total Percentage
         * |--------------------------------------------------------------------------
         */
        $totalPercentage =
            $finalResults->sum(function ($item) {
                return (float) $item->percentage;
            });

        $totalPercentage =
            round($totalPercentage, 2);

        /**
         * |--------------------------------------------------------------------------
         * | Final Result Must Be Exactly 100%
         * |--------------------------------------------------------------------------
         */
        if ($totalPercentage !== 100.00) {
            return response()->json([
                'status' => false,
                'message' =>
                    'Final result configuration must total exactly 100%.',
                'total_percentage' =>
                    $totalPercentage,
                'remaining_percentage' =>
                    round(
                        100 - $totalPercentage,
                        2
                    ),
            ], 422);
        }

        /**
         * |--------------------------------------------------------------------------
         * | Get Student Results
         * |--------------------------------------------------------------------------
         * |
         * | Existing Result System remains untouched.
         * |
         * |--------------------------------------------------------------------------
         */
        $results = Result::with([
            'resultSubjects.subject',
        ])
            ->where('student_id', $studentId)
            ->where('exam_year', $year)
            ->get();

        /**
         * |--------------------------------------------------------------------------
         * | No Result Found
         * |--------------------------------------------------------------------------
         */
        if ($results->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' =>
                    "No examination results found for this student in {$year}.",
            ], 404);
        }

        /**
         * |--------------------------------------------------------------------------
         * | Build Exam Data
         * |--------------------------------------------------------------------------
         */
        $examResults = [];

        foreach ($finalResults as $configuration) {
            $exam = $configuration->examination;

            /**
             * |--------------------------------------------------------------------------
             * | Find Student Result For This Examination
             * |--------------------------------------------------------------------------
             */
            $result = $results->first(function ($item) use ($exam) {
                return
                    (string) $item->exam_type ===
                    (string) $exam->examination_type;
            });

            $examResults[] = [
                'id' =>
                    $exam->id,
                'name' =>
                    $exam->examination_type,
                'year' =>
                    $exam->examination_year,
                'exam_mark' =>
                    $exam->exam_mark !== null
                        ? (float) $exam->exam_mark
                        : null,
                'percentage' =>
                    (float) $configuration->percentage,
                'result_id' =>
                    $result?->id,
                'subjects' =>
                    $result
                        ? $result->resultSubjects
                        : [],
            ];
        }

        /**
         * |--------------------------------------------------------------------------
         * | Build Subject Collection
         * |--------------------------------------------------------------------------
         */
        $subjects = [];

        foreach ($examResults as $examData) {
            foreach ($examData['subjects'] as $resultSubject) {
                $subject = $resultSubject->subject;

                if (!$subject) {
                    continue;
                }

                $subjectId = $subject->id;

                /**
                 * |--------------------------------------------------------------------------
                 * | Create Subject
                 * |--------------------------------------------------------------------------
                 */
                if (!isset($subjects[$subjectId])) {
                    $subjects[$subjectId] = [
                        'id' =>
                            $subjectId,
                        'name' =>
                            $subject->subject_name
                            ?? $subject->name
                            ?? 'Unknown Subject',
                        'full_mark' =>
                            $subject->full_mark !== null
                                ? (float) $subject->full_mark
                                : null,
                        'exams' => [],
                    ];
                }

                /**
                 * |--------------------------------------------------------------------------
                 * | Marks
                 * |--------------------------------------------------------------------------
                 */
                $marks =
                    $resultSubject->marks ?? 0;

                /**
                 * |--------------------------------------------------------------------------
                 * | Full Mark
                 * |--------------------------------------------------------------------------
                 */
                $fullMark =
                    $examData['exam_mark']
                    ?? $subject->full_mark
                    ?? 100;

                /**
                 * |--------------------------------------------------------------------------
                 * | Save Exam Marks
                 * |--------------------------------------------------------------------------
                 */
                $subjects[$subjectId]['exams'][
                    $examData['id']
                ] = [
                    'marks' =>
                        is_numeric($marks)
                            ? (float) $marks
                            : 0,
                    'full_mark' =>
                        (float) $fullMark,
                    'percentage' =>
                        (float) $examData['percentage'],
                ];
            }
        }

        /**
         * |--------------------------------------------------------------------------
         * | Process Final Subject Results
         * |--------------------------------------------------------------------------
         */
        $processedSubjects = [];

        foreach ($subjects as $subject) {
            $weightedPercentage = 0;

            /**
             * |--------------------------------------------------------------------------
             * | Calculate Weighted Percentage
             * |--------------------------------------------------------------------------
             */
            foreach ($examResults as $examData) {
                $examId =
                    $examData['id'];

                $examSubject =
                    $subject['exams'][$examId]
                    ?? null;

                /**
                 * |--------------------------------------------------------------------------
                 * | If Student Has No Result For This Subject In This Exam
                 * |--------------------------------------------------------------------------
                 */
                if (!$examSubject) {
                    continue;
                }

                $marks =
                    (float) $examSubject['marks'];

                $fullMark =
                    (float) $examSubject['full_mark'];

                if ($fullMark <= 0) {
                    continue;
                }

                /**
                 * |--------------------------------------------------------------------------
                 * | Convert Marks To Percentage
                 * |--------------------------------------------------------------------------
                 */
                $examPercentage =
                    ($marks / $fullMark) * 100;

                /**
                 * |--------------------------------------------------------------------------
                 * | Apply Final Result Weight
                 * |--------------------------------------------------------------------------
                 */
                $weightedPercentage +=
                    $examPercentage
                    *
                    (
                        (float) $examData['percentage']
                        / 100
                    );
            }

            $weightedPercentage =
                round(
                    $weightedPercentage,
                    2
                );

            /**
             * |--------------------------------------------------------------------------
             * | Grade
             * |--------------------------------------------------------------------------
             */
            if ($weightedPercentage >= 80) {
                $grade = 'A+';
                $point = 5.00;
            } elseif ($weightedPercentage >= 70) {
                $grade = 'A';
                $point = 4.00;
            } elseif ($weightedPercentage >= 60) {
                $grade = 'A-';
                $point = 3.50;
            } elseif ($weightedPercentage >= 50) {
                $grade = 'B';
                $point = 3.00;
            } elseif ($weightedPercentage >= 40) {
                $grade = 'C';
                $point = 2.00;
            } elseif ($weightedPercentage >= 33) {
                $grade = 'D';
                $point = 1.00;
            } else {
                $grade = 'F';
                $point = 0.00;
            }

            /**
             * |--------------------------------------------------------------------------
             * | Prepare Exam Marks For Frontend
             * |--------------------------------------------------------------------------
             */
            $examMarks = [];

            foreach ($examResults as $examData) {
                $examId =
                    $examData['id'];

                $examSubject =
                    $subject['exams'][$examId]
                    ?? null;

                $examMarks[$examId] = [
                    'marks' =>
                        $examSubject
                            ? $examSubject['marks']
                            : null,
                    'full_mark' =>
                        $examSubject
                            ? $examSubject['full_mark']
                            : null,
                    'percentage' =>
                        $examData['percentage'],
                ];
            }

            /**
             * |--------------------------------------------------------------------------
             * | Final Subject Data
             * |--------------------------------------------------------------------------
             */
            $processedSubjects[] = [
                'id' =>
                    $subject['id'],
                'name' =>
                    $subject['name'],
                'full_mark' =>
                    $subject['full_mark'],
                'exams' =>
                    $examMarks,
                'obtained_total' =>
                    $weightedPercentage,
                'letter_grade' =>
                    $grade,
                'grade_point' =>
                    $point,
            ];
        }

        /**
         * |--------------------------------------------------------------------------
         * | Overall GPA
         * |--------------------------------------------------------------------------
         */
        $overallGpa = 0;

        if (count($processedSubjects) > 0) {
            $totalPoint = 0;
            $subjectCount = 0;

            foreach ($processedSubjects as $subject) {
                $totalPoint +=
                    (float) $subject['grade_point'];

                $subjectCount++;
            }

            if ($subjectCount > 0) {
                $overallGpa =
                    $totalPoint /
                    $subjectCount;
            }
        }

        /**
         * |--------------------------------------------------------------------------
         * | Maximum GPA = 5
         * |--------------------------------------------------------------------------
         */
        $overallGpa =
            min(
                5,
                $overallGpa
            );

        /**
         * |--------------------------------------------------------------------------
         * | Student Information
         * |--------------------------------------------------------------------------
         */
        $studentData = [
            'id' =>
                $student->id,
            'name' =>
                $student->full_name,
            'student_id' =>
                $student->student_id,
            'image' =>
                $student->image,
            'campus' =>
                $student->campus ?? null,
            'shift' =>
                $student->shift?->shift_name
                ?? $student->shift?->name
                ?? null,
            'version' =>
                $student->version ?? null,
            'session' =>
                $student->session ?? null,
            'class' =>
                $student->classInfo?->class_name
                ?? null,
            'group' =>
                $student->classGroup?->group_name
                ?? null,
            'section' =>
                $student->section?->section_name
                ?? null,
            'roll' =>
                $student->roll ?? null,
        ];

        /**
         * |--------------------------------------------------------------------------
         * | Final Response
         * |--------------------------------------------------------------------------
         */
        return response()->json([
            'status' => true,

            'year' =>
                $year,

            'student' =>
                $studentData,

            'exams' =>
                $examResults,

            'subjects' =>
                $processedSubjects,

            'total_percentage' =>
                $totalPercentage,

            'overall_gpa' =>
                round(
                    $overallGpa,
                    2
                ),
        ]);
    }
}
