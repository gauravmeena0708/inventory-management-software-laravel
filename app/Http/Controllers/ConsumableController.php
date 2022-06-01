<?php

namespace App\Http\Controllers;

use App\Models\Consumable;
use App\Models\Official;
use App\Models\Entry;
use Illuminate\Http\Request;

class ConsumableController extends Controller
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
        $consumables = Consumable::get();
        return view('consumables.index',['consumables'=>$consumables]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $consumables = consumable::latest()->take(5)->get();
        return view('consumables.create',['consumables'=>$consumables]);
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
            'name'=>'required|min:2',
            'in_stock'=>'required',
            'max_quantity'=>'required',
            'min_quantity'=>'required'
        ]);
        Consumable::create($validated);
        
        //return redirect()->route('categories.index');
        return back()->with('success','Agreement created Successfull');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Consumable  $consumable
     * @return \Illuminate\Http\Response
     */
    public function show(Consumable $consumable)
    {
        return view('consumables.show', ['consumable'=>$consumable]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Consumable  $consumable
     * @return \Illuminate\Http\Response
     */
    public function edit(Consumable $consumable)
    {
        return view('consumables.edit', ['consumable'=>$consumable]);
    }

    public function addEntry(Consumable $consumable)
    {
        $officials = Official::get();
        return view('consumables.addEntry', ['consumable'=>$consumable, 'officials'=>$officials]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Consumable  $consumable
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Consumable $consumable)
    {
        $validated = $request->validate([
            'name'=>'required|min:2',
            'max_quantity'=>'required',
            'min_quantity'=>'required'
        ]);

        $consumable->update($validated);
        return back()->with('success','Consumable updated Successfull');
    }

    public function updateEntry(Request $request, Consumable $consumable)
    {
        $validated = $request->validate([
            'quantity'=>'required|min:1|max:5',
            'type' => 'required|min:0|max:1',
            'o_id' => 'required'
        ]);
        if($validated['type']=="1") {
            $new_entry = [
                'consumable_id'=>$consumable['id'],
                'type' =>1,
                'amount' =>$validated['quantity'],
                'stock' => $consumable['in_stock']-$validated['quantity'],
                'issuer_id' => $validated['o_id']
            ];
            $update_consumable=[
                'in_stock' => $consumable['in_stock']-$validated['quantity']
            ];
            
            
        } else {

            $new_entry = [
                'consumable_id'=>$consumable['id'],
                'type' =>0,
                'amount' =>$validated['quantity'],
                'stock' => $consumable['in_stock']+$validated['quantity'],
                'issuer_id' => $validated['o_id']
            ];
            $update_consumable=[
                'in_stock' => $consumable['in_stock']+$validated['quantity']
            ];
            
        }
        $consumable->update($update_consumable);
        Entry::create($new_entry);
        return back()->with('success','Consumable updated Successfull');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Consumable  $consumable
     * @return \Illuminate\Http\Response
     */
    public function destroy(Consumable $consumable)
    {
        //
    }
}
