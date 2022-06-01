<?php

namespace App\Http\Controllers;

use App\Models\Entry;
use App\Models\Consumable;
use Illuminate\Http\Request;

class EntryController extends Controller
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
        $entries = Entry::latest()->get();
        return view('entries.index',['entries'=>$entries]);
    }

    public function purchase() 
    {
        $entries = Entry::where('type',0)->latest()->get();
        return view('entries.index',['entries'=>$entries]);
    }

    public function issue() 
    {
        $entries = Entry::where('type',1)->latest()->get();
        return view('entries.index',['entries'=>$entries]);
    }
    
    public function consumable_all(Consumable $consumable) 
    {
        $entries = Entry::where('consumable_id',$consumable->id)->latest()->get();
        return view('entries.index',['entries'=>$entries]);
    }
    
    public function consumable_purchase(Consumable $consumable) 
    {
        $entries = Entry::where('type',0)->where('consumable_id',$consumable->id)->latest()->get();
        return view('entries.index',['entries'=>$entries]);
    }
    
    public function consumable_issue(Consumable $consumable) 
    {
        $entries = Entry::where('type',1)->where('consumable_id',$consumable->id)->latest()->get();
        return view('entries.index',['entries'=>$entries]);
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
     * @param  \App\Models\Entry  $entry
     * @return \Illuminate\Http\Response
     */
    public function show(Entry $entry)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Entry  $entry
     * @return \Illuminate\Http\Response
     */
    public function edit(Entry $entry)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Entry  $entry
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Entry $entry)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Entry  $entry
     * @return \Illuminate\Http\Response
     */
    public function destroy(Entry $entry)
    {
        //
    }
}
