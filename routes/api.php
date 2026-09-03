<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\StafftController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\OtherPaymentController;
use App\Http\Controllers\PdfController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\TeachersAttendanceController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\ClassGroupController;
use App\Http\Controllers\ClssMController;
use App\Http\Controllers\ExaminationController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\InstituteInfoController;
use App\Http\Controllers\SectionController;

/*
|--------------------------------------------------------------------------
| AUTH (PUBLIC)
|--------------------------------------------------------------------------
*/
Route::post('/register', [StafftController::class, 'store']);
Route::post('/login', [StafftController::class, 'login']);
Route::get('/expense-staffs', [ExpenseController::class, 'getStaffs']);

/*
|--------------------------------------------------------------------------
| STUDENTS (PUBLIC)
|--------------------------------------------------------------------------
*/
Route::get('/students', [StudentController::class, 'index']);
Route::post('/students', [StudentController::class, 'store']);
Route::get('/students/{student}', [StudentController::class, 'show']);
Route::put('/students/{student}', [StudentController::class, 'update']);
Route::delete('/students/{student}', [StudentController::class, 'destroy']);

/*
|--------------------------------------------------------------------------
| TEACHERS & TEACHER ATTENDANCE CUSTOM ROUTES (MUST BE ON TOP)
|--------------------------------------------------------------------------
*/
Route::get('/teachers/attendance-summary', [TeachersAttendanceController::class, 'teacherSummaryReport']);
Route::get('/teachers/{id}/yearly-report', [TeachersAttendanceController::class, 'singleTeacherYearlyReport']);
Route::get('/expense-teachers', [ExpenseController::class, 'getTeachers']);

/*
|--------------------------------------------------------------------------
| TEACHERS (PUBLIC/RESOURCE)
|--------------------------------------------------------------------------
*/
Route::apiResource('teachers', TeacherController::class);
use App\Http\Controllers\StaffAttendanceController;
use App\Http\Controllers\SubjectController;

Route::apiResource('staff-attendances', StaffAttendanceController::class);
/*
|--------------------------------------------------------------------------
| TEACHER ATTENDANCE (RESOURCE & CUSTOM ROUTES)
|--------------------------------------------------------------------------
*/
Route::apiResource('teacher-attendances', TeachersAttendanceController::class);
Route::get('/teacher-attendances/report/{teacherId}', [TeachersAttendanceController::class, 'report']);

/*
|--------------------------------------------------------------------------
| PAYMENTS (PUBLIC)
|--------------------------------------------------------------------------
*/
Route::get('/payments', [PaymentController::class, 'index']);
Route::post('/payments', [PaymentController::class, 'store']);
Route::get('/payments/{id}', [PaymentController::class, 'show']);
Route::put('/payments/{id}', [PaymentController::class, 'update']);
Route::delete('/payments/{id}', [PaymentController::class, 'destroy']);
Route::get('/student-payments/{id}', [PaymentController::class, 'studentPayments']);
Route::get('/student-payment-report', [PaymentController::class, 'studentPaymentReport']);

/*
|--------------------------------------------------------------------------
| PDF RECEIPT (PUBLIC OR OPTIONAL PROTECTED)
|--------------------------------------------------------------------------
*/
Route::get('/payments/{id}/receipt', [PdfController::class, 'downloadReceipt']);

/*
|--------------------------------------------------------------------------
| OTHER PUBLIC ROUTES
|--------------------------------------------------------------------------
*/
Route::get('/debug-db', function () {
    return [
        'host' => config('database.connections.mysql.host'),
        'port' => config('database.connections.mysql.port'),
        'database' => config('database.connections.mysql.database'),
        'username' => config('database.connections.mysql.username'),
    ];
});

Route::apiResource('sections', SectionController::class);
Route::apiResource('other-payments', OtherPaymentController::class);
Route::apiResource('results', ResultController::class);
Route::apiResource('shifts', ShiftController::class);
Route::apiResource('examinations', ExaminationController::class);
Route::apiResource('classes', ClssMController::class);
Route::apiResource('holidays', HolidayController::class);
Route::apiResource('subjects', SubjectController::class);
Route::apiResource('class_group', ClassGroupController::class);
Route::get('/class_group_subjects', [ClassGroupController::class, 'subjects']);
Route::apiResource('institute-info', InstituteInfoController::class);
/*
|--------------------------------------------------------------------------
| AUTH PROTECTED ROUTES (SANCTUM)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    /*
    |--------------------------
    | STAFF
    |--------------------------
    */
    Route::get('/staff', [StafftController::class, 'index']);
    Route::get('/staff/{staff}/edit', [StafftController::class, 'edit']);
    Route::put('/staff/{staff}', [StafftController::class, 'update']);
    Route::delete('/staff/{staff}', [StafftController::class, 'destroy']);
    Route::get('/staff/dashboard', [StafftController::class, 'dashboard']);

    /*
    |--------------------------
    | EXPENSES
    |--------------------------
    */
    Route::apiResource('expenses', ExpenseController::class);

    /*
    |--------------------------
    | AUTH
    |--------------------------
    */
    Route::post('/logout', [StafftController::class, 'logout']);

    /*
    |--------------------------
    | DATABASE BACKUP ROUTE
    |--------------------------
    */
});


    Route::post('/run-backup', [BackupController::class, 'takeBackup']);
