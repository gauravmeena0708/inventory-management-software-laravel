@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row justify-content-center">
    @include('layouts.sidebar') 
        <div class="col">
            <div class="card">
                <div class="card-header bg-danger">{{ $title }}</div>
                <div class="card-body">
                    
                    <table id="table1" class="table table-stripped table-condensed table-bordered">
                        <thead>
                            <th>id</th>
                            <th>Server Sr</th>
                            <th>End of Sale</th>
                            <th>End of Support</th>
                            <th>Contract Expiry</th>
                        </thead>
                        <tbody>
                            @forelse ($servers as $server)
                                <tr>
                                    <td><a href="{{route('servers.show',$server->id)}}">{{$server->id}}</a></td>
                                    <td>{{$server->asset_sr}}</td>
                                    <td>
                                        {{$server->end_of_sale}}
                                        @if($server->end_of_sale < now())
                                                <span class="badge badge-warning">Not Sold Anymore</span>
                                        @endif</a>
                                    </td>
                                    <td>
                                        {{$server->end_of_support}}
                                        @if($server->end_of_support < now())
                                                <span class="badge badge-warning">Out of Support</span>
                                        @endif
                                    </td>
                                    <td>{{$server->contract_expiry}}</td>
                                </tr>
                            @empty
                                No posts found.
                            @endforelse
                        </tbody>
                    </table>
                    
                </div>
                <div class="card-footer"><btn class="btn btn-primary">Create New Asset</btn></div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('jsscript')
<script>
    $(document).ready( function () {
        $('#table1').DataTable();
    } );
</script>
@endsection            
