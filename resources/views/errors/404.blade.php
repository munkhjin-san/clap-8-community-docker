<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ページは見つかりませんでした</title>
</head>
<body>
    <div style="display: flex; justify-content: center; align-items: center; height: 100vh;">
        <div style="background-color: #fff; padding: 30px; text-align: center;">
            <h1 style="font-size: 3em; margin-bottom: 20px;">ページは見つかりませんでした</h1>
            <p style="font-size: 1.5em; margin-bottom: 20px;"></p>
            <a href="{{ route('board') }}" style="background-color: #000000; color: #fff; padding: 10px 20px; text-decoration: none; font-size: 1.2em;">
                ホーム画面へ戻る
            </a>
        </div>
    </div>
</body>
</html>