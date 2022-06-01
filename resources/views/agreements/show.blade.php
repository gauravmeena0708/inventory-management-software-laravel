@extends('layouts.app')

@section('content')
<div class="card">
    <div class="card-header">Agreement</div>
    <div class="card-body">
        <table class="table table-stripped table-condensed table-bordered">
            <thead>
                <th>Field</th>
                <th>Value</th>
            </thead>
            <tbody>
            <tr><td>id</td><td>{{$agreement->id}}</td></tr>
            <tr><td>Name</td><td>{{$agreement->name}}</td></tr>
            <tr><td>Agency</td><td>{{$agreement->agency}}</td></tr>
            <tr><td>File</td><td>{{$agreement->file}}</td></tr>
            <tr><td>E-file</td><td>{{$agreement->efile}}</td></tr>
            <tr><td>Type</td><td>{{$agreement->type}}</td></tr>
            <tr><td>Expiry</td><td>{{$agreement->expiry}}
            @if($agreement->expiry < now())
                                <span class="badge badge-warning">Due</span>
                        @endif
            </td></tr>      
            <tr><td>Annual Cost</td><td>{{$agreement->annual_cost}}</td></tr>       
            <tr><td>Frequency</td><td>{{$agreement->frequency}}</td></tr>     
            <tr><td>Paid Till</td><td>{{$agreement->paid_till}}
            @if($agreement->paid_till < now())
                                <span class="badge badge-warning">Due</span>
                        @endif
            </td></tr>       
            <tr><td>Remarks</td><td>{{$agreement->remarks}}</td></tr>
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        <a class="btn btn-primary" href="{{route('agreements.show',$agreement->id-1)}}">Previous</a>
        <a class="btn btn-primary" href="{{route('agreements.index')}}">Show All</a>
        <a class="btn btn-primary" href="{{route('agreements.show',$agreement->id+1)}}">Next</a>
    </div>
</div>
@endsection