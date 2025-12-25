<p>
チャレンジのステータスが更新されました。</p>

@if(!empty($post->title))
<p>タイトル: {{ $post->title }}</p>
@endif

<p>ID: {{ $post->id }}</p>
@php
$status_map = ["実施中", "達成", "未達成", "中止","不成立", "進捗"];
@endphp
<p>ステータス: {{ $status_map[$post->status_flag] ?? $post->status_flag }}</p>

@if(!empty($post->result))
<p>結果：{!! nl2br(e($post->result)) !!}</p>
@endif

<br>
以下のURLにて内容をご確認ください。<br>
<br>
<a href="{!! url('post/?id=' . $post->id) !!}">{!! url('post/?id=' . $post->id) !!}</a>
