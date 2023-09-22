@extends('layouts.app')
@section('content')
@if(Auth::check())
<root :auth_user="{{Auth::user()}}" :session="'{{Session::getId()}}'"/>   
@endif
@endsection







