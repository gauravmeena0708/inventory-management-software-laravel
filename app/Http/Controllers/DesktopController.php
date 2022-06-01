<?php

namespace App\Http\Controllers;

use App\Models\Desktop;
use Illuminate\Http\Request;

class DesktopController extends Controller
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
        $desktops = Desktop::get();
        return view('desktops.index',['desktops'=>$desktops]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Desktop  $desktop
     * @return \Illuminate\Http\Response
     */
    public function show(Desktop $desktop)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Desktop  $desktop
     * @return \Illuminate\Http\Response
     */
    public function edit(Desktop $desktop)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Desktop  $desktop
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Desktop $desktop)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Desktop  $desktop
     * @return \Illuminate\Http\Response
     */
    public function destroy(Desktop $desktop)
    {
        //
    }
}
