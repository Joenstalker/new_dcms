<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clinic Suspended - DCMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen p-4">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl border border-red-100 p-8 text-center">
        <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-10 h-10 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Clinic Suspended</h1>
        <p class="text-gray-600 mb-8">Access to this platform has been suspended by the administrator. Please contact support to resolve this issue.</p>
        
        <button onclick="openModal()" class="inline-block bg-red-600 hover:bg-red-700 text-white font-semibold px-6 py-3 rounded-lg transition-colors w-full shadow-lg shadow-red-100">
            Contact Support
        </button>
    </div>

    <!-- Contact Modal -->
    <div id="contactModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center p-4 z-50">
        <div class="bg-white rounded-2xl max-w-lg w-full overflow-hidden shadow-2xl">
            <div class="h-1.5 w-full bg-gradient-to-r from-red-500 to-red-600"></div>
            <div class="p-8">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Support Ticket</h2>
                        <p class="text-sm text-gray-500">Send a message to the administrator.</p>
                    </div>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                
                <form id="contactForm" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Name</label>
                        <input type="text" name="name" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-red-500/20 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-red-500/20 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Message</label>
                        <textarea name="message" rows="4" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-red-500/20 outline-none resize-none"></textarea>
                    </div>
                    
                    <div class="flex justify-center py-2">
                        <div id="recaptcha-container"></div>
                    </div>

                    <button type="submit" id="submitBtn" class="w-full bg-red-600 text-white font-bold py-3 rounded-lg hover:bg-red-700 transition-all">
                        Send Message
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://www.google.com/recaptcha/api.js?render=explicit" async defer></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        let recaptchaWidgetId = null;
        const siteKey = "{{ config('services.recaptcha.site_key') }}";

        function openModal() {
            const modal = document.getElementById('contactModal');
            modal.style.display = 'flex';
            modal.classList.remove('hidden');
            if (recaptchaWidgetId === null && window.grecaptcha) {
                recaptchaWidgetId = grecaptcha.render('recaptcha-container', {
                    'sitekey': siteKey,
                    'theme': 'light'
                });
            }
        }

        function closeModal() {
            const modal = document.getElementById('contactModal');
            modal.style.display = 'none';
            modal.classList.add('hidden');
        }

        document.getElementById('contactForm').onsubmit = async (e) => {
            e.preventDefault();
            const btn = document.getElementById('submitBtn');
            const originalText = btn.innerText;
            
            const token = grecaptcha.getResponse(recaptchaWidgetId);
            if (!token) {
                Swal.fire({ icon: 'warning', text: 'Please complete the reCAPTCHA' });
                return;
            }

            btn.disabled = true;
            btn.innerText = 'Sending...';

            const formData = new FormData(e.target);
            formData.append('recaptcha_token', token);

            try {
                const response = await fetch('/contact-support', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                
                const result = await response.json();
                if (result.success) {
                    Swal.fire({ icon: 'success', title: 'Sent!', text: result.message });
                    closeModal();
                    e.target.reset();
                    grecaptcha.reset(recaptchaWidgetId);
                } else {
                    Swal.fire({ icon: 'error', title: 'Oops!', text: result.message || 'Validation failed' });
                    grecaptcha.reset(recaptchaWidgetId);
                }
            } catch (err) {
                Swal.fire({ icon: 'error', text: 'Connection failed' });
            } finally {
                btn.disabled = false;
                btn.innerText = originalText;
            }
        };
    </script>
</body>
</html>
