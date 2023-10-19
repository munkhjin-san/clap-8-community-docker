スケジュールが{{$type}}されました。

以下にて内容をご確認ください。

繰り返し設定 : 
{{$details[0]['recursion']}}

内容 : 
{{$details[0]['content']}}

@foreach($details as $detail)
{{$detail['start_at']}}
{{url('calendar?id=' . $detail['id'])}}

@endforeach
このメールアドレスは送信専用です。

