@extends('layouts.app')

@section('content')
<public-survey-root public-token="{{ $publicToken }}" />
@endsection
