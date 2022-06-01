@extends('layouts.app')

@section('content')
<div class="col">
<div class="card">
    <div class="card-header">Laptops</div>
    <div class="card-body">
        
        <table class="table table-stripped table-condensed table-bordered datatable">
            <thead>
                <th>No.</th>
                <th>Serial</th>
                <th>Brand</th>
                <th>Category</th>
                <th>Official Id</th>
            </thead>
            <tbody>
                @forelse ($laptops as $laptop)
                    <tr>
                        <td>{{$laptop->id}}</td>
                        <td><a href="#">{{$laptop->serial}}</a></td>
                        <td>{{$laptop->brand}}</td>
                        <td>{{$laptop->category}}</td>
                        <td>{{$laptop->official_id}}</td>
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