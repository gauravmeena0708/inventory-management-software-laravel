@extends('layouts.app')

@section('content')
<div class="col">
<div class="card">
    <div class="card-header">Payments</div>
    <div class="card-body">
        
        <table class="table table-stripped table-condensed table-bordered datatable">
            <thead>
                <th>id</th>
                <th>Name</th>
                <th>File</th>
                <th>Agreement</th>
                <th>Amount</th>
                <th>Due Date</th>
            </thead>
            <tbody>
                @forelse ($payments as $payment)
                    <tr class="@if($payment->pending == 0) bg-success @endif">
                        <td><a href="{{route('payments.show',$payment->id)}}">{{$payment->id}}</a></td>
                        <td><a href="{{route('payments.show',$payment->id)}}">{{$payment->name}}</a></td>
                        <td><a href="{{route('files.show',$payment->agreement->file->id)}}">{{$payment->agreement->file->id}}</td>
                        <td><a href="{{route('agreements.show',$payment->agreement->id)}}">{{$payment->agreement->name}}</a></td>
                        <td>{{$payment->amount}}</td>
                        <td>{{$payment->due_date}}</td>
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
        $('.datatable').DataTable();
    } );
</script>
@endsection