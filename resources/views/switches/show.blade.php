@extends('layouts.app')

@section('content')
<div class="col">
    <div class="card">
        <div class="card-header">User</div>
        <div class="card-body">
            <table class="table table-stripped table-condensed table-bordered">
                <thead>
                    <th>Field</th>
                    <th>Value</th>
                </thead>
                <tbody>
                <tr><td>asset_sr</td><td>{{$server->asset_sr}}</td></tr>
                <tr><td>location</td><td>{{$server->location}}</td></tr>
                <tr><td>covered</td><td>{{$server->covered}}</td></tr>
                <tr><td>asset_tag</td><td>{{$server->asset_tag}}</td></tr>
                <tr><td>purchase_date</td><td>{{$server->purchase_date}}</td></tr>
                <tr><td>purchase_cost</td><td>{{$server->purchase_cost}}</td></tr>
                <tr><td>description</td><td>{{$server->description}}</td></tr>
                <tr><td>end_of_sale</td><td>{{$server->end_of_sale}}
                @if($server->end_of_sale < now())
                                    <span class="badge badge-warning">Not sold in market</span>
                            @endif
                </td></tr>
                <tr><td>end_of_support</td><td>{{$server->end_of_support}}
                @if($server->end_of_support < now())
                                    <span class="badge badge-warning">Out of Support</span>
                            @endif
                </td></tr>
                <tr><td>contract_expiry</td><td>{{$server->contract_expiry}}
                @if($server->contract_expiry < now())
                                    <span class="badge badge-warning">Contract Expired</span>
                            @endif
                            </td></tr>
                <tr><td>amc_cost</td><td>{{$server->amc_cost}}</td></tr>
                <tr><td>contract_type</td><td>{{$server->contract_type}}</td></tr>
                <tr><td>contracthash</td><td>{{$server->contracthash}}</td></tr>
                <tr><td>address</td><td>{{$server->address}}</td></tr>
                <tr><td>city</td><td>{{$server->city}}</td></tr>
                <tr><td>state</td><td>{{$server->state}}</td></tr>
                <tr><td>country</td><td>{{$server->country}}</td></tr>
                <tr><td>terms</td><td>{{$server->terms}}</td></tr>
                <tr><td>duplicated</td><td>{{$server->duplicated}}</td></tr>
                <tr><td>timeout</td><td>{{$server->timeout}}</td></tr>
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            <a class="btn btn-primary" href="{{route('servers.show',$server->id-1)}}">Previous</a>
            <a class="btn btn-primary" href="{{route('servers.index')}}">Show All</a>
            <a class="btn btn-primary" href="{{route('servers.show',$server->id+1)}}">Next</a>
        </div>
    </div>
</div>
@endsection