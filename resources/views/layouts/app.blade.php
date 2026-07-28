<!doctype html >
<html lang="ja" class="notranslate" translate="no">
<head>
    <meta name="title" content="{{ __('meta.og_title') }}"/>
    <meta name="description" content="{{ __('meta.description') }}"/>
    <meta name="google" content="notranslate">
    <meta charset="utf-8">
    <meta name="robots" content="noindex">
    <meta name="theme-color" content="#262626"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">    
    <meta name="viewport" content="width=device-width, height=device-height, initial-scale=1, minimum-scale=1, maximum-scale=1, user-scalable=0, viewport-fit=cover" />

    @if (app()->environment('production'))
        <link rel="icon" href="/dark2.svg" type="image/svg+xml" media="(prefers-color-scheme: light)">
        <link rel="icon" href="/light2.svg" type="image/svg+xml" media="(prefers-color-scheme: dark)">
    @else
        <link rel="icon" href="/dev2.svg" type="image/svg+xml">
    @endif

    <link rel="manifest" href="/manifest.json">
    <title>GLOWD</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;600&display=swap" rel="stylesheet">
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

        if ('mediaSession' in navigator) {
        navigator.mediaSession.metadata = new MediaMetadata({
            artwork: [
            { src: '/v7/512.png', sizes: '512x512', type: 'image/png' },
            ],
        });
        }
    </script>
</head>
<body style="height:100%;position:fixed;overflow:hidden">
    <div id="app" style="height:100%;width:100%;" data-user-id="{{ Auth::id() }}">
        @yield('content')
    </div>
    @if(Auth::check())                
        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
            @csrf
        </form>                    
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.ts'])
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
    --scroll-bar: #808080;
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
    --inactive-background: #efefef;
    --complete: #9effb4;
    --panel-separate: #e9e9e9;
    --accent1: #b1cae7;
}

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
    --inactive-background: #292929;
    --complete: #004510;
    --panel-separate: #3b3b3b;
    --accent1: #1a3764;
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
</html>
