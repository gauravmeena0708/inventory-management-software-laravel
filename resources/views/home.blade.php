@extends('layouts.app')

@section('content')
<div class="card">
    <div class="card-header">{{ __('Dashboard') }}</div>
    <div class="card-body">
        @if (session('status'))
            <div class="alert alert-success" role="alert">
                {{ session('status') }}
            </div>
        @endif
        <h1>This alerts are generated using sample data</h1>
        
        @foreach ($data as $item)
            
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
            You have <a href="{{ $item['link'] }}"><strong>{{$item['data']}}</strong> {{$item['title']}}</a>.
  <button type="button" class="close" data-dismiss="alert" aria-label="Close">
    <span aria-hidden="true">&times;</span>
  </button>
</div>
        @endforeach

        <a class="btn bg-primary" href="{{route('agreements.export')}}">Agreements</a>
        <a class="btn bg-primary" href="{{route('servers.export')}}">Servers</a>
        <a class="btn bg-primary" href="{{route('switches.export')}}">Switches</a>
        
        
    </div>
</div>
@endsection
