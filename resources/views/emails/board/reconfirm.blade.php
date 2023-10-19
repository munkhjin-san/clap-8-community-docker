再確認依頼が届きました。

@if($blocked)
メッセージに機密性の高い情報が含まれているかもしれません。
CLAPにアクセスし内容を確認してください。 
@else
メッセージ内容：
{{$content}}
@endif
以下のURLにて内容をご確認ください。
{!! url('board/' . $board_id . '?m=' . $message_id) !!}
ID: {{$message_id}}
