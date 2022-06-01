@extends('layouts.app')

@section('content')
<div class="card">
    <div class="card-header">Update Agreement</div>
    <div class="card-body">
        <form action="{{route('agreements.update',$agreement->id )}}" method="POST">
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
                <label for="">Name</label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ $agreement->name }}" placeholder="Enter name">
                @error('name')<span style="color:red"> {{ $errors->first('name')}} </span> @enderror
            </div>

            <div class="form-group">
                <label for="">Agency</label>
                <input type="text" class="form-control @error('agency') is-invalid @enderror" name="agency" value="{{ $agreement->agency }}" placeholder="Enter agency">
                @error('agency')<span style="color:red"> {{ $errors->first('agency')}} </span> @enderror
            </div>

            <div class="form-group">
                <label for="">Expiry</label>
                <input type="date" class="form-control @error('expiry') is-invalid @enderror" name="expiry" value="{{ $agreement->expiry }}" placeholder="Enter expiry">
                @error('expiry')<span style="color:red"> {{ $errors->first('expiry')}} </span> @enderror
            </div>

            <div class="form-group">
                <label for="">Paid Till</label>
                <input type="date" class="form-control @error('paid_till') is-invalid @enderror" name="paid_till" value="{{ $agreement->paid_till }}" placeholder="Enter expiry">
                @error('paid_till')<span style="color:red"> {{ $errors->first('paid_till')}} </span> @enderror
            </div>
            
            <div class="form-group" style="margin-top: 24px;">
                <input type="submit" class="btn btn-primary" value="Submit">
            </div>

        </form>    
    </div>
    <div class="card-footer"><a class="btn btn-primary" href="{{route('agreements.index')}}">Show All</a></div>
</div>
@endsection