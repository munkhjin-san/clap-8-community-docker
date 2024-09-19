スケジュールが{{$type}}されました。<br><br>

以下にて内容をご確認ください。<br><br>

繰り返し設定 : <br>
{{$details[0]['recursion']}}
<br>
タイトル：<br>
{{$details[0]['title']}}
<br>
メモ : <br>
{{$details[0]['content']}}
<br>
@foreach($details as $detail)
{{$detail['start_at']}}<br><br>
<a href="{{url('schedule?id=' . $detail['id'])}}">{{url('schedule?id=' . $detail['id'])}}</a>
<br>
@endforeach
<br>
このメールアドレスは送信専用です。

