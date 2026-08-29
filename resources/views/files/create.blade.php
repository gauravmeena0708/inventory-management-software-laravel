@extends('layouts.app')
@section('content')
<div class="card mx-auto max-w-4xl"><div class="card-header">Add file record</div><form method="POST" action="{{ route('files.store') }}">@csrf<div class="card-body">@include('files._form')</div><div class="card-footer"><button class="btn btn-primary" type="submit">Save file</button><a class="btn btn-secondary" href="{{ route('files.index') }}">Cancel</a></div></form></div>
@endsection
