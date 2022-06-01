@extends('layouts.app')

@section('content')
<div class="card">
    <div class="card-header">New File</div>
    <div class="card-body">

        <form action="{{route('files.store')}}" method="POST">
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
                <label for="">efile</label>
                <input type="number" class="form-control @error('efile') is-invalid @enderror" name="efile" value="{{ old('efile') }}" placeholder="Enter efile no ">
                @error('name')<span style="color:red"> {{ $errors->first('name')}} </span> @enderror
            </div>
            
            <div class="form-group">
                <label for="">Physical file name</label>
                <input type="text" class="form-control @error('physical') is-invalid @enderror" name="physical" value="{{ old('physical') }}" placeholder="Enter physical file name">
                @error('physical')<span style="color:red"> {{ $errors->first('physical')}} </span> @enderror
            </div>

            <div class="form-group">
                <label for="">PNumber</label>
                <input type="number" class="form-control @error('pnumber') is-invalid @enderror" name="pnumber" value="{{ old('pnumber') }}" placeholder="Enter Physical no ">
                @error('pnumber')<span style="color:red"> {{ $errors->first('pnumber')}} </span> @enderror
            </div>
            
            <div class="form-group">
                <label for="subject">Subject</label>
                <input type="text" class="form-control @error('subject') is-invalid @enderror" name="subject" value="{{ old('subject') }}" placeholder="Enter file no ">
                @error('subject')<span style="color:red"> {{ $errors->first('subject')}} </span> @enderror
            </div>

            <div class="form-group">
                <label for="division">Division</label>
                <select name="division" class="form-control">
                    <option value="NDC">NDC</option>
                    <option value="SW">Software</option>
                    <option value="HW">Hardware</option>
                </select>
                @error('division')<span style="color:red"> {{ $errors->first('division')}} </span> @enderror
            </div>

            
            
            
            <div class="form-group" style="margin-top: 24px;">
                <input type="submit" class="btn btn-primary" value="Submit">
            </div>

        </form>

        <span class="text-danger"> Last 5 Files:
            @forelse ($files as $file)
                <a href="{{route('files.show',$file->id)}}">{{$file->name}}</a>, 
            @empty
                No items found.
            @endforelse
        </span>
                    
	</div>
    <div class="card-footer"><a class="btn btn-primary" href="{{route('files.index')}}">Show All</a></div>
</div>
@endsection