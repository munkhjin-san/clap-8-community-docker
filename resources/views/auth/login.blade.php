@extends('layouts.app')

@section('content')
    <login :intended="'{{url()->previous()}}'" />
@endsection
