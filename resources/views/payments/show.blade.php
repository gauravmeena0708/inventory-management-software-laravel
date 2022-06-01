@extends('layouts.app')

@section('content')
<div class="card">
    <div class="card-header">Payment</div>
    <div class="card-body">
        <table class="table table-stripped table-condensed table-bordered">
            <thead>
                <th>Field</th>
                <th>Value</th>
            </thead>
            <tbody>
            <tr><td>id</td><td>{{$payment->id}}</td></tr>
            <tr><td>Name</td><td>{{$payment->name}}</td></tr>
            <tr><td>Due Date</td><td>{{$payment->due_date}}</td></tr>
            <tr><td>Amount</td><td>{{$payment->amount}}</td></tr>   
            <tr><td>Agreement ID</td><td>{{$payment->agreement_id}}</td></tr>     
            <tr><td>Remarks</td><td>{{$payment->remarks}}</td></tr>
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        <a class="btn btn-primary" href="{{route('payments.show',$payment->id-1)}}">Previous</a>
        <a class="btn btn-primary" href="{{route('payments.index')}}">Show All</a>
        <a class="btn btn-primary" href="{{route('payments.show',$payment->id+1)}}">Next</a>
    </div>
</div>
@endsection