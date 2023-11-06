@extends('layouts.app')
@section('content')
@if(Auth::check())
<root :auth_user="{{$user}}" :session="'{{Session::getId()}}'" :initial_date="'{{$initialDate}}'"/>   
@endif
@endsection







