<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use Illuminate\Http\Request;

class HolidayController extends Controller
{
    public function index(Request $request)
    {
        $query = Holiday::query();

        if ($request->has('year')) {
            $query->whereYear('start_date', $request->year);
        }

        $holidays = $query->orderBy('start_date', 'asc')->get();

        return response()->json([
            'success' => true,
            'data' => $holidays
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'description' => 'nullable|string'
        ]);

        $holiday = Holiday::create([
            'title' => $request->title,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'description' => $request->description,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Holiday added successfully!',
            'data' => $holiday
        ], 201);
    }

    public function destroy(Holiday $holiday)
    {
        $holiday->delete();

        return response()->json([
            'success' => true,
            'message' => 'Holiday deleted successfully!'
        ]);
    }
}
