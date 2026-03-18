<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Received - Awaiting Approval</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">
    <div class="max-w-2xl mx-auto py-8 px-4">
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <!-- Header -->
            <div class="bg-teal-600 px-6 py-4">
                <h1 class="text-2xl font-bold text-white">Payment Received!</h1>
            </div>
            
            <!-- Content -->
            <div class="p-6">
                <p class="text-gray-700 mb-6">
                    Dear <strong>{{ $registration->first_name }} {{ $registration->last_name }}</strong>,
                </p>
                
                <p class="text-gray-700 mb-6">
                    Thank you for registering <strong>{{ $registration->clinic_name }}</strong>. 
                    Your payment of <strong>₱{{ number_format($registration->amount_paid, 2) }}</strong> has been successfully received!
                </p>
                
                <!-- Status Notice -->
                <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
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
                <h2 class="text-lg font-semibold text-gray-900 mb-3">Registration Details</h2>
                <div class="bg-gray-50 rounded-lg p-4 mb-6">
                    <table class="w-full">
                        <tr>
                            <td class="py-2 text-gray-600 font-medium w-32">Clinic:</td>
                            <td class="py-2 text-gray-900 font-semibold">{{ $registration->clinic_name }}</td>
                        </tr>
                        <tr>
                            <td class="py-2 text-gray-600 font-medium">URL:</td>
                            <td class="py-2 text-teal-600 font-medium">{{ $registration->subdomain }}.{{ config('app.url') }}</td>
                        </tr>
                        <tr>
                            <td class="py-2 text-gray-600 font-medium">Email:</td>
                            <td class="py-2 text-gray-900">{{ $registration->email }}</td>
                        </tr>
                        <tr>
                            <td class="py-2 text-gray-600 font-medium">Amount Paid:</td>
                            <td class="py-2 text-green-600 font-semibold">₱{{ number_format($registration->amount_paid, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="py-2 text-gray-600 font-medium">Status:</td>
                            <td class="py-2">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    Awaiting Approval
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <p class="text-gray-600 text-sm mb-4">
                    <strong>What's next?</strong> Once an administrator reviews and approves your application, 
                    you will receive a confirmation email with your login credentials and access instructions.
                </p>
                
                <p class="text-gray-600 text-sm">
                    If you have any questions in the meantime, please contact our support team.
                </p>
            </div>
            
            <!-- Footer -->
            <div class="bg-gray-100 px-6 py-4">
                <p class="text-gray-500 text-sm text-center">
                    &copy; {{ date('Y') }} Dental Clinic Management System. All rights reserved.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
