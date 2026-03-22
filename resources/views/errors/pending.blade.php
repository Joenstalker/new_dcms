<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification Pending - DCMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen p-4">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl border border-yellow-100 p-8 text-center">
        <div class="w-20 h-20 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-10 h-10 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Verification Pending</h1>
        <p class="text-gray-600 mb-6">
            Your clinic application is currently under review. Our administrators need to verify that your clinic is a legitimate business before granting access to the dental clinic portal.
        </p>
        
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
            <h2 class="text-sm font-semibold text-yellow-800 mb-2">Verification Time Remaining</h2>
            <div id="countdown" class="text-2xl font-mono font-bold text-yellow-600 mb-2">
                Loading...
            </div>
            <p class="text-xs text-yellow-700" id="expiry-message">
                You'll be notified via email once approved.
            </p>
        </div>

        <script>
            function startCountdown() {
                // Use the exact unix timestamp from the server (in milliseconds)
                // This prevents browser timezone parsing bugs when using unformatted date strings
                const expiresAt = {{ isset($expires_at) ? (is_string($expires_at) ? \Carbon\Carbon::parse($expires_at)->timestamp : $expires_at->timestamp) * 1000 : (isset($created_at) ? (is_string($created_at) ? \Carbon\Carbon::parse($created_at)->timestamp : $created_at->timestamp) * 1000 : time() * 1000) }};
                
                function updateTimer() {
                    const now = new Date().getTime();
                    const distance = expiresAt - now;
                    
                    if (distance <= 0) {
                        document.getElementById('countdown').innerHTML = '00:00:00';
                        document.getElementById('countdown').classList.add('text-red-600');
                        document.getElementById('countdown').classList.remove('text-yellow-600');
                        
                        // Update message
                        const messageEl = document.getElementById('expiry-message');
                        if (messageEl) {
                            messageEl.innerHTML = 'Your registration has expired. Please contact support.';
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
                    
                    // Change color when less than 24 hours remain
                    if (distance < 24 * 60 * 60 * 1000) {
                        document.getElementById('countdown').classList.add('text-orange-600');
                        document.getElementById('countdown').classList.remove('text-yellow-600');
                    }
                }
                
                // Update immediately and every second
                updateTimer();
                setInterval(updateTimer, 1000);
            }
            startCountdown();
        </script>
        
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
            <h2 class="text-sm font-semibold text-gray-800 mb-2">What happens next?</h2>
            <ul class="text-xs text-yellow-700 text-left space-y-2">
                <li class="flex items-start">
                    <svg class="w-4 h-4 mr-1 mt-0.5 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                    </svg>
                    Please wait while we verify your clinic
                </li>
                <li class="flex items-start">
                    <svg class="w-4 h-4 mr-1 mt-0.5 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                        <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                    </svg>
                    You'll receive an email once verification is complete
                </li>
                <li class="flex items-start">
                    <svg class="w-4 h-4 mr-1 mt-0.5 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
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
        
        <div class="flex gap-3">
            <button onclick="window.location.reload()" class="flex-1 bg-[#2B7CB3] hover:bg-[#236491] text-white font-semibold px-6 py-3 rounded-lg transition-colors">
                Check Status
            </button>
            <a href="mailto:admin@dcms.com" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold px-6 py-3 rounded-lg transition-colors">
                Contact Support
            </a>
        </div>
    </div>
</body>
</html>
