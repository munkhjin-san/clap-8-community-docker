<!doctype html >
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="notranslate" translate="no">
<head>
    <meta name="title" content="{{ __('meta.og_title') }}"/>
    <meta name="description" content="{{ __('meta.description') }}"/>
    <meta property="og:title" content="{{ __('meta.og_title') }}" />
    <meta property="og:description" content="{{ __('meta.description') }}" />
    <meta property="og:image" content="https://glowd.app/glowd_icon_200.png">
    <meta property='twitter:title' content="{{ __('meta.og_title') }}"/>
    <meta property='twitter:image' content="https://glowd.app/glowd_icon_200.png"/>
    <meta name="twitter:card" content="summary"/>
    <meta name="google" content="notranslate">
    <meta charset="utf-8">
    <meta name="robots" content="noindex">
    <!-- <meta name="googlebot" content="index, follow"> -->
    <meta name="csrf-token" content="{{ csrf_token() }}">    
    <meta name="viewport" content="width=device-width, height=device-height, initial-scale=1, minimum-scale=1, maximum-scale=1, user-scalable=0, viewport-fit=cover" />
    <!-- <link rel="apple-touch-icon" size="152x152" href="/glowd_icon_192.png">
    <link rel="icon" type="image/png" size="152x152" href="/glowd_icon_192-152.png"> 
    <link rel="apple-touch-icon" sizes="180x180" href="/glowd_icon_180.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="icon" type="image/x-icon" sizes="36x36" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="36x36" href="/favicon.png"> -->
    <link rel="apple-touch-icon" size="152x152" href="/app/public/icon-152x152.png">
    <link rel="icon" type="image/png" size="152x152" href="/app/public/icon-152x152.png">
    <link rel="manifest" href="/site.webmanifest">
    <!-- <link rel="canonical" href="https://glowd.app/auth"> -->
    <title>CLAP</title>
    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">

    <!-- Styles -->
    <link href="{{ mix('css/app.css') }}" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <!-- <link href="https://fonts.googleapis.com/css2?family=Noto+Sans" rel="stylesheet"> -->
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP&display=swap" rel="stylesheet">
    <script>
        /* ピッチインピッチアウトによる拡大縮小を禁止 */
        document.documentElement.addEventListener('touchstart', function (e) {
            if (e.touches && e.touches.length >= 2) {e.preventDefault();}
        }, {passive: false});
        /* ダブルタップによる拡大を禁止 */
        var t = 0;
        document.documentElement.addEventListener('touchend', function (e) {
            var now = new Date().getTime();
            if ((now - t) < 350){
                e.preventDefault();
            }
            t = now;
        }, false);
    </script>
</head>
<body style="height:100%;">
    <div id="app" style="height:100%;width:100%;" data-user-id="{{ Auth::id() }}">
    <over-ride></over-ride>   
        @if(Auth::check())     
                      
            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                @csrf
            </form>                    
        @endif
        @yield('content')
    </div>
</body>
<script src="{{ mix('js/app.js') }}" defer></script>
</html>
