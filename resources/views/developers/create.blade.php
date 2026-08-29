@extends('layouts.app')
@section('content')
<div class="card mx-auto max-w-4xl"><div class="card-header">Add developer</div><form method="POST" action="{{ route('developers.store') }}">@csrf<div class="card-body">@include('developers._form')</div><div class="card-footer"><button class="btn btn-primary" type="submit">Save developer</button><a class="btn btn-secondary" href="{{ route('developers.index') }}">Cancel</a></div></form></div>
@endsection
