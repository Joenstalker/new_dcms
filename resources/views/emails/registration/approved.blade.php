<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Clinic is Now Live</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">
    <div class="max-w-2xl mx-auto py-8 px-4">
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <!-- Header -->
            <div class="bg-green-600 px-6 py-4">
                <h1 class="text-2xl font-bold text-white">🎉 Your Clinic is Now Live!</h1>
            </div>
            
            <!-- Content -->
            <div class="p-6">
                <p class="text-gray-700 mb-6">
                    Dear <strong>{{ is_array($registration->first_name) ? $registration->first_name['first_name'] ?? '' : ($registration->first_name ?? '') }}</strong>,
                </p>
                
                <p class="text-gray-700 mb-6">
                    Great news! Your dental clinic <strong>{{ $registration->clinic_name }}</strong> has been approved and is now live!
                </p>
                
                <!-- Success Notice -->
                <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-green-700">
                                <strong>Access Granted!</strong><br>
                                Your clinic subdomain is now active and ready for use.
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Login Details -->
                <h2 class="text-lg font-semibold text-gray-900 mb-3">Your Login Credentials</h2>
                <div class="bg-gray-50 rounded-lg p-4 mb-6">
                    <table class="w-full">
                        <tr>
                            <td class="py-2 text-gray-600 font-medium w-32">URL:</td>
                            <td class="py-2">
                                <a href="{{ $tenantUrl }}" class="text-teal-600 hover:text-teal-800 underline font-semibold">
                                    {{ $tenantUrl }}
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td class="py-2 text-gray-600 font-medium">Username:</td>
                            <td class="py-2 text-gray-900 font-semibold">{{ is_array($registration->first_name) ? ($registration->first_name['first_name'] ?? '') . ' ' . ($registration->first_name['last_name'] ?? '') : ($registration->owner_name ?? ($registration->first_name . ' ' . $registration->last_name)) }}</td>
                        </tr>
                        <tr>
                            <td class="py-2 text-gray-600 font-medium">Email:</td>
                            <td class="py-2 text-gray-900">{{ $registration->email }}</td>
                        </tr>
                        <tr>
                            <td class="py-2 text-gray-600 font-medium">Password:</td>
                            <td class="py-2 text-gray-900 font-mono">{{ is_array($registration->password) ? '' : ($registration->password ?? 'As set during registration') }}</td>
                        </tr>
                    </table>
                </div>
                
                <p class="text-gray-600 text-sm mb-4">
                    You can now access your website and use the credentials above to access your portal.
                </p>
                
                <p class="text-sm font-semibold text-red-600 mb-4 border border-red-200 bg-red-50 p-3 rounded">
                    NOTE FOR THE TENANT: PLEASE CHANGE YOUR PASSWORD AS MUCH AS POSSIBLE.
                </p>
                
                <p class="text-gray-600 text-sm mb-4">
                    If you have any questions or need assistance, please don't hesitate to contact our support team.
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
