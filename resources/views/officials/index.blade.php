@extends('layouts.app')

@section('content')
<div class="col">
<div class="card">
    <div class="card-header">Officials</div>
    <div class="card-body">
        <table class="table table-stripped table-condensed table-bordered datatable">
            <thead>
                <th>No.</th>
                <th>Name</th>
                <th>Location</th>
            </thead>
            <tbody>
                @forelse ($officials as $official)
                    <tr>
                        <td>{{$official->id}}</td>
                        <td><a href="#">{{$official->name}}</a></td>
                        @if($official->location)
                            <td><a href="#">{{$official->location->seat}}</a></td>
                        @else
                            <td></td>
                        @endif
                        
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
        $('.datatable').DataTable();
    });
</script>
@endsection