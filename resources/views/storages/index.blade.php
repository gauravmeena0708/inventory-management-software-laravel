@extends('layouts.app')

@section('content')
<div class="col">
<div class="card">
    <div class="card-header">Storages</div>
    <div class="card-body">
        
        <table class="table table-stripped table-condensed table-bordered datatable">
            <thead>
                <th>No.</th>
                <th>Serial</th>
                <th>Description</th>
                <th>Location</th>
                <th>Type</th>
            </thead>
            <tbody>
                @forelse ($storages as $storage)
                    <tr>
                        <td>{{$storage->id}}</td>
                        <td><a href="#">{{$storage->serial}}</a></td>
                        <td>{{$storage->description}}</td>
                        <td>{{$storage->location}}</td>
                        <td>{{$storage->type}}</td>
                    </tr>
                @empty
                    No items found.
                @endforelse
            </tbody>
        </table>
        
    </div>
    <div class="card-footer"><a class="btn btn-primary" href="#">Create New</a></div>
</div>
</div>
@endsection

@section('jsscript')
<script>
    $(document).ready( function () {
        $('.datatable').DataTable({
            "order":[['1']]
        });
    });
</script>
@endsection