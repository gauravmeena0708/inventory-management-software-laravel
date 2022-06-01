@extends('layouts.app')

@section('content')
<div class="col">
<div class="card">
    <div class="card-header">Desktops</div>
    <div class="card-body">
        
        <table class="table table-stripped table-condensed table-bordered datatable">
            <thead>
                <th>No.</th>
                <th>Serial</th>
                <th>Brand</th>
                <th>Category</th>
                <th>Location Id</th>
            </thead>
            <tbody>
                @forelse ($desktops as $desktop)
                    <tr>
                        <td>{{$desktop->id}}</td>
                        <td><a href="#">{{$desktop->serial}}</a></td>
                        <td>{{$desktop->brand}}</td>
                        <td>{{$desktop->category}}</td>
                        <td>{{$desktop->location_id}}</td>
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