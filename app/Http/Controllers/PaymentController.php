<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Agreement;
use Illuminate\Http\Request;

class PaymentController extends Controller
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
        $payments = Payment::get();
        return view('payments.index',['payments'=>$payments]);
    }

    public function due()
    {
        $payments = Payment::where('due_date','<',now())
                    -> where('pending',1)
                    ->get();
        return view('payments.index',['payments'=>$payments]);
    }

    public function completed()
    {
        $payments = Payment::where('pending','<',1)->get();
        return view('payments.index',['payments'=>$payments]);
    }

    public function generate()
    {   $counter =1;
        $agreements = Agreement::get();
        $payments = Payment::count();
        if ($payments) {
            return "Already Generated";
        }
        $update_array = array();
        foreach($agreements as $agreement){
            if ($agreement['frequency']>0) {
                $payment_numbers= 12/$agreement['frequency'];
                foreach(range(1, $payment_numbers) as $i) {
                    $push_array=array(
                            "id" => $counter,
                            "name" => $agreement['name']."_".$i,
                            "file_id" => $agreement['efile'],
                            "agreement_id" => $agreement['id'],
                            "due_date" => "2020-".$agreement['frequency']*$i."-01",
                            "remark" => NULL,
                            "pending" => 1
                    );
                    array_push($update_array,$push_array);
                    $counter++;
                }
            }
        }
        
        Payment::insert($update_array);
        return $update_array;
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
     * @param  \App\Models\Payment  $payment
     * @return \Illuminate\Http\Response
     */
    public function show(Payment $payment)
    {
        return view('payments.show',['payment'=>$payment]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Payment  $payment
     * @return \Illuminate\Http\Response
     */
    public function edit(Payment $payment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Payment  $payment
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Payment $payment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Payment  $payment
     * @return \Illuminate\Http\Response
     */
    public function destroy(Payment $payment)
    {
        //
    }
}
