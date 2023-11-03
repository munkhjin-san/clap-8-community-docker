@extends('layouts.app')
@section('content')
@if(Auth::check())
<root :auth_user="{{Auth::user()}}" :mood_val='@json(Auth::user()->today_weather)' :session="'{{Session::getId()}}'" :initial_date="'{{$initialDate}}'"/>   
@endif
@endsection







