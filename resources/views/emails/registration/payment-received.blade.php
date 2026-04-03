<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Received - Awaiting Approval</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/laravel-echo@1.15.0/dist/laravel-echo.min.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        /* Prevent scrolling on body */
        body { overflow: hidden; }
    </style>
</head>
<body class="bg-gray-100 font-sans">
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75"></div>
            
            <!-- Modal Panel -->
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                <!-- Header -->
                <div class="bg-[#2B7CB3] px-6 py-4">
                    <h1 class="text-2xl font-bold text-white">Payment Received!</h1>
                </div>
                
                <!-- Content -->
                <div class="p-6">
                    <p class="text-gray-700 mb-4">
                        Dear <strong>{{ $registration->first_name }} {{ $registration->last_name }}</strong>,
                    </p>
                    
                    <p class="text-gray-700 mb-4">
                        Thank you for registering <strong>{{ $registration->clinic_name }}</strong>. 
                        Your payment of <strong>₱{{ number_format($registration->amount_paid, 2) }}</strong> has been successfully received!
                    </p>
                    
                    <!-- Countdown Timer -->
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                        <h3 class="text-sm font-semibold text-yellow-800 mb-2">Approval Time Remaining</h3>
                        <div id="countdown" class="text-2xl font-mono font-bold text-yellow-600 mb-2">
                            Loading...
                        </div>
                        <p class="text-xs text-yellow-700" id="expiry-message">
                            Our administrator will review your application soon.
                        </p>
                    </div>

                    <script>
                        function startCountdown() {
                            const expiresAt = new Date('{{ $registration->expires_at ? $registration->expires_at->toIso8601String() : now()->addHours(168)->toIso8601String() }}').getTime();
                            
                            function updateTimer() {
                                const now = new Date().getTime();
                                const distance = expiresAt - now;
                                
                                if (distance <= 0) {
                                    document.getElementById('countdown').innerHTML = '00:00:00';
                                    document.getElementById('countdown').classList.add('text-red-600');
                                    document.getElementById('countdown').classList.remove('text-yellow-600');
                                    document.getElementById('expiry-message').innerHTML = 'Your registration has expired. Please contact support.';
                                    document.getElementById('expiry-message').classList.add('text-red-600');
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
                                    document.getElementById('countdown').classList.remove('text-yellow-600');
                                }
                            }
                            
                            updateTimer();
                            setInterval(updateTimer, 1000);
                        }
                        startCountdown();
                    </script>
                    
                    <!-- Registration Summary -->
                    <div class="bg-gray-50 rounded-lg p-4 mb-4">
                        <h2 class="text-sm font-semibold text-gray-900 mb-2">Registration Details</h2>
                        <div class="text-sm space-y-1">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Clinic:</span>
                                <span class="font-medium">{{ $registration->clinic_name }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">URL:</span>
                                <span class="text-[#2B7CB3] font-medium">
                                    <?php $appUrl = config('app.url'); $parsed = parse_url($appUrl); $host = $parsed['host'] ?? str_replace(['http://', 'https://'], '', $appUrl); $port = isset($parsed['port']) ? ':' . $parsed['port'] : ''; $fullUrl = 'http://' . $registration->subdomain . '.' . $host . $port; ?>
                                    <a href="<?php echo $fullUrl; ?>" target="_blank" class="text-[#2B7CB3] hover:text-[#1F547A] underline"><?php echo $fullUrl; ?></a>
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Amount Paid:</span>
                                <span class="text-green-600 font-semibold">₱{{ number_format($registration->amount_paid, 2) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Status:</span>
                                <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    Awaiting Approval
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <p class="text-gray-600 text-sm mb-2">
                        <strong>What's next?</strong> Once an administrator reviews and approves your application, 
                        you will receive a confirmation email with your login credentials.
                    </p>
                </div>
                
                <!-- Footer -->
                <div class="bg-gray-50 px-6 py-4 sm:flex sm:flex-row-reverse">
                    <a
                        href="{{ config('app.url') }}/?payment-success=true"
                        target="_top"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-[#2B7CB3] text-base font-medium text-white hover:bg-[#236491] focus:outline-none sm:ml-3 sm:w-auto sm:text-sm"
                    >
                        Back to Home
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
