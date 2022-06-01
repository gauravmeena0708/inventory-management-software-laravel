@extends('layouts.app')

@section('content')
<div class="col">
<div class="card">
    <div class="card-header">Locations</div>
    <div class="card-body">
        
        <table class="table table-stripped table-condensed table-bordered datatable">
            <thead>
                <th>No.</th>
                <th>Seat</th>
                <th>Floor</th>
                <th>PIN</th>
                <th>Point</th>
                <th>Officials</th>
            </thead>
            <tbody>
                @forelse ($locations as $location)
                    <tr>
                        <td>{{$location->id}}</td>
                        <td>{{$location->seat}}</td>
                        <td>{{$location->floor}}</td>
                        <td>{{$location->pin}}</td>
                        <td>{{$location->point}}</td>
                        @if($location->officials)
                        @forelse ($location->officials as $official)
                            <td><a href="#">{{$official->name}}</a></td>
                            @empty
                            <td> </td>
                        @endforelse
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
        $('.datatable').DataTable({
            "order":[['1']]
        });
    });
</script>
@endsection