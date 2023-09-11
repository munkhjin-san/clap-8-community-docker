@extends('layouts.app')
@section('title', '')
@section('content')
@if(Auth::check())
<root :auth_user="{{Auth::user()}}" :session="'{{Session::getId()}}'" :remember='{!! json_encode(Auth::user()->app_remember_record) !!}'/>   
@else
<Help/>   
@endif
@endsection