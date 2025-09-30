@component('mail::message')
# 月次差異アラート（{{ $period->format('Y年n月') }}）

対象プロジェクト：**{{ $project_name }}**

@component('mail::table')
| 指標 | 予算 | 実績 | 乖離(%) |
|:--|--:|--:|--:|
@foreach ($rows as $r)
| {{ $r['metric_label'] }} | {{ number_format($r['plan'] ?? 0) }} | {{ number_format($r['actual'] ?? 0) }} | {{ $r['variance'] === null ? '—' : round($r['variance']).'%' }} |
@endforeach
@endcomponent

※ 閾値を超えた指標のみ表示しています。

@endcomponent
