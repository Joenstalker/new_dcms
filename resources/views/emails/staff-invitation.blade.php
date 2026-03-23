<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitation to join {{ $tenantName }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">
    <div class="max-w-2xl mx-auto py-8 px-4">
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <!-- Header -->
            <div class="bg-blue-600 px-6 py-4">
                <h1 class="text-2xl font-bold text-white">🦷 Welcome to the Team!</h1>
            </div>
            
            <!-- Content -->
            <div class="p-6">
                <p class="text-gray-700 mb-6">
                    Hello <strong>{{ $name }}</strong>,
                </p>
                
                <p class="text-gray-700 mb-6">
                    You have been added as a <strong>{{ $role }}</strong> to <strong>{{ $tenantName }}</strong>. You can now access your portal using the credentials below.
                </p>
                
                <!-- Login Details -->
                <h2 class="text-lg font-semibold text-gray-900 mb-3">Your Login Credentials</h2>
                <div class="bg-gray-50 rounded-lg p-4 mb-6">
                    <table class="w-full text-sm">
                        <tr>
                            <td class="py-2 text-gray-600 font-medium w-32">URL:</td>
                            <td class="py-2">
                                <a href="{{ $tenantUrl }}" class="text-blue-600 hover:text-blue-800 underline font-semibold">
                                    {{ $tenantUrl }}
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td class="py-2 text-gray-600 font-medium">Username:</td>
                            <td class="py-2 text-gray-900 font-semibold">{{ $name }}</td>
                        </tr>
                        <tr>
                            <td class="py-2 text-gray-600 font-medium">Email:</td>
                            <td class="py-2 text-gray-900">{{ $email }}</td>
                        </tr>
                        <tr>
                            <td class="py-2 text-gray-600 font-medium">Password:</td>
                            <td class="py-2 text-gray-900 font-mono font-bold text-lg text-blue-700">{{ $password }}</td>
                        </tr>
                    </table>
                </div>
                
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                    <p class="text-sm text-yellow-700">
                        <strong>Important Security Note:</strong><br>
                        Please change your password immediately after your first login for security purposes.
                    </p>
                </div>
                
                <p class="text-gray-600 text-sm mb-4">
                    If you have any questions or need assistance setting up your portal, please contact your administrator.
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
