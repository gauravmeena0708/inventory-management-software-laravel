@extends('layouts.app')

@section('content')
<div class="card">
    <div class="card-header">New Agreement</div>
    <div class="card-body">

        <form action="{{route('agreements.store')}}" method="POST">
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
                <label for="">Agency</label>
                <input type="text" class="form-control @error('agency') is-invalid @enderror" name="agency" value="{{ old('agency') }}" placeholder="Enter agency">
                @error('agency')<span style="color:red"> {{ $errors->first('agency')}} </span> @enderror
            </div>

            <div class="form-group">
                <label for="">File</label>
                <input type="text" class="form-control @error('file') is-invalid @enderror" name="file" value="{{ old('file') }}" placeholder="Enter file no ">
                @error('name')<span style="color:red"> {{ $errors->first('name')}} </span> @enderror
            </div>

            <div class="form-group">
                <label for="">efile</label>
                <input type="number" class="form-control @error('efile') is-invalid @enderror" name="efile" value="{{ old('efile') }}" placeholder="Enter efile no ">
                @error('name')<span style="color:red"> {{ $errors->first('name')}} </span> @enderror
            </div>
            <div class="form-group">
                <label for="Type">Type</label>
                <select name="type" class="form-control">
                <option>Service</option>
                <option>Product</option>
                </select>
                @error('type')<span style="color:red"> {{ $errors->first('type')}} </span> @enderror
            </div>

            <div class="form-group">
                <label for="">Annual Cost</label>
                <input type="number" class="form-control @error('annual_cost') is-invalid @enderror" name="annual_cost" value="{{ old('annual_cost') }}" placeholder="Enter Annual Cost ">
                @error('annual_cost')<span style="color:red"> {{ $errors->first('annual_cost')}} </span> @enderror
            </div>

            <div class="form-group">
                <label for="">Frequency</label>
                <input type="number" class="form-control @error('frequency') is-invalid @enderror" name="frequency" value="{{ old('frequency') }}" placeholder="Enter Annual Cost ">
                @error('frequency')<span style="color:red"> {{ $errors->first('frequency')}} </span> @enderror
            </div>

            <div class="form-group">
                <label for="">Expiry</label>
                <input type="date" class="form-control @error('expiry') is-invalid @enderror" name="expiry" value="{{ old('expiry') }}" placeholder="Enter expiry">
                @error('expiry')<span style="color:red"> {{ $errors->first('expiry')}} </span> @enderror
            </div>
            
            <div class="form-group">
                <label for="">Paid Till</label>
                <input type="date" class="form-control @error('paid_till') is-invalid @enderror" name="paid_till" value="{{ old('paid_till') }}" placeholder="Enter expiry">
                @error('paid_till')<span style="color:red"> {{ $errors->first('paid_till')}} </span> @enderror
            </div>
            
            
            <div class="form-group" style="margin-top: 24px;">
                <input type="submit" class="btn btn-primary" value="Submit">
            </div>

        </form>

        <span class="text-danger"> Last 5 agreements:
            @forelse ($agreements as $agreement)
                <a href="{{route('agreements.show',$agreement->id)}}">{{$agreement->name}}</a>, 
            @empty
                No items found.
            @endforelse
        </span>
                    
	</div>
    <div class="card-footer"><a class="btn btn-primary" href="{{route('agreements.index')}}">Show All</a></div>
</div>
@endsection