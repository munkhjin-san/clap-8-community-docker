<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>GLOWD - メール認証</title>
    <style>
        :root { color-scheme: light dark; }
        body { font-family: system-ui, sans-serif; display: flex; min-height: 100vh; margin: 0;
               align-items: center; justify-content: center; background: #f3f4f6; }
        .card { background: #fff; padding: 2rem; border-radius: .5rem; width: min(360px, 90vw);
                box-shadow: 0 4px 6px -1px rgb(0 0 0 / .1); }
        h1 { font-size: 1.25rem; margin: 0 0 1rem; text-align: center; }
        p.hint { color: #6b7280; font-size: .85rem; margin: 0 0 1rem; }
        label { display: block; font-size: .8rem; margin: 1rem 0 .25rem; color: #374151; }
        input[type=text] { width: 100%; box-sizing: border-box; padding: .6rem; border: 1px solid #d1d5db;
                           border-radius: .375rem; font-size: 1rem; letter-spacing: 3px; text-align: center; }
        button { width: 100%; margin-top: 1.5rem; padding: .7rem; border: 0; border-radius: .375rem;
                 background: #2563eb; color: #fff; font-size: 1rem; cursor: pointer; }
        .error { color: #dc2626; font-size: .85rem; margin: .5rem 0 0; }
        label.remember { display: flex; align-items: center; gap: .5rem; margin-top: 1.25rem;
                         font-size: .8rem; color: #6b7280; }
        label.remember input { width: auto; }
        .resend { margin-top: 1rem; text-align: center; }
        .resend button { background: none; color: #2563eb; width: auto; padding: 0; margin: 0;
                         font-size: .8rem; text-decoration: underline; }
    </style>
</head>
<body>
    <form class="card" method="POST" action="/email-otp-challenge">
        @csrf
        <h1>メール認証</h1>
        <p class="hint">登録メールアドレスに送信した6桁のコードを入力してください。</p>

        @if ($errors->any())
            <p class="error">{{ $errors->first() }}</p>
        @endif

        <label for="code">認証コード</label>
        <input id="code" type="text" name="code" inputmode="numeric" maxlength="6" autofocus autocomplete="one-time-code">

        <label class="remember">
            <input type="checkbox" name="remember_device" value="1">
            この端末を記憶する（30日間、次回からコード入力を省略）
        </label>

        <button type="submit">認証する</button>
    </form>

    <form class="resend" method="POST" action="/email-otp-challenge/resend">
        @csrf
        <button type="submit">コードを再送する</button>
    </form>
</body>
</html>
