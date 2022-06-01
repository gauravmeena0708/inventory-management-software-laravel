@extends('layouts.app')

@section('content')
<div class="col">
<div class="card">
    <div class="card-header">Developers</div>
    <div class="card-body">
        
        <table class="table table-stripped table-condensed table-bordered datatable">
            <thead>
                <th>id</th>
                <th>Name</th>
                <th>Reporting Officer</th>
                <th>Category</th>
                <th>Salary</th>
            </thead>
            <tbody>
                @forelse ($developers as $developer)
                    <tr>
                        <td><a href="#">{{$developer->id}}</a></td>
                        <td><a href="#">{{$developer->name}}</a></td>
                        <td><a href="#">{{$developer->reporting->name}}</a></td>
                        <td><a href="#">{{$developer->category->name}}</a></td>
                        <td><a href="#">{{$developer->category->salary}}</a></td>
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