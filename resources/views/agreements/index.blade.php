@extends('layouts.app')

@section('content')
<div class="card">
    <div class="card-header">Agreements</div>
    <div class="card-body">				
        <table class="table table-stripped table-condensed table-bordered datatable">
            <thead>
                <th>id</th>
                <th>Name</th>
                <th>Agency</th>
                <th>File</th>
                <th>Expiry</th>
                <th>Paid Till</th>
                <th>Edit</th>
            </thead>
            <tbody>
                @forelse ($agreements as $agreement)
                    <tr>
                        <td><a href="{{route('agreements.show',$agreement->id)}}">{{$agreement->id}}</a></td>
                        <td><a href="{{route('agreements.show',$agreement->id)}}">{{$agreement->name}}</a></td>
                        <td>{{$agreement->agency}}</td>
                        <td><a href="{{route('files.show',$agreement->file_id)}}">{{$agreement->file->name}}</a></td>
                        <td>{{$agreement->expiry}}
                            @if($agreement->expiry < now())
                                <span class="badge badge-warning">Due</span>
                            @endif
                        </td>
                        <td>{{$agreement->paid_till}}
                            @if($agreement->paid_till < now())
                                <span class="badge badge-warning">Due</span>
                            @endif
                        </td>
                        <td><a href="{{route('agreements.edit',$agreement->id)}}"><i class="fas fa-edit" alt="Edit"></i></a></td>
                    </tr>
                @empty
                    No items found.
                @endforelse
            </tbody>
        </table>	
    </div>
    <div class="card-footer"><a class="btn btn-primary" href="{{route('agreements.create')}}">Create New</a></div>
</div>
@endsection

@section('jsscript')
<script>
    $(document).ready( function () {
        $('.datatable').DataTable();
    } );
</script>
@endsection