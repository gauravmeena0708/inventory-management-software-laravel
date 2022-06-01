@extends('layouts.app')

@section('content')
<div class="col">
    <div class="card">
        <div class="card-header">Servers</div>
        <div class="card-body">
            <table class="table table-stripped table-condensed table-bordered datatable">   
                <thead>
                    <th>id</th>
                    <th>Asset Serial</th>
                    <th>Location</th>
                    <th>Covered</th>
                    <th>Asset_tag</th>
                    <th>Purchase Date</th>
                    <th>Purchase Cost</th>
                    <th>Description</th>
                    <th>End of sale</th>
                    <th>End of Support</th>
                    <th>Contract Expiry</th>
                </thead>
                <tbody>
                    @forelse ($servers as $server)
                        <tr>
                            <td><a href="#">{{$server->id}}</a></td>
                            <td>{{$server->asset_sr}}</td>
                            <td>{{$server->location}}</td>
                            <td>{{$server->covered}}</td>
                            <td>{{$server->asset_tag}}</td>
                            <td>{{$server->purchase_date}}</td>
                            <td>{{$server->purchase_cost}}</td>
                            <td>{{$server->description}}</td>
                            <td>{{$server->end_of_sale}}
                            @if($server->end_of_sale < now())
                                    <span class="badge badge-warning">Not Sold Anymore</span>
                            @endif
                            </td>
                            <td>{{$server->end_of_support}}
                            @if($server->end_of_support < now())
                                    <span class="badge badge-warning">Out of Support</span>
                            @endif
                            </td>
                            <td>{{$server->contract_expiry}}</td>
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
        $('.datatable').DataTable({
            "autoWidth": false,
            scrollX:        true
        });
    } );
</script>
@endsection