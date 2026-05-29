<!DOCTYPE html>
<html lang="ja">
<body>
<p>{{ $user->name }} 様</p>

<p>お疲れ様です。経営管理本部です。</p>

@if($isIncident)
<p>
昨日ご案内いたしましたが、日報がまだ未申請のため、インシデントとして対応いたします。<br>
早急にご申請をお願いいたします。
</p>
@else
<p>
日報が未申請です。<br>
本日中にご申請いただけない場合、インシデントとして扱われますので、ご申請をお願いいたします。
</p>
@endif

<p>
未申請日：<br>
@foreach($missingDays as $day)
・{{ $day }}<br>
@endforeach
</p>

<p>お忙しいところ恐れ入りますが、ご対応のほどよろしくお願いいたします。</p>

<hr>
<small>このメールアドレスは送信専用です。</small>
</body>
</html>
