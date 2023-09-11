<!DOCTYPE html>
<html lang="ja">
<body>
<pre>{{$body}}

@if($param02 === 'comment')
@if($param03)
コメントに機密性の高い情報が含まれているかもしれません。
ГЛОУДにアクセスし内容を確認してください。
@else
コメント内容：<br>{{$param04}}
@endif
@endif
以下のURLにて内容をご確認ください。
<a href="{{$url}}">{{$url}}</a>
-----------------------------<br>
このメールアドレスは送信専用です。

ID: {{$param01}}
</pre>
</body>
</html>