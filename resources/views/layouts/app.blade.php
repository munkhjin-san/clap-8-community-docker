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
    <meta name="theme-color" content="#262626"/>
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
<body style="height:100%;position:fixed;overflow:hidden">
    <div id="app" style="height:100%;width:100%;" data-user-id="{{ Auth::id() }}">
    <over-ride></over-ride>               
        
        @yield('content')
    </div>
    @if(Auth::check())                
        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
            @csrf
        </form>                    
    @endif
</body>
<style lang="scss">
:root {
    // Define the CSS variables or Sass variables with default values
    --primary-color: #000000;
    --background-color: #ffffff;
    --bg2: #ddd;
    --bg3: #efefef;
    --hoverBorder: #000000;
    --normalBorder: #dddddd; 
    --formBorder: #cccccc;
    --skItem1: #eaeaea;
    --skItem2: #f1f1f1;
    --message-background: #ffffff;
    --primary-button: #000000;
    --menu-bg: #ffffff;
    --soft-bg: #e7e7e790;
    --scroll-bar: #000000;
    --secondary-background: #e6e6e6;
    --selected-background: rgba(204, 223, 245, 0.5);
    --check-inactive: #c0c0c0;
    --calendarBorder: #ddd;
    --kebab-bg1: #ebebeb;
    --kebab-icon: #000000;
    --calendarToday: #dddddd;
    --overlay: rgba(0,0,0,0.6);  
    --side-menu-bg: #f5f5f5;
    --side-menu-border: #cdcdcd;
    --link-color: #1a73e8;
    --task-background: #dddddd;
    --past-calendar: #cccccc;
    --third-color: #878787;
    --inactive-background: #efefef
}

// If the app is in dark mode, update the variables
.dark-mode {
    --primary-color: #e4e6eb;
    --background-color: #323232;
    --bg2: #262626;
    --bg3: #262626;
    --hoverBorder: #727272;
    --normalBorder: #464646; 
    --formBorder: #727272;
    --skItem1: #26262665;
    --skItem2: #5f5f5f;
    --message-background: #3d3d3d;
    --primary-button: #4b4b4b;
    --menu-bg: #4a4a4a;
    --soft-bg: #00000020;
    --scroll-bar: #5e5e5e;
    --secondary-background: #5e5e5e;
    --selected-background: #3d3d3d;
    --check-inactive: #898989;
    --calendarBorder: #404040;
    --kebab-bg1: #4a4a4a;
    --kebab-icon: #949494;
    --calendarToday: #4a4a4a;
    --overlay: rgba(0,0,0,0.8);  
    --side-menu-bg: #181818;
    --side-menu-border: #404040;
    --link-color: #81b8fd;
    --task-background: #3d3d3d;
    --past-calendar: #494949;
    --third-color: #e4e6eb;
    --inactive-background: #292929
}
.header {
    background-color: var(--background-color);
}
.primary-button {
  background-color: var(--primary-color);
  color: #ffffff;
}
.hd-hr{
    background-color: var(--background-color);
}

.errorWindow{
    background: #fff;
    border-radius: 8px;
    padding:20px;
    height: fit-content;
    margin-top:20px;
    font-size: 16px;
    line-height: 1.5;
}
@media screen and (max-width: 959px) {
    
    .errorWindow{
        width: 80%;
    }
}
</style>
<script src="{{ mix('js/app.js') }}" defer></script>
</html>
