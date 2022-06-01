@extends('layouts.app')

@section('content')
<div class="col">
    <div class="card">
        <div class="card-header">Add Consumable Entry</div>
        <div class="card-body">
			<form action="{{route('consumables.updateEntry',$consumable->id )}}" method="POST">
                @csrf
                {{ method_field('PUT') }}
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
                    <span class="bg-success"><strong>Name:</strong>{{ $consumable->name }}</span>
                    <span class="bg-success"><strong>Max:</strong>{{ $consumable->max_quantity }}</span>
                    <span class="bg-success"><strong>Min:</strong>{{ $consumable->min_quantity }}</span>
                    <span class="bg-success"><strong>In Stock:</strong>{{ $consumable->in_stock }}</span>
                </div>
                <div class="form-group">
                    <label for="id">Id</label>
                    <input type="number" class="form-control @error('id') is-invalid @enderror" name="id" value="{{ $consumable->id }}" disabled>
                    @error('id')<span style="color:red"> {{ $errors->first('id')}} </span> @enderror
                </div>

                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ $consumable->name }}" disabled>
                    @error('name')<span style="color:red"> {{ $errors->first('name')}} </span> @enderror
                </div>

                <div class="form-group">
                    <label for="max_quantity">Max</label>
                    <input type="text" class="form-control @error('max_quantity') is-invalid @enderror" name="max_quantity" value="{{ $consumable->max_quantity }}" disabled>
                    @error('max_quantity')<span style="color:red"> {{ $errors->first('max_quantity')}} </span> @enderror
                </div>

                <div class="form-group">
                    <label for="min_quantity">Min</label>
                    <input type="text" class="form-control @error('min_quantity') is-invalid @enderror" name="min_quantity" value="{{ $consumable->min_quantity }}" disabled>
                    @error('max_quantity')<span style="color:red"> {{ $errors->first('max_quantity')}} </span> @enderror
                </div>

                <div class="form-group">
                    <label for="type">Type:</label>
                    <select class="form-control" id="type" name="type">
                        <option value="1">Issue</option>
                        <option value="0">Purchase</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="type">Official:</label>
                    <select class="form-control" id="o_id" name="o_id">
                    @foreach($officials as $official)
                        <option value="{{$official->id}}">{{$official->name}}</option>
                    @endforeach
                    </select>
                </div>  

                <div class="form-group">
                    <label for="quantity">Quantity</label>
                    <input type="number" min="1" max="5" class="form-control @error('quantity') is-invalid @enderror" name="quantity" value="{{ old('quantity') }}" placeholder="Type the number of items">
                    @error('quantity')<span style="color:red"> {{ $errors->first('quantity')}} </span> @enderror
                </div>
                
                <div class="form-group" style="margin-top: 24px;">
                    <input type="submit" class="btn btn-primary" value="Submit">
                </div>

                </form>
 
                    
				</div>
                <div class="card-footer"><a class="btn btn-primary" href="{{route('consumables.index')}}">Show All</a></div>
            </div>
        </div>
    </div>
</div>
@endsection