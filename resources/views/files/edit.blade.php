@extends('layouts.app')
@section('content')
<div class="card mx-auto max-w-4xl"><div class="card-header">Edit file record</div><form method="POST" action="{{ route('files.update', $file) }}">@csrf @method('PUT')<div class="card-body">@include('files._form')</div><div class="card-footer"><button class="btn btn-primary" type="submit">Update file</button><a class="btn btn-secondary" href="{{ route('files.show', $file) }}">Cancel</a></div></form></div>
@endsection
