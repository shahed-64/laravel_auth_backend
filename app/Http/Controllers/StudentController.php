<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class StudentController extends Controller
{
    /**
     * Display all students
     */
    public function index()
    {
        // classGroup সহ student load করা হচ্ছে
        $students = Student::with([
            'payments',
            'section',
            'classInfo',
            'classGroup',
            'shift'
        ])
            ->orderBy('id', 'desc')
            ->get();

        $allMonths = [
            'January',
            'February',
            'March',
            'April',
            'May',
            'June',
            'July',
            'August',
            'September',
            'October',
            'November',
            'December'
        ];

        $currentMonth = Carbon::now()->month;

        foreach ($students as $student) {

            $paidMonths = $student->payments
                ->pluck('month')
                ->toArray();

            $admissionMonth = Carbon::parse(
                $student->admission_date
            )->month;

            $monthsTillNow = array_slice(
                $allMonths,
                $admissionMonth - 1,
                max(
                    0,
                    $currentMonth - $admissionMonth + 1
                )
            );

            $dueMonths = array_values(
                array_diff(
                    $monthsTillNow,
                    $paidMonths
                )
            );

            $monthsTillDecember = array_slice(
                $allMonths,
                $admissionMonth - 1
            );

            $availableMonths = array_values(
                array_diff(
                    $monthsTillDecember,
                    $paidMonths
                )
            );

            $student->setAttribute(
                'due_months',
                $dueMonths
            );

            $student->setAttribute(
                'available_months',
                $availableMonths
            );
        }

        return response()->json([
            'status' => true,
            'students' => $students
        ]);
    }


    /**
     * Store a newly created student
     */
    public function store(Request $request)
    {
        $request->validate([

            'full_name' => 'required|string|max:255',

            'fathers_name' => 'required|string|max:255',

            'mothers_name' => 'required|string|max:255',

            'phone' => 'required|string|max:20',

            'section_id' => 'required|exists:sections,id',

            'class_id' => 'required|exists:clss_m_s,id',

            /*
             * IMPORTANT
             * Student-এর Group এখন class_groups table
             * থেকে আসবে।
             */
            'class_group_id' => 'required|exists:class_groups,id',

            /*
             * পুরোনো course_name রাখা হলো,
             * যাতে existing system ভেঙে না যায়।
             */
            'course_name' => 'nullable|string|max:100',

            'admission_date' => 'nullable|date',

            'email' => 'required|email|unique:students,email',

            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',

            'shift_id' => 'required|exists:shifts,id',

            'monthly_fee' => 'nullable|numeric',
        ]);


        /*
         * Image Handling
         */
        $imagePath = null;

        if ($request->hasFile('image')) {

            $file = $request->file('image');

            $filename =
                time() .
                '_' .
                Str::random(10) .
                '.' .
                $file->getClientOriginalExtension();

            $imagePath = $file->storeAs(
                'students',
                $filename,
                'public'
            );
        }


        /*
         * Generate Student ID
         */
        $lastStudent = Student::latest('id')->first();

        if (
            $lastStudent &&
            $lastStudent->student_id
        ) {

            $lastNumber = (int) str_replace(
                'STD-',
                '',
                $lastStudent->student_id
            );

            $studentId =
                'STD-' .
                ($lastNumber + 1);

        } else {

            $studentId = 'STD-1001';
        }


        /*
         * Create Student
         */
        $student = Student::create([

            'full_name' => $request->full_name,

            'fathers_name' => $request->fathers_name,

            'mothers_name' => $request->mothers_name,

            'student_id' => $studentId,

            'phone' => $request->phone,

            'section_id' => $request->section_id,

            'class_id' => $request->class_id,

            /*
             * IMPORTANT
             * Group ID সরাসরি students table-এ save হবে।
             */
            'class_group_id' => $request->class_group_id,

            /*
             * পুরোনো field
             */
            'course_name' => $request->course_name,

            'admission_date' =>
                $request->admission_date
                ?? now()->toDateString(),

            'email' => $request->email,

            'image' => $imagePath,

            'shift_id' => $request->shift_id,

            'monthly_fee' => $request->monthly_fee,
        ]);


        /*
         * Return Student
         * classGroup সহ
         */
        return response()->json([

            'status' => true,

            'message' =>
                'Student Created Successfully',

            'student' => $student->load([
                'section',
                'classInfo',
                'classGroup',
                'shift'
            ])

        ], 201);
    }


    /**
     * Display the specified student
     */
    public function show(Student $student)
    {
        return response()->json([

            'status' => true,

            'student' => $student->load([
                'payments',
                'section',
                'classInfo',
                'classGroup',
                'shift'
            ])

        ]);
    }


    /**
     * Edit student
     */
    public function edit(Student $student)
    {
        return response()->json([

            'status' => true,

            'student' => $student->load([
                'section',
                'classInfo',
                'classGroup',
                'shift'
            ])

        ]);
    }


    /**
     * Update student
     */
    public function update(
        Request $request,
        Student $student
    ) {

        $request->validate([

            'full_name' =>
                'required|string|max:255',

            'phone' =>
                'required|string|max:20',

            'section_id' =>
                'required|exists:sections,id',

            'class_id' =>
                'required|exists:clss_m_s,id',

            /*
             * IMPORTANT
             * Update-এর সময়ও Group ID লাগবে।
             */
            'class_group_id' =>
                'required|exists:class_groups,id',

            'course_name' =>
                'nullable|string|max:100',

            'admission_date' =>
                'required|date',

            'email' => [
                'required',
                'email',
                Rule::unique(
                    'students',
                    'email'
                )->ignore($student->id)
            ],

            'image' =>
                'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',

            'shift_id' =>
                'required|exists:shifts,id',

            'monthly_fee' =>
                'nullable|numeric',
        ]);


        /*
         * Existing Image
         */
        $imagePath = $student->image;


        /*
         * Delete old image and upload new image
         */
        if ($request->hasFile('image')) {

            if (
                $student->image &&
                Storage::disk('public')->exists(
                    $student->image
                )
            ) {

                Storage::disk('public')->delete(
                    $student->image
                );
            }


            $file = $request->file('image');

            $filename =
                time() .
                '_' .
                Str::random(10) .
                '.' .
                $file->getClientOriginalExtension();

            $imagePath = $file->storeAs(
                'students',
                $filename,
                'public'
            );
        }


        /*
         * Update Student
         */
        $student->update([

            'full_name' =>
                $request->full_name,

            'phone' =>
                $request->phone,

            'section_id' =>
                $request->section_id,

            'class_id' =>
                $request->class_id,

            /*
             * IMPORTANT
             * Group ID update হবে।
             */
            'class_group_id' =>
                $request->class_group_id,

            'course_name' =>
                $request->course_name,

            'admission_date' =>
                $request->admission_date,

            'email' =>
                $request->email,

            'image' =>
                $imagePath,

            'shift_id' =>
                $request->shift_id,

            'monthly_fee' =>
                $request->monthly_fee
                ?? $student->monthly_fee,
        ]);


        /*
         * Return updated student
         * classGroup সহ
         */
        return response()->json([

            'status' => true,

            'message' =>
                'Student Updated Successfully',

            'student' => $student->load([
                'section',
                'classInfo',
                'classGroup',
                'shift'
            ])

        ]);
    }


    /**
     * Delete student
     */
    public function destroy(Student $student)
    {
        /*
         * Delete Image
         */
        if (
            $student->image &&
            Storage::disk('public')->exists(
                $student->image
            )
        ) {

            Storage::disk('public')->delete(
                $student->image
            );
        }


        /*
         * Delete related payments
         */
        $student->payments()->delete();


        /*
         * Delete student
         */
        $student->delete();


        return response()->json([

            'status' => true,

            'message' =>
                'Student and related payments deleted successfully'

        ]);
    }


    /**
     * Student Payments
     */
    public function studentPayments($id)
    {
        $payments = Payment::with('student')
            ->where('student_id', $id)
            ->latest()
            ->get();

        return response()->json([

            'status' => true,

            'payments' => $payments

        ]);
    }
}
