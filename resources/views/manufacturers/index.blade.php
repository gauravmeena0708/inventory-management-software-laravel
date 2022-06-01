@extends('layouts.app')

@section('content')
<div class="col">
<div class="card">
    <div class="card-header">Manufacturers</div>
    <div class="card-body">
        
        <table class="table table-stripped table-condensed table-bordered datatable">
            <thead>
                <th>No.</th>
                <th>Name</th>
            </thead>
            <tbody>
                @forelse ($manufacturers as $manufacturer)
                    <tr>
                        <td>{{$manufacturer->id}}</td>
                        <td><a href="#">{{$manufacturer->name}}</a></td>
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