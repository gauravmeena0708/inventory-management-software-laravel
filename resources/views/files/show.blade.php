@extends('layouts.app')

@section('content')
<div class="card">
    <div class="card-header">File</div>
    <div class="card-body">
        <table class="table table-stripped table-condensed table-bordered">
            <thead>
                <th>Field</th>
                <th>Value</th>
            </thead>
            <tbody>
            <tr><td>id</td><td>{{$file->id}}</td></tr>
            <tr><td>Name</td><td>{{$file->name}}</td></tr>
            <tr><td>E-file</td><td>{{$file->efile}}</td></tr>
            <tr><td>Physical</td><td>{{$file->physical}}</td></tr>
            <tr><td>pnumber</td><td>{{$file->pnumber}}</tr>           
            <tr><td>Subject</td><td>{{$file->Subject}}</td></tr>
            <tr><td>Division</td><td>{{$file->division}}</td></tr>
            <tr><td>Opened</td><td>{{$file->Opened}}</td></tr>
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        <a class="btn btn-primary" href="{{route('files.show',$file->id-1)}}">Previous</a>
        <a class="btn btn-primary" href="{{route('files.index')}}">Show All</a>
        <a class="btn btn-primary" href="{{route('files.show',$file->id+1)}}">Next</a>
    </div>
</div>
@endsection