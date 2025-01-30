@extends('layouts.app')

@section('content')
    <login :errors="{{ json_encode($errors->all()) }}" :message="{{ json_encode(session('error', '')) }}" :intended="'{{url()->previous()}}'" />
@endsection
