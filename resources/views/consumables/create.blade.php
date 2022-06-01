@extends('layouts.app')

@section('content')
<div class="card">
    <div class="card-header">New Consumable</div>
    <div class="card-body">

        <form action="{{route('consumables.store')}}" method="POST">
        @csrf
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
                </ul>
            </div>
        @endif

            <div class="form-group">
                <label for="">Name</label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" placeholder="Enter name">
                @error('name')<span style="color:red"> {{ $errors->first('name')}} </span> @enderror
            </div>

            <div class="form-group">
                <label for="in_stock">In Stock</label>
                <input type="number" class="form-control @error('in_stock') is-invalid @enderror" name="in_stock" value="{{ old('in_stock') }}" placeholder="Enter stock value ">
                @error('in_stock')<span style="color:red"> {{ $errors->first('in_stock')}} </span> @enderror
            </div>

            <div class="form-group">
                <label for="min_quantity">min_quantity</label>
                <input type="number" class="form-control @error('min_quantity') is-invalid @enderror" name="min_quantity" value="{{ old('min_quantity') }}" placeholder="Enter min_quantity no ">
                @error('min_quantity')<span style="color:red"> {{ $errors->first('min_quantity')}} </span> @enderror
            </div>

            <div class="form-group">
                <label for="max_quantity">max_quantity</label>
                <input type="max_quantity" class="form-control @error('max_quantity') is-invalid @enderror" name="max_quantity" value="{{ old('max_quantity') }}" placeholder="Enter max_quantity no ">
                @error('max_quantity')<span style="color:red"> {{ $errors->first('max_quantity')}} </span> @enderror
            </div>
            
            
            <div class="form-group" style="margin-top: 24px;">
                <input type="submit" class="btn btn-primary" value="Submit">
            </div>

        </form>

        <span class="text-danger"> Last 5 consumables:
            @forelse ($consumables as $consumable)
                <a href="{{route('consumables.show',$consumable->id)}}">{{$consumable->name}}</a>, 
            @empty
                No items found.
            @endforelse
        </span>
                    
	</div>
    <div class="card-footer"><a class="btn btn-primary" href="{{route('consumables.index')}}">Show All</a></div>
</div>
@endsection