@extends('benchmark::layouts.master')

@section('content')
    <h1>Hello World</h1>

    <p>Module: {!! config('benchmark.name') !!}</p>
@endsection
