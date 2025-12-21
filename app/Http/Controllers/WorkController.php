<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WorkController extends Controller
{
    public function index(Request $request){
        
        return view('work');
    }

    public function uploadExcel(Request $request) {
        \Log::info("in here");

            \Log::info(print_r($request->all(),true));
        
        if ($request->hasFile('excel_file')) {
            $file = $request->file('excel_file');
            \Log::info("Uploaded file: " . $file->getClientOriginalName());
        } else {
            \Log::warning("No file found in request.");
        }

        return view('work');
    }
}
