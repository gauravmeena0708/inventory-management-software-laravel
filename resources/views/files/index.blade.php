@extends('layouts.app')

@section('content')
<div class="col">
<div class="card">
    <div class="card-header">Files</div>
    <div class="card-body">
        
        <table class="table table-stripped table-condensed table-bordered datatable">
            <thead>
                <th>id</th>
                <th>Name</th>
                <th>eFile</th>
                <th>Subject</th>
                <th>Division</th>
                <th>Edit</th>
            </thead>
            <tbody>
                @forelse ($files as $file)
                    <tr>
                        <td><a href="{{route('files.show',$file->id)}}">{{$file->id}}</a></td>
                        <td><a href="{{route('files.show',$file->id)}}">{{$file->name}}</a></td>
                        <td><a href="#">{{$file->efile}}</a></td>
                        <td><a href="#">{{$file->subject}}</a></td>
                        <td><a href="#">{{$file->division}}</a></td>
                        <td><a href="{{route('files.edit',$file->id)}}"><i class="fas fa-edit" alt="Edit"></i></a></td>
                    
                    </tr>
                @empty
                    No items found.
                @endforelse
            </tbody>
        </table>
        
    </div>
    <div class="card-footer"><a class="btn btn-primary" href="{{route('files.create')}}">Create New</a></div>
</div>
@endsection

@section('jsscript')
<script>
    $(document).ready( function () {
        $('.datatable').DataTable();
    } );
</script>
@endsection