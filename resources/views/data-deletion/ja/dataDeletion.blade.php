@extends('layouts.app')
@section('title', 'CLAP - アカウントの削除について')
@section('content')
<div style="color:var(--primary-color);background-color:var(--background-color);;padding: 3em;">
    <h1 style="font-size: 24px;">アカウントの削除について</h1>
    <br>
    <div style="line-height:2; margin-left: 10px;">
        Facebook のポリシーに従って、ユーザー データ削除コールバック URL またはデータ削除手順 URL を提供する必要があります。
        <br>
        アカウントの削除方法は以下の手順です。
        <div style="margin-left:30px;line-height:2;">
        1.利用者のアカウントでログインします。
        <br>
        2.ログイン後、画面左上のハンバーガーメニューをクリックまたはタップします。
        <br>
        3.プロフィールをクリックまたはタップしてプロフィール ページに移動します。
        <br>
        4.プロフィールページに入ったら、画面右上のケバブメニューをクリックします。
        <br>
        5.表示されたメニューから「アカウント設定」をクリックまたはタップし、アカウント設定画面に移動します。        
        <br>
        6.表示されたポップアップ画面で「アカウント削除」をクリックまたはタップすると、ユーザーの登録情報が削除され、アカウントの削除が完了します。        
        <br>
        </div>
    </div>
</div>
@endsection