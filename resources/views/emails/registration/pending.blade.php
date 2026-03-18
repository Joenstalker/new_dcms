<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Received</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">
    <div class="max-w-2xl mx-auto py-8 px-4">
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <!-- Header -->
            <div class="bg-teal-600 px-6 py-4">
                <h1 class="text-2xl font-bold text-white">Welcome, {{ $registration->clinic_name }}!</h1>
            </div>
            
            <!-- Content -->
            <div class="p-6">
                <p class="text-gray-700 mb-6">
                    Dear <strong>{{ $registration->first_name }} {{ $registration->last_name }}</strong>,
                </p>
                
                <p class="text-gray-700 mb-6">
                    Thank you for registering your dental clinic, <strong>{{ $registration->clinic_name }}</strong>. 
                    Your payment has been successfully processed. However, your clinic is currently pending verification.
                </p>
                
                <!-- Status Notice -->
                <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700">
                                <strong>Awaiting Verification</strong><br>
                                Your clinic application is pending admin review. This typically takes up to 1 hour. 
                                Please check your email again for updates.
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Credentials -->
                <h2 class="text-lg font-semibold text-gray-900 mb-3">Your Account Credentials</h2>
                <div class="bg-gray-50 rounded-lg p-4 mb-6">
                    <table class="w-full">
                        <tr>
                            <td class="py-2 text-gray-600 font-medium w-32">URL:</td>
                            <td class="py-2">
                                <a href="{{ config('app.url') }}/tenant/{{ $registration->subdomain }}" class="text-teal-600 hover:text-teal-800 underline">
                                    {{ config('app.url') }}/tenant/{{ $registration->subdomain }}
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td class="py-2 text-gray-600 font-medium">Fullname:</td>
                            <td class="py-2 text-gray-900 font-semibold">{{ $registration->first_name }} {{ $registration->last_name }}</td>
                        </tr>
                        <tr>
                            <td class="py-2 text-gray-600 font-medium">EMAIL:</td>
                            <td class="py-2 text-gray-900">{{ $registration->email }}</td>
                        </tr>
                        <tr>
                            <td class="py-2 text-gray-600 font-medium">PASSWORD:</td>
                            <td class="py-2 text-gray-900 font-mono">{{ $registration->plain_password }}</td>
                        </tr>
                    </table>
                </div>
                
                <p class="text-gray-600 text-sm mb-4">
                    <strong>Note:</strong> Your clinic portal is not yet accessible. Once an administrator reviews and approves 
                    your application, you will receive a confirmation email with access instructions.
                </p>
                
                <p class="text-gray-600 text-sm">
                    If you have any questions, please contact our support team.
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
