<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Verification Pending') }} - DCMS</title>
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
    <div class="max-w-md w-full py-6 text-center px-2" role="alertdialog" aria-labelledby="pending-title">
                    <div class="w-20 h-20 bg-amber-100 rounded-2xl flex items-center justify-center mx-auto mb-5 ring-1 ring-amber-200/80">
                        <svg class="w-10 h-10 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h1 id="pending-title" class="text-2xl font-extrabold text-slate-900 mb-2">{{ __('Verification Pending') }}</h1>
                    <p class="text-slate-600 text-sm font-medium leading-relaxed mb-6">
                        {{ __('Your clinic application is currently under review. Our administrators need to verify that your clinic is a legitimate business before granting access to the dental clinic portal.') }}
                    </p>

                    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6 text-left">
                        <h2 class="text-sm font-semibold text-amber-900 mb-2 text-center">{{ __('Verification Time Remaining') }}</h2>
                        <div id="countdown" class="text-2xl font-mono font-bold text-amber-700 mb-2 text-center">
                            Loading...
                        </div>
                        <p class="text-xs text-amber-800 text-center" id="expiry-message">
                            {{ __("You'll be notified via email once approved.") }}
                        </p>
                    </div>

                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 mb-6 text-left">
                        <h2 class="text-sm font-semibold text-slate-800 mb-2">{{ __('What happens next?') }}</h2>
                        <ul class="text-xs text-amber-900 space-y-2">
                            <li class="flex items-start">
                                <svg class="w-4 h-4 mr-1.5 mt-0.5 text-amber-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                                </svg>
                                {{ __('Please wait while we verify your clinic') }}
                            </li>
                            <li class="flex items-start">
                                <svg class="w-4 h-4 mr-1.5 mt-0.5 text-amber-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                                    <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                                </svg>
                                {{ __("You'll receive an email once verification is complete") }}
                            </li>
                            <li class="flex items-start">
                                <svg class="w-4 h-4 mr-1.5 mt-0.5 text-amber-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                                </svg>
                                @php
                                    $timeoutText = 'the verification period';
                                    if (isset($timeout_minutes)) {
                                        if ($timeout_minutes >= 1440 && $timeout_minutes % 1440 == 0) {
                                            $days = $timeout_minutes / 1440;
                                            $timeoutText = $days . ' day' . ($days != 1 ? 's' : '');
                                        } elseif ($timeout_minutes >= 60 && $timeout_minutes % 60 == 0) {
                                            $hours = $timeout_minutes / 60;
                                            $timeoutText = $hours . ' hour' . ($hours != 1 ? 's' : '');
                                        } else {
                                            $timeoutText = $timeout_minutes . ' minute' . ($timeout_minutes != 1 ? 's' : '');
                                        }
                                    }
                                @endphp
                                If not verified within {{ $timeoutText }}, you'll receive an automatic refund
                            </li>
                        </ul>
                    </div>

                    <p class="text-xs text-slate-500 mb-6">
                        {{ __('Questions?') }}
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

    @php
        $expiresAt = 0;
        if (isset($expires_at)) {
            $expiresAt = \Illuminate\Support\Carbon::parse($expires_at)->timestamp * 1000;
        } elseif (isset($created_at)) {
            $expiresAt = \Illuminate\Support\Carbon::parse($created_at)->timestamp * 1000;
        } else {
            $expiresAt = time() * 1000;
        }
    @endphp

    <script>
        function startCountdown() {
            const expiresAt = Number("{{ $expiresAt }}");
            const serverTimeMs = Number("{{ time() * 1000 }}");
            const timeOffset = serverTimeMs - new Date().getTime();

            function updateTimer() {
                const now = new Date().getTime() + timeOffset;
                const distance = expiresAt - now;

                if (distance <= 0) {
                    document.getElementById('countdown').innerHTML = '00:00:00';
                    document.getElementById('countdown').classList.add('text-red-600');
                    document.getElementById('countdown').classList.remove('text-amber-700', 'text-orange-600');
                    const messageEl = document.getElementById('expiry-message');
                    if (messageEl) {
                        messageEl.textContent = "{{ __('Your registration has expired. Please contact support.') }}";
                        messageEl.classList.add('text-red-600');
                    }
                    return;
                }

                const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                let timeString = '';
                if (days > 0) {
                    timeString = days + 'd ' + (hours < 10 ? '0' : '') + hours + ':' + (minutes < 10 ? '0' : '') + minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
                } else {
                    timeString = (hours < 10 ? '0' : '') + hours + ':' + (minutes < 10 ? '0' : '') + minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
                }

                document.getElementById('countdown').innerHTML = timeString;

                if (distance < 24 * 60 * 60 * 1000) {
                    document.getElementById('countdown').classList.add('text-orange-600');
                    document.getElementById('countdown').classList.remove('text-amber-700');
                }
            }

            updateTimer();
            setInterval(updateTimer, 1000);
        }
        startCountdown();

        document.getElementById('error-ok-btn').addEventListener('click', function() {
            window.location.href = 'http://localhost:8080/';
        });
    </script>
</body>
</html>
