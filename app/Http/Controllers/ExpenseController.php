<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExpenseController extends Controller
{
    /**
     * Display all expenses
     */
    public function index()
    {
        $expenses = Expense::latest()->get();

        return response()->json([
            'status'   => true,
            'expenses' => $expenses
        ]);
    }

    /**
     * Store a newly created expense
     */
    public function store(Request $request)
    {
        // ১. payment_date কে nullable করা হয়েছে
        $request->validate([
            'expense_type'   => 'required|string',
            'employee_name'  => 'nullable|string|max:255',
            'salary_amount'  => 'required|numeric',
            'paid_amount'    => 'required|numeric',
            'payment_method' => 'required|string',
            'payment_month'  => 'required',
            'payment_date'   => 'nullable|date',
        ]);

        $paymentMonth = $request->payment_month
            ? (strlen($request->payment_month) == 7 ? $request->payment_month . '-01' : $request->payment_month)
            : null;
        $dueAmount = $request->salary_amount - $request->paid_amount;
$user = Auth::user() ?? $request->user('sanctum') ?? auth('sanctum')->user();
$createdByName = $user ? $user->name : 'System Admin';
        // ২. payment_date না আসলে অটোমেটিক আজকের তারিখ (Current Date) বসে যাবে
        $expense = Expense::create([
            'expense_type'   => $request->expense_type,
            'employee_name'  => $request->employee_name,
            'salary_amount'  => $request->salary_amount,
            'paid_amount'    => $request->paid_amount,
            'due_amount'     => $dueAmount < 0 ? 0 : $dueAmount,
            'payment_month'  => $paymentMonth,
            'payment_method' => $request->payment_method,
            'payment_date'   => $request->payment_date ?? now()->toDateString(),
            'created_by'     => $createdByName,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Expense added successfully.',
            'expense' => $expense
        ], 201);
    }

    /**
     * Display a single expense
     */
    public function show($id)
    {
        $expense = Expense::find($id);

        if (!$expense) {
            return response()->json([
                'status'  => false,
                'message' => 'Expense not found.'
            ], 404);
        }

        return response()->json([
            'status'  => true,
            'expense' => $expense
        ]);
    }

    /**
     * Update an expense
     */
    public function update(Request $request, $id)
    {
        $expense = Expense::find($id);

        if (!$expense) {
            return response()->json([
                'status'  => false,
                'message' => 'Expense not found.'
            ], 404);
        }

        $request->validate([
            'expense_type'   => 'required|string',
            'employee_name'  => 'nullable|string|max:255',
            'salary_amount'  => 'required|numeric',
            'paid_amount'    => 'required|numeric',
            'payment_method' => 'required|string',
            'payment_month'  => 'nullable',
            'payment_date'   => 'nullable|date',
        ]);

        $dueAmount = $request->salary_amount - $request->paid_amount;

        // payment_month ফরম্যাটিং এর সেফটি চেক
            $paymentMonth = $request->payment_month
        ? (strlen($request->payment_month) == 7 ? $request->payment_month . '-01' : $request->payment_month)
        : $expense->payment_month;

        $expense->update([
            'expense_type'   => $request->expense_type,
            'employee_name'  => $request->employee_name,
            'salary_amount'  => $request->salary_amount,
            'paid_amount'    => $request->paid_amount,
            'due_amount'     => $dueAmount < 0 ? 0 : $dueAmount,
            'payment_month'  => $paymentMonth,
            'payment_method' => $request->payment_method,
            'payment_date'   => $request->payment_date ?? $expense->payment_date ?? now()->toDateString(),
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Expense updated successfully.',
            'expense' => $expense
        ]);
    }

    /**
     * Get teachers list for expense salary auto-fill.
     */
        public function getteachers()
        {
            try {
                // সরাসরি টিচার টেবিল থেকে সব ডাটা নিয়ে নিচ্ছি
                $teachers = \App\Models\Teacher::all();

                return response()->json([
                    'status'   => true,
                    'teachers' => $teachers
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'status'   => false,
                    'message'  => $e->getMessage()
                ], 500);
            }
        }
           /**
     * Get Staff list for expense salary auto-fill.
     */
       public function getStaffs()
        {
            // রোল বা পজিশন 'Manager' না হওয়া স্টাফদের আনবে
            $staffs = \App\Models\Staff::where('role', '!=', 'Manager') // অথবা position != 'Manager'
                        ->select('id', 'user_name', 'salary')
                        ->get();
            return response()->json(['status' => true, 'staffs' => $staffs]);
        }
    /**
     * Delete an expense
     */
    public function destroy($id)
    {
        $expense = Expense::find($id);

        if (!$expense) {
            return response()->json([
                'status'  => false,
                'message' => 'Expense not found.'
            ], 404);
        }

        $expense->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Expense deleted successfully.'
        ]);
    }
}
