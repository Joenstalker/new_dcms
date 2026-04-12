<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title')</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        fontFamily: {
                            sans: ['Plus Jakarta Sans', 'sans-serif'],
                        },
                        animation: {
                            'fade-in': 'fadeIn 0.35s ease-out',
                            'zoom-in': 'zoomIn 0.35s ease-out',
                        },
                        keyframes: {
                            fadeIn: {
                                '0%': { opacity: '0' },
                                '100%': { opacity: '1' },
                            },
                            zoomIn: {
                                '0%': { transform: 'scale(0.96)' },
                                '100%': { transform: 'scale(1)' },
                            },
                        },
                    },
                },
            }
        </script>
        <style>
            html, body {
                font-family: 'Plus Jakarta Sans', sans-serif;
                background-color: transparent !important;
                margin: 0;
                min-height: 100vh;
            }
        </style>
    </head>
    <body class="antialiased min-h-screen flex items-center justify-center p-4 sm:p-6">
        <div
            class="max-w-md w-full animate-fade-in animate-zoom-in"
            role="alertdialog"
            aria-labelledby="error-code"
            aria-describedby="error-message"
        >
            <div class="text-center px-2 py-4">
                <div class="mb-5">
                    <div class="w-20 h-20 bg-red-100 rounded-2xl flex items-center justify-center mx-auto mb-5 transform rotate-3 shadow-lg shadow-red-200/60 ring-1 ring-red-200/80">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-10 h-10 text-red-600 -rotate-3 shrink-0" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                    </div>
                    <h1 id="error-code" class="text-6xl sm:text-7xl font-extrabold text-transparent bg-clip-text bg-gradient-to-br from-red-600 to-rose-800 tracking-tighter mb-2 drop-shadow-sm">
                        @yield('code')
                    </h1>
                    <div class="inline-flex items-center px-3 py-1 rounded-full bg-red-50 text-red-800 text-xs font-bold uppercase tracking-widest border border-red-100">
                        <span>@yield('title')</span>
                    </div>
                </div>

                <p id="error-message" class="text-slate-800 text-base sm:text-lg font-medium leading-relaxed mb-8 max-w-sm mx-auto">
                    @yield('message')
                </p>

                <button
                    type="button"
                    id="error-ok-btn"
                    class="w-full max-w-sm mx-auto rounded-2xl py-3.5 px-6 font-bold text-base text-white bg-gradient-to-r from-red-600 to-rose-700 hover:from-red-700 hover:to-rose-800 shadow-lg shadow-red-600/30 transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-2 focus-visible:ring-offset-transparent"
                >
                    {{ __('Ok') }}
                </button>
            </div>
        </div>

        <script>
            (function () {
                function dismissAppError() {
                    if (window.history.length > 1) {
                        window.history.back();
                        return;
                    }
                    var ref = document.referrer;
                    try {
                        if (ref && new URL(ref).origin === window.location.origin) {
                            window.location.assign(ref);
                            return;
                        }
                    } catch (e) {}
                    window.location.href = @json(url('/'));
                }
                var btn = document.getElementById('error-ok-btn');
                if (btn) {
                    btn.addEventListener('click', dismissAppError);
                }
            })();
        </script>
    </body>
</html>
