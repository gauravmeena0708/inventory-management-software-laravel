@extends('layouts.app')
@section('content')
<div class="card mx-auto max-w-4xl"><div class="card-header">Edit developer</div><form method="POST" action="{{ route('developers.update', $developer) }}">@csrf @method('PUT')<div class="card-body">@include('developers._form')</div><div class="card-footer"><button class="btn btn-primary" type="submit">Update developer</button><a class="btn btn-secondary" href="{{ route('developers.show', $developer) }}">Cancel</a></div></form></div>
@endsection
