<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Tenant Registration</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">
    <div class="max-w-2xl mx-auto py-8 px-4">
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <!-- Header -->
            <div class="bg-blue-600 px-6 py-4">
                <h1 class="text-2xl font-bold text-white">New Tenant Registration</h1>
            </div>
            
            <!-- Content -->
            <div class="p-6">
                <p class="text-gray-700 mb-6">
                    A new dental clinic has registered and is awaiting approval.
                </p>
                
                <!-- Registration Details -->
                <div class="bg-gray-50 rounded-lg p-4 mb-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-3">Registration Details</h2>
                    <table class="w-full">
                        <tr>
                            <td class="py-2 text-gray-600 font-medium w-40">Clinic Name:</td>
                            <td class="py-2 text-gray-900 font-semibold">{{ $registration->clinic_name }}</td>
                        </tr>
                        <tr>
                            <td class="py-2 text-gray-600 font-medium">Subdomain:</td>
                            <td class="py-2 text-teal-600">{{ $registration->subdomain }}.{{ parse_url(config('app.url'), PHP_URL_HOST) }}</td>
                        </tr>
                        <tr>
                            <td class="py-2 text-gray-600 font-medium">Owner Name:</td>
                            <td class="py-2 text-gray-900">{{ $registration->first_name }} {{ $registration->last_name }}</td>
                        </tr>
                        <tr>
                            <td class="py-2 text-gray-600 font-medium">Email:</td>
                            <td class="py-2 text-gray-900">{{ $registration->email }}</td>
                        </tr>
                        <tr>
                            <td class="py-2 text-gray-600 font-medium">Phone:</td>
                            <td class="py-2 text-gray-900">{{ $registration->phone ?? 'Not provided' }}</td>
                        </tr>
                        <tr>
                            <td class="py-2 text-gray-600 font-medium">Plan:</td>
                            <td class="py-2 text-gray-900">{{ $registration->plan->name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="py-2 text-gray-600 font-medium">Amount Paid:</td>
                            <td class="py-2 text-green-600 font-semibold">₱{{ number_format($registration->amount_paid, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="py-2 text-gray-600 font-medium">Registered:</td>
                            <td class="py-2 text-gray-900">{{ $registration->created_at->format('M d, Y h:i A') }}</td>
                        </tr>
                        <tr>
                            <td class="py-2 text-gray-600 font-medium">Expires:</td>
                            <td class="py-2 text-gray-900">{{ $registration->expires_at->format('M d, Y h:i A') }}</td>
                        </tr>
                    </table>
                </div>
                
                <!-- Address -->
                @if($registration->street || $registration->barangay || $registration->city || $registration->province)
                <div class="bg-gray-50 rounded-lg p-4 mb-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-3">Address</h2>
                    <p class="text-gray-700">
                        {{ $registration->street }}<br>
                        {{ $registration->barangay }}<br>
                        {{ $registration->city }}, {{ $registration->province }}
                    </p>
                </div>
                @endif
                
                <!-- Action Required -->
                <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700">
                                <strong>Action Required</strong><br>
                                Please review this registration and approve or reject within the specified time limit to process the payment.
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="flex gap-4">
                    <a href="{{ config('app.url') }}/admin/tenants" class="inline-block bg-teal-600 hover:bg-teal-700 text-white font-semibold px-6 py-3 rounded-lg transition-colors">
                        Review Registrations
                    </a>
                </div>
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
