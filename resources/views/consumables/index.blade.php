@extends('layouts.app')

@section('content')
<div class="col">
<div class="card">
    <div class="card-header">Consumables</div>
    <div class="card-body">
        
        <table class="table table-stripped table-condensed table-bordered datatable">
            <thead>
                <th>id</th>
                <th>Name</th>
                <th>Max Quantity</th>
                <th>Min Quantity</th>
                <th>Buttons</th>
                <th>In Stock</th>
                <th>In Stock t2</th>
                <th></th>
            </thead>
            <tbody>
                @forelse ($consumables as $consumable)
                    <tr>
                        <td><a href="{{route('consumables.show',$consumable->id )}}">{{$consumable->id}}</a></td>
                        <td><a href="{{route('consumables.show',$consumable->id )}}">{{$consumable->name}}</a></td>
                        <td>{{$consumable->max_quantity}}</td>
                        <td>{{$consumable->min_quantity}}</td> 
                        <td><a class="btn bg-primary" href="{{route('entries.consumable_all',$consumable->id )}}">A</a> 
                        <a class="btn bg-primary" href="{{route('entries.consumable_purchase',$consumable->id )}}">P</a> 
                        <a class="btn bg-primary" href="{{route('entries.consumable_issue',$consumable->id )}}">I</a>
                        </td>
                        <td>{{$consumable->latestentry->first()->stock}}
                        @if ($consumable->latestentry->first()->stock < $consumable->min_quantity)
                        <span class="badge badge-warning">Buy {{$consumable->max_quantity - $consumable->latestentry->first()->stock}}</span>
                        @endif
                        </td>
                        <td>{{$consumable->in_stock}}</td>
                        <td>
                        <a href="{{route('consumables.edit',$consumable->id )}}"><i class="fas fa-edit" alt="Edit"></i></a>
                            <a href="{{route('consumables.addEntry',$consumable->id )}}"><i class="fas fa-plus" alt="Add"></i> / <i class="fas fa-minus" alt="issue"></i></a>
                        </td>
                    </tr>
                @empty
                    No items found.
                @endforelse
            </tbody>
        </table>
        
    </div>
    <div class="card-footer"><a class="btn btn-primary" href="{{route('consumables.create')}}">Create New</a></div>
</div>
@endsection

@section('jsscript')
<script>
    $(document).ready( function () {
        $('.datatable').DataTable();
    } );
</script>
@endsection