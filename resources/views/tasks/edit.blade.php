@extends('layouts.app')
@section('content')
<div class="card mx-auto max-w-4xl"><div class="card-header">Edit task</div><form method="POST" action="{{ route('tasks.update', $task) }}">@csrf @method('PUT')<div class="card-body">@include('tasks._form')</div><div class="card-footer"><button class="btn btn-primary" type="submit">Update task</button><a class="btn btn-secondary" href="{{ route('tasks.show', $task) }}">Cancel</a></div></form></div>
@endsection
