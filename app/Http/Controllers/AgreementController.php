<?php

namespace App\Http\Controllers;

use App\Models\Agreement;
use Illuminate\Http\Request;
use App\Exports\AgreementsExport;
use Maatwebsite\Excel\Facades\Excel;

class AgreementController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
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
        $agreements = Agreement::get();
        return view('agreements.index',['agreements'=>$agreements]);
    }

    public function expired()
    {
        $agreements = Agreement::where('expiry', '<' , now())->get();
        return view('agreements.index',['agreements'=>$agreements]);
    }

    public function due()
    {
        $agreements = Agreement::where('expiry', '<' , now()->addDays(180))->where('expiry', '>' , now())->get();
        return view('agreements.index',['agreements'=>$agreements]);
    }

    public function export() 
    {
        return Excel::download(new AgreementsExport, 'agreements.xlsx');
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $agreements = Agreement::latest()->take(5)->get();
        return view('agreements.create',['agreements'=>$agreements]);
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
            'agency'=>'required|min:5',
            'file'=>'required|min:5',
            'efile'=>'required|min:5',
            'type'=>'required|in:Service,Product',
            'annual_cost'=>'required',
            'frequency'=>'required',
            'type'=>'required|in:Service,Product',
            'expiry'=>'required|date|before:5 years|after:5 years ago',
            'paid_till' =>'required|date|before:5 years|after:5 years ago'
        ]);
        Agreement::create($validated);
        
        //return redirect()->route('categories.index');
        return back()->with('success','Agreement created Successfull');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Agreement  $agreement
     * @return \Illuminate\Http\Response
     */
    public function show(Agreement $agreement)
    {
		return view('agreements.show',['agreement'=>$agreement]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Agreement  $agreement
     * @return \Illuminate\Http\Response
     */
    public function edit(Agreement $agreement)
    {
        return view('agreements.edit', ['agreement'=>$agreement]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Agreement  $agreement
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Agreement $agreement)
    {
        //$input = $request->all();

        $validated = $request->validate([
            'name'=>'required|min:5',
            'agency'=>'required|min:5',
            'expiry'=>'required|date|before:5 years|after:5 years ago',
            'paid_till' =>'required|date|before:5 years|after:5 years ago'
        ]);

        $agreement->update($validated);
        //return $validated;
        //return redirect()->route('categories.index');
        return back()->with('success','Agreement updated Successfull');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Agreement  $agreement
     * @return \Illuminate\Http\Response
     */
    public function destroy(Agreement $agreement)
    {
        //
    }
}
