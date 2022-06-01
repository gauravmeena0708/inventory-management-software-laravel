<?php

namespace App\Http\Controllers;

use App\Models\File;
use Illuminate\Http\Request;

class FileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $files = File::get();
        return view('files.index',['files'=>$files]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $files = File::latest()->take(5)->get();
        return view('files.create',['files'=>$files]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $input = $request->all();

        $validated = $request->validate([
            'name'=>'required|min:5',
            'efile'=>'required|min:5',
            'physical'=>'required|min:5',
            'efile'=>'required|min:5',
            'division'=>'required|in:NDC,SW,HW',
            'subject'=>'required'
        ]);
        File::create($validated);
        
        //return redirect()->route('categories.index');
        return back()->with('success','Agreement created Successfull');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\File  $file
     * @return \Illuminate\Http\Response
     */
    public function show(File $file)
    {
        return view('files.show',['file'=>$file]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\File  $file
     * @return \Illuminate\Http\Response
     */
    public function edit(File $file)
    {
        return view('files.edit', ['file'=>$file]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\File  $file
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, File $file)
    {
        $validated = $request->validate([
            'name'=>'required|min:5',
            'efile'=>'required|min:5',
            'physical'=>'',
            'pfile' =>'',
            'subject'=>'required|min:5'
        ]);

        $file->update($validated);
        //return $validated;
        //return redirect()->route('files.index');
        return back()->with('success','Agreement updated Successfull');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\File  $file
     * @return \Illuminate\Http\Response
     */
    public function destroy(File $file)
    {
        //
    }
}
