<?php
namespace App\Http\Controllers;

use App\Models\Section;
use Illuminate\Http\Request;

class SectionController extends Controller
{
    // সকল সেকশনের তালিকা দেখার জন্য (Vue.js এর জন্য JSON রেসপন্স)
    public function index()
    {
        $sections = Section::withCount('students')->orderBy('id', 'desc')->get();

        return response()->json([
            'status' => true,
            'sections' => $sections
        ]);
    }

    // নতুন সেকশন সংরক্ষণের জন্য
    public function store(Request $request)
    {
        $request->validate([
            'section_name' => 'required|string|max:255|unique:sections,section_name',
        ]);

        $section = Section::create([
            'section_name' => $request->section_name
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Section Created Successfully',
            'section' => $section
        ], 201);
    }

    // নির্দিষ্ট একটি সেকশন দেখার জন্য
    public function show(Section $section)
    {
        return response()->json([
            'status' => true,
            'section' => $section->load('students')
        ]);
    }

    // সেকশন আপডেট করার জন্য
    public function update(Request $request, Section $section)
    {
        $request->validate([
            'section_name' => 'required|string|max:255|unique:sections,section_name,' . $section->id,
        ]);

        $section->update([
            'section_name' => $request->section_name
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Section Updated Successfully',
            'section' => $section
        ]);
    }

    // সেকশন ডিলেট করার জন্য
    public function destroy(Section $section)
    {
        // চাইলে চেক করতে পারেন এই সেকশনে কোনো স্টুডেন্ট আছে কি না
        $section->delete();

        return response()->json([
            'status' => true,
            'message' => 'Section Deleted Successfully'
        ]);
    }
}
