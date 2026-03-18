<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Rejected</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">
    <div class="max-w-2xl mx-auto py-8 px-4">
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <!-- Header -->
            <div class="bg-red-600 px-6 py-4">
                <h1 class="text-2xl font-bold text-white">Application Status: Rejected</h1>
            </div>
            
            <!-- Content -->
            <div class="p-6">
                <p class="text-gray-700 mb-6">
                    Dear <strong>{{ $registration->first_name }}</strong>,
                </p>
                
                <p class="text-gray-700 mb-6">
                    Thank you for your interest in Dental Clinic Management System. Unfortunately, your application for 
                    <strong>{{ $registration->clinic_name }}</strong> has been rejected.
                </p>
                
                <!-- Rejection Notice -->
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700">
                                <strong>Application Not Approved</strong><br>
                                Your clinic application did not meet our requirements at this time.
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Admin Message -->
                @if($rejectionMessage)
                <div class="bg-gray-50 rounded-lg p-4 mb-6">
                    <h2 class="text-sm font-semibold text-gray-700 mb-2">Message from Administrator:</h2>
                    <p class="text-gray-700 text-sm">{{ $rejectionMessage }}</p>
                </div>
                @endif
                
                <!-- Refund Notice -->
                <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700">
                                <strong>Refund Information</strong><br>
                                A refund for your payment has been processed. Please allow 5-10 business days for the refund to appear in your account.
                            </p>
                        </div>
                    </div>
                </div>
                
                <p class="text-gray-600 text-sm mb-4">
                    If you believe this decision was made in error or would like to reapply in the future, please contact our support team.
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
