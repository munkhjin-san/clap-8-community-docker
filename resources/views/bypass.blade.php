@extends('layouts.app')
@section('title', '')
@section('content')
<by-pass :target-user='@json($target_user)' :target-board='@json($target_board)' :has-auth='@json(Auth::check())'/>   
@endsection