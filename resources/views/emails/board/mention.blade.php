チャット内でメンションされました。<br><br>

@if($blocked)
メッセージに機密性の高い情報が含まれているかもしれません。<br>
MISOにアクセスし内容を確認してください。 <br><br>
@else
メッセージ内容：<br>
<p>{!! nl2br($content) !!}</p>
@endif
<br><br>
以下のURLにて内容をご確認ください。<br>
<a href="{!! url('board/' . $board_id . '?m=' . $message_id) !!}">{!! url('board/' . $board_id . '?m=' . $message_id) !!}</a>
<br><br>
ID: {{$message_id}}
