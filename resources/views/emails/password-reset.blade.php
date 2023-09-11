@extends('emails.mail-layout')

@section('content')
    <h2>Нууц үг сэргээх хүсэлт</h2>

    <p>Сайн байна уу {{$user->name}},</p>
    <p>Нууц үг сэргээх хүсэлт явуулсан байна.</p>
    <a href="{{$actionUrl}}" style="font-size: 16px;" align="left" alt="Reset Password">
        Нууц үг сэргээх
    </a>
    <p style="margin-top: 16px;">Хэрвээ хүсэлт явуулаагүй бол энэ мэйлийг тоохгүй орхино уу.</p>
@endsection