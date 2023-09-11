@extends('layouts.app')

@section('content')
    <login-component :intended="'{{url()->previous()}}'" />
@endsection
