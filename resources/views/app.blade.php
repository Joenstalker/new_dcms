<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <script>
            (function() {
                var theme = localStorage.getItem('dcms-theme') || 'light';
                document.documentElement.setAttribute('data-theme', theme);
            })();
        </script>

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- CSRF Token -->
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <!-- Favicon -->
        <link rel="icon" href="/favicon.ico" type="image/x-icon">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Google reCAPTCHA -->
        <script>
            window.onRecaptchaLoadGlobal = function() {
                window.dispatchEvent(new CustomEvent('recaptcha-loaded'));
            };
        </script>
        <script src="https://www.google.com/recaptcha/api.js?render=explicit&onload=onRecaptchaLoadGlobal" async defer></script>

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
