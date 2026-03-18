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
                <div class="bg-teal-600 px-6 py-4">
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
                    
                    <!-- Status Notice -->
                    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-700">
                                    <strong>Awaiting Admin Approval</strong><br>
                                    Your clinic application is pending review by our administrator. 
                                    This typically takes up to 1 hour during business hours.
                                </p>
                            </div>
                        </div>
                    </div>
                    
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
                                <span class="text-teal-600 font-medium">
                                    <?php $appUrl = config('app.url'); $parsed = parse_url($appUrl); $host = $parsed['host'] ?? str_replace(['http://', 'https://'], '', $appUrl); $port = isset($parsed['port']) ? ':' . $parsed['port'] : ''; $fullUrl = 'http://' . $registration->subdomain . '.' . $host . $port; ?>
                                    <a href="<?php echo $fullUrl; ?>" target="_blank" class="text-teal-600 hover:text-teal-800 underline"><?php echo $fullUrl; ?></a>
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
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-teal-600 text-base font-medium text-white hover:bg-teal-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm"
                    >
                        Back to Home
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
