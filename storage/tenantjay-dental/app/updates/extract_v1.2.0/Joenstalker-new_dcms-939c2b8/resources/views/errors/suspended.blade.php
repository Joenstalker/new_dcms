<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Clinic Suspended') }} - DCMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { fontFamily: { sans: ['Plus Jakarta Sans', 'sans-serif'] } } } };
    </script>
    <style>
        html, body {
            background-color: transparent !important;
            margin: 0;
            min-height: 100vh;
        }
    </style>
</head>
<body class="antialiased min-h-screen font-sans flex items-center justify-center p-4 sm:p-6">
    <div class="max-w-md w-full text-center px-2 py-4" role="alertdialog" aria-labelledby="suspended-title">
        <div class="w-20 h-20 bg-red-100 rounded-2xl flex items-center justify-center mx-auto mb-5 ring-1 ring-red-200/80">
            <svg class="w-10 h-10 text-red-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </div>
        <h1 id="suspended-title" class="text-2xl font-extrabold text-slate-900 mb-2">{{ __('Clinic Suspended') }}</h1>
        <p class="text-slate-800 text-base font-medium leading-relaxed mb-2 max-w-sm mx-auto">
            {{ __('Access to this platform has been suspended by the administrator. Please contact support to resolve this issue.') }}
        </p>
        <p class="text-sm text-slate-600 mb-8">
            <a href="mailto:admin@dcms.com" class="text-red-700 font-semibold hover:underline">admin@dcms.com</a>
        </p>
        <button
            type="button"
            id="error-ok-btn"
            class="w-full max-w-sm mx-auto rounded-2xl py-3.5 px-6 font-bold text-base text-white bg-gradient-to-r from-red-600 to-rose-700 hover:from-red-700 hover:to-rose-800 shadow-lg shadow-red-600/30 transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-2 focus-visible:ring-offset-transparent"
        >
            {{ __('Ok') }}
        </button>
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
            document.getElementById('error-ok-btn').addEventListener('click', dismissAppError);
        })();
    </script>
</body>
</html>
