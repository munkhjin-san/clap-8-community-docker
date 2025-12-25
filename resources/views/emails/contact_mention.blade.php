コンタクトコメントでメンションされました。<br><br>

@if($blocked)
メッセージに機密性の高い情報が含まれているかもしれません。<br>
GLOWDにアクセスし内容を確認してください。<br><br>
@else
メッセージ内容：<br>
<p>{!! nl2br(e($content)) !!}</p>
@endif

<br><br>
以下のURLにて内容をご確認ください。<br>
<a href="{{ $url }}">{{ $url }}</a>
