@extends('layouts.app')

@section('content')
<div class="col">
    <div class="card">
        <div class="card-header">Switches</div>
        <div class="card-body">
            <table class="table table-stripped table-condensed table-bordered datatable">   
                <thead>
                    <th>id</th>
                    <th>OEM</th>
                    <th>Location</th>
                    <th>Sub-location</th>
                    <th>Partcode</th>
                    <th>Serial</th>
                    <th>Support Date</th>
                </thead>
                <tbody>
                    @forelse ($devices as $switch)
                        <tr>
                            <td><a href="#">{{$switch->id}}</a></td>
                            <td>{{$switch->oem}}</td>
                            <td>{{$switch->location}}</td>
                            <td>{{$switch->sublocation}}</td>
                            <td>{{$switch->partcode}}</td>
                            <td>{{$switch->serial}}</td>
                            <td>{{$switch->support_date}}</td>
                        </tr>
                    @empty
                        No items found.
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer"><btn class="btn btn-primary">Create New Asset</btn></div>
    </div>
</div>
@endsection

@section('jsscript')
<script>
    $(document).ready( function () {
        $('.datatable').DataTable();
    } );
</script>
@endsection