<?php

namespace App\Http\Controllers;

use App\Models\OtherPayment;
use Illuminate\Http\Request;

class OtherPaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $otherPayments = OtherPayment::with('student')
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'data' => $otherPayments,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'student_id'     => 'required|exists:students,id',
            'item_name'      => 'required|string|max:255',
            'quantity'       => 'required|integer|min:1',
            'price'          => 'required|numeric|min:0',
            'payment_method' => 'required|string|max:100',
            'payment_date'   => 'required|date',
            'remarks'        => 'nullable|string',
        ]);

        $otherPayment = OtherPayment::create([
            'student_id'     => $request->student_id,
            'item_name'      => $request->item_name,
            'quantity'       => $request->quantity,
            'price'          => $request->price,
            'total_amount'   => $request->quantity * $request->price,
            'payment_method' => $request->payment_method,
            'payment_date'   => $request->payment_date,
            'remarks'        => $request->remarks,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Other Payment Added Successfully.',
            'data'    => $otherPayment,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $otherPayment = OtherPayment::with('student')->findOrFail($id);

        return response()->json([
            'status' => true,
            'data'   => $otherPayment,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $otherPayment = OtherPayment::findOrFail($id);

        $request->validate([
            'student_id'     => 'required|exists:students,id',
            'item_name'      => 'required|string|max:255',
            'quantity'       => 'required|integer|min:1',
            'price'          => 'required|numeric|min:0',
            'payment_method' => 'required|string|max:100',
            'payment_date'   => 'required|date',
            'remarks'        => 'nullable|string',
        ]);

        $otherPayment->update([
            'student_id'     => $request->student_id,
            'item_name'      => $request->item_name,
            'quantity'       => $request->quantity,
            'price'          => $request->price,
            'total_amount'   => $request->quantity * $request->price,
            'payment_method' => $request->payment_method,
            'payment_date'   => $request->payment_date,
            'remarks'        => $request->remarks,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Other Payment Updated Successfully.',
            'data'    => $otherPayment,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $otherPayment = OtherPayment::findOrFail($id);

        $otherPayment->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Other Payment Deleted Successfully.',
        ]);
    }
}
