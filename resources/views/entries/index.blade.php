@extends('layouts.app')

@section('content')
<div class="col">
<div class="card">
    <div class="card-header">Stock Register Entries</div>
    <div class="card-body">
        
        <table class="table table-stripped table-condensed table-bordered datatable">
            <thead>
                <th>Created</th>
                <th>Consumable</th>
                <th>In</th>
                <th>Out</th>
                <th>Updated Stock</th>
                <th>Issuer/Consignee</th>
            </thead>
            <tbody>
                @forelse ($entries as $entry)
                    <tr>
                        <td>{{$entry->created_at}}</td>
                        <td><a href="{{route('entries.consumable_all',$entry->consumable_id)}}">{{$entry->consumable->name}}</a></td>
                        @if ($entry->type)
                            <td>-</td>
                            <td class="bg-danger">{{$entry->amount}}</td>
                        @else
                            <td class="bg-success">{{$entry->amount}}</td>
                            <td>-</td>
                        @endif
                        <td>{{$entry->stock}}</td>
                        <td>{{$entry->issuer->name}}</td>
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
        $('.datatable').DataTable({
            "order":[['1']]
        });
    });
</script>
@endsection