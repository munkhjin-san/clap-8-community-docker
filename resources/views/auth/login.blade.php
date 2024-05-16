@extends('layouts.app')

@section('content')
    <login :message="{{ json_encode(session('error', '')) }}" :intended="'{{url()->previous()}}'" />
@endsection
