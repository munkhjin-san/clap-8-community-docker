@extends('layouts.app')
@section('title', 'GLOWD - About account deletion')
@section('content')
<div style="color:var(--primary-color);background-color:var(--background-color);padding: 3em;">
    <h1 style="font-size: 24px;">About account deletion</h1>
    <br>
    <div style="line-height:2; margin-left: 10px;">
        According to Facebook policy, we have to provide the User Data Deletion Callback URL or Data Deletion Instructions URL. 
        <br>
        Follow the steps below to delete your account.
        <div style="margin-left:30px;line-height:2;">
        1. Log in with your user account.
        <br>
        2. After logging in, click or tap the hamburger menu on the top left of the screen.
        <br>
        3. And click or tap your profile to jump to profile page. 
        <br>
        4. After entering profile page click kebab menu on the top right of the screen.
        <br>
        5. Click or tap "Account Settings" from the displayed menu to move to the Account Settings screen.
        <br>
        6. Click or tap "Delete account" on the displayed pop-up screen to delete the user's registration information and complete the account deletion.
        <br>
        </div>
    </div>
</div>
@endsection