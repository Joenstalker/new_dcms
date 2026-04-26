<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Refund Processed</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">
    <div class="max-w-2xl mx-auto py-8 px-4">
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <!-- Header -->
            <div class="bg-yellow-600 px-6 py-4">
                <h1 class="text-2xl font-bold text-white">Refund Processed</h1>
            </div>
            
            <!-- Content -->
            <div class="p-6">
                <p class="text-gray-700 mb-6">
                    Dear <strong>{{ $registration->first_name }}</strong>,
                </p>
                
                <p class="text-gray-700 mb-6">
                    This email confirms that a refund has been processed for your dental clinic registration 
                    for <strong>{{ $registration->clinic_name }}</strong>.
                </p>
                
                <!-- Refund Details -->
                <div class="bg-gray-50 rounded-lg p-4 mb-6">
                    <h2 class="text-sm font-semibold text-gray-700 mb-3">Refund Details</h2>
                    <table class="w-full text-sm">
                        <tr>
                            <td class="py-2 text-gray-600 font-medium w-40">Clinic Name:</td>
                            <td class="py-2 text-gray-900">{{ $registration->clinic_name }}</td>
                        </tr>
                        <tr>
                            <td class="py-2 text-gray-600 font-medium">Subdomain:</td>
                            <td class="py-2 text-gray-900">{{ $registration->subdomain }}</td>
                        </tr>
                        <tr>
                            <td class="py-2 text-gray-600 font-medium">Refund Amount:</td>
                            <td class="py-2 text-green-600 font-semibold">₱{{ number_format($refundAmount, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="py-2 text-gray-600 font-medium">Original Payment:</td>
                            <td class="py-2 text-gray-900">₱{{ number_format($registration->amount_paid, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="py-2 text-gray-600 font-medium">Reason:</td>
                            <td class="py-2 text-gray-900">Registration not approved within the required time limit</td>
                        </tr>
                    </table>
                </div>
                
                <!-- Notice -->
                <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700">
                                <strong>Refund Timeline</strong><br>
                                Please allow 5-10 business days for the refund to appear in your account. 
                                The refund will be credited to the same payment method used for the original transaction.
                            </p>
                        </div>
                    </div>
                </div>
                
                <p class="text-gray-600 text-sm mb-4">
                    If you have any questions about the refund, please contact our support team.
                </p>
                
                <p class="text-gray-600 text-sm">
                    Thank you for your understanding.
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
