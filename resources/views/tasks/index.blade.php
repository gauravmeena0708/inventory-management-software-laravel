@extends('layouts.app')

@section('content')
<div class="col">
<div class="card">
    <div class="card-header">Tasks</div>
    <div class="card-body">
        
        <table class="table table-stripped table-condensed table-bordered datatable">
            <thead>
                <th>id</th>
                <th>Name</th>
                <th>Status</th>
            </thead>
            <tbody>
                @forelse ($tasks as $task)
                    <tr>
                        <td><a href="#">{{$task->id}}</a></td>
                        <td><a href="#">{{$task->name}}</a></td>
                        <td class="@if($task->status == 'completed') bg-success @endif"><a href="#">{{$task->status}}</a></td>
                    </tr>
                @empty
                    No items found.
                @endforelse
            </tbody>
        </table>
        
    </div>
    <div class="card-footer"><a class="btn btn-primary" href="#">Create New</a></div>
</div>
@endsection

@section('jsscript')
<script>
    $(document).ready( function () {
        $('.datatable').DataTable();
    } );
</script>
@endsection