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
                <tr><td>Name</td><td>{{$consumable->name}}</td></tr>
                <tr><td>Max</td><td>{{$consumable->max_quantity}}</td></tr>
                <tr><td>Min</td><td>{{$consumable->min_quantity}}</td></tr>
                <tr><td>in_stock</td><td>{{$consumable->in_stock}}</td></tr>
                </tbody>
            </table>
            <h1>Stock Entries</h1>
            <table class="table table-stripped table-condensed table-bordered datatable">
            <thead>
                <th>id</th>
                <th>Consumable</th>
                <th>In</th>
                <th>Out</th>
                <th>Updated Stock</th>
                <th>Issuer/Consignee</th>
                <th></th>
            </thead>
            <tbody>
                @forelse ($consumable->entries as $entry)
                    <tr>
                        <td><a href="#">{{$entry->id}}</a></td>
                        <td><a href="#">{{$entry->consumable->name}}</a></td>
                        @if ($entry->type)
                            <td>-</td>
                            <td class="bg-danger">{{$entry->amount}}</td>
                        @else
                            <td class="bg-success">{{$entry->amount}}</td>
                            <td>-</td>
                        @endif
                        <td>{{$entry->stock}}</td>
                        <td>{{$entry->issuer->name}}</td>
                        <td></td>
                    </tr>
                @empty
                    No items found.
                @endforelse
            </tbody>
        </table>
        </div>
        <div class="card-footer">
            <a class="btn btn-primary" href="{{route('consumables.show',$consumable->id-1)}}">Previous</a>
            <a class="btn btn-primary" href="{{route('consumables.index')}}">Show All</a>
            <a class="btn btn-primary" href="{{route('consumables.show',$consumable->id+1)}}">Next</a>
        </div>
    </div>
</div>
@endsection