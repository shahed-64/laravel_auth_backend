<?php

namespace App\Http\Controllers;

use App\Models\TeachersAttendance;
use App\Models\Teacher;
use App\Models\Holiday; // আপনার ছুটির মডেলের নাম যদি আলাদা হয়, এখানে ঠিক করে দেবেন
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TeachersAttendanceController extends Controller
{
    /**
     * Display a listing of the resource.
     * তারিখ / মাস / শিক্ষক অনুযায়ী attendance data পাওয়ার জন্য।
     */
    public function index(Request $request)
    {
        $query = TeachersAttendance::query();

        if ($request->has('date')) {
            $query->where('date', $request->date);
        }

        // মাস অনুযায়ী ফিল্টার করার জন্য (যেমন: 2026-08)
        if ($request->has('month')) {
            $query->whereYear('date', Carbon::parse($request->month)->year)
                  ->whereMonth('date', Carbon::parse($request->month)->month);
        }

        if ($request->has('shift_name')) {
            $query->where('shift_name', $request->shift_name);
        }

        $attendances = $query->with(['teacher', 'shift'])->get();

        return response()->json([
            'success' => true,
            'data' => $attendances
        ]);
    }


    /**
     * Store a newly created resource in storage.
     * একসাথে একাধিক teacher-এর attendance save/update।
     */
    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'attendances' => 'required|array',
            'attendances.*.teacher_id' => 'required|exists:teachers,id',
            'attendances.*.shift_name' => 'nullable|string',
            'attendances.*.status' => 'nullable|in:Present,Absent,Late,Leave,Off Day',
            'attendances.*.leave' => 'nullable|boolean',
            'attendances.*.in_time' => 'nullable',
            'attendances.*.out_time' => 'nullable',
            'attendances.*.note' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();

        try {
            $date = $request->date;
            $savedAttendances = [];

            // ১. চেক করা যাক সাবমিট করা ডেটটি 'holidays' টেবিলে আছে কি না
            // (নোট: আপনার টেবিলের ডেট কলামের নাম যদি 'date' এর বদলে অন্য কিছু হয়, যেমন 'holiday_date', তবে সেটি এখানে বদল করে দেবেন)
          $isHoliday = Holiday::where('start_date', '<=', $date)
                    ->where('end_date', '>=', $date)
                    ->exists();

            foreach ($request->attendances as $item) {

                $shiftName = $item['shift_name'] ?? 'General Shift';
                $isLeave = $item['leave'] ?? false;

                /*
                 * স্ট্যাটাস নির্ধারণের লজিক:
                 * ১. যদি ছুটির দিন হয় -> Off Day
                 * ২. যদি লিভ থাকে -> Leave
                 * ৩. যদি কোনো কিছু সিলেক্ট না করা হয় (ফাঁকা থাকে) -> Absent
                 * ৪. অন্যথায় -> ইউজার যা পাঠাবে তাই
                 */
                if ($isHoliday) {
                    $status = 'Off Day';
                    $inTime = null;
                    $outTime = null;
                    $note = 'Holiday / Off Day';
                } elseif ($isLeave) {
                    $status = 'Leave';
                    $inTime = null;
                    $outTime = null;
                    $note = null;
                } elseif (empty($item['status'])) {
                    $status = 'Absent';
                    $inTime = null;
                    $outTime = null;
                    $note = null;
                } else {
                    $status = $item['status'];
                    $inTime = $item['in_time'] ?? null;
                    $outTime = $item['out_time'] ?? null;
                    $note = $item['note'] ?? null;
                }

                /*
                 * Same teacher + shift + date
                 * হলে update হবে, duplicate হবে না।
                 */
                $attendance = TeachersAttendance::updateOrCreate(
                    [
                        'teacher_id' => $item['teacher_id'],
                        'shift_name' => $shiftName,
                        'date'       => $date,
                    ],
                    [
                        'status'   => $status,
                        'in_time'  => $inTime,
                        'out_time' => $outTime,
                        'note'     => $note,
                        'leave'    => $isLeave,
                    ]
                );

                $savedAttendances[] = $attendance;
            }

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'Attendance saved successfully.',
                'data' => $savedAttendances
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status'  => false,
                'message' => 'Failed to save attendance.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $attendance = TeachersAttendance::with('teacher')->find($id);

            if (!$attendance) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Attendance record not found.'
                ], 404);
            }

            return response()->json([
                'status' => true,
                'data' => $attendance
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Error retrieving record.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Update the specified resource in storage.
     * Leave checkbox ON/OFF handle করবে।
     */
    public function update(Request $request, $id)
    {
        $attendance = TeachersAttendance::find($id);

        if (!$attendance) {
            return response()->json([
                'status'  => false,
                'message' => 'Attendance record not found.'
            ], 404);
        }

        $request->validate([
            'status' => 'nullable|in:Present,Absent,Late,Leave,Off Day',
            'leave' => 'nullable|boolean',
            'in_time' => 'nullable',
            'out_time' => 'nullable',
            'note' => 'nullable|string|max:255',
        ]);

        try {
            $isLeave = $request->has('leave')
                ? (bool) $request->leave
                : (bool) $attendance->leave;

            if ($isLeave) {
                $attendance->update([
                    'leave'    => true,
                    'status'   => 'Leave',
                    'in_time'  => null,
                    'out_time' => null,
                    'note'     => null,
                ]);
            } else {
                $attendance->update([
                    'leave' => false,
                    'status' => $request->has('status') ? $request->status : $attendance->status,
                    'in_time' => $request->has('in_time') ? $request->in_time : $attendance->in_time,
                    'out_time' => $request->has('out_time') ? $request->out_time : $attendance->out_time,
                    'note' => $request->has('note') ? $request->note : $attendance->note,
                ]);
            }

            return response()->json([
                'status'  => true,
                'message' => 'Attendance updated successfully.',
                'data' => $attendance->fresh()
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to update attendance.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * সব শিক্ষকের সারা বছরের summary report।
     */
    public function teacherSummaryReport(Request $request)
    {
        $year = $request->input('year', date('Y'));

        try {
            $teachers = Teacher::with('shifts')->get();

            $summary = $teachers->map(function ($teacher) use ($year) {
                $attendances = TeachersAttendance::where('teacher_id', $teacher->id)
                    ->whereYear('date', $year)
                    ->get();

                $shiftNames = $teacher->shifts->pluck('name')->implode(', ');

                return [
                    'id' => $teacher->id,
                    'name' => $teacher->full_name ?? $teacher->name ?? 'N/A',
                    'code' => $teacher->teacher_id ?? $teacher->code ?? 'N/A',
                    'shift' => !empty($shiftNames) ? $shiftNames : 'General',
                    'total_present' => $attendances->where('status', 'Present')->count(),
                    'total_late' => $attendances->where('status', 'Late')->count(),
                    'total_absent' => $attendances->where('status', 'Absent')->count(),
                    'total_leave' => $attendances->where('status', 'Leave')->count(),
                    'total_off_day' => $attendances->where('status', 'Off Day')->count(), // অফ ডে কাউন্ট যোগ করা হলো
                ];
            });

            return response()->json($summary, 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch summary report.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * নির্দিষ্ট একজন শিক্ষকের January থেকে December পর্যন্ত monthly report।
     */
    public function singleTeacherYearlyReport($id, Request $request)
    {
        $year = $request->input('year', date('Y'));

        try {
            $teacher = Teacher::findOrFail($id);
            $monthlyReports = [];

            for ($month = 1; $month <= 12; $month++) {
                $monthName = Carbon::create()->month($month)->format('F');

                $attendances = TeachersAttendance::where('teacher_id', $id)
                    ->whereYear('date', $year)
                    ->whereMonth('date', $month)
                    ->get();

                $monthlyReports[] = [
                    'month_number' => $month,
                    'month_name' => $monthName,
                    'present' => $attendances->where('status', 'Present')->count(),
                    'late' => $attendances->where('status', 'Late')->count(),
                    'absent' => $attendances->where('status', 'Absent')->count(),
                    'leave' => $attendances->where('status', 'Leave')->count(),
                    'off_day' => $attendances->where('status', 'Off Day')->count(), // অফ ডে কাউন্ট যোগ করা হলো
                    'total_days' => $attendances->count()
                ];
            }

            return response()->json([
                'status' => true,
                'teacher' => $teacher,
                'year' => $year,
                'monthly_reports' => $monthlyReports
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch yearly report.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $attendance = TeachersAttendance::find($id);

            if (!$attendance) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Attendance record not found.'
                ], 404);
            }

            $attendance->delete();

            return response()->json([
                'status' => true,
                'message' => 'Attendance record deleted successfully.'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete record.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
