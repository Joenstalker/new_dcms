<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import Swal from 'sweetalert2';

import logoImage from '../../../public/images/dcms-logo.png';

const props = defineProps({
    show: Boolean,
});

const emit = defineEmits(['close']);

const page = usePage();
const recaptchaSiteKey = computed(() => page.props.config?.recaptcha_site_key || '');

// Form state
const name = ref('');
const email = ref('');
const message = ref('');
const acceptTerms = ref(false);
const isSubmitting = ref(false);
const recaptchaToken = ref('');
const recaptchaWidgetId = ref(null);
const recaptchaContainer = ref(null);

const isFormValid = computed(() => {
    return name.value.trim().length >= 2 &&
           email.value.includes('@') &&
           message.value.trim().length >= 10 &&
           acceptTerms.value;
});

// Load reCAPTCHA v2 script
onMounted(() => {
    if (recaptchaSiteKey.value && !document.querySelector('#recaptcha-v2-script')) {
        const script = document.createElement('script');
        script.id = 'recaptcha-v2-script';
        script.src = 'https://www.google.com/recaptcha/api.js?onload=onRecaptchaLoad&render=explicit';
        script.async = true;
        script.defer = true;
        document.head.appendChild(script);
    }

// Render reCAPTCHA 
const initRecaptcha = () => {
    if (!window.grecaptcha || !recaptchaSiteKey.value) return;

    window.grecaptcha.ready(() => {
        const container = document.getElementById('contact-recaptcha-container');
        if (!container) return;

        // If it has children, it's already rendered in this DOM instance. Just reset it.
        if (container.hasChildNodes() && recaptchaWidgetId.value !== null) {
             try {
                 window.grecaptcha.reset(recaptchaWidgetId.value);
             } catch(e) {
                 console.warn("Could not reset", e);
             }
             return;
        }

        try {
            // Render freshly
            container.innerHTML = '';
            recaptchaWidgetId.value = window.grecaptcha.render(container, {
                sitekey: recaptchaSiteKey.value,
                callback: onRecaptchaSuccess,
                'expired-callback': onRecaptchaExpired,
                'error-callback': onRecaptchaError,
                theme: 'light',
                size: 'normal',
            });
        } catch (e) {
            console.warn('reCAPTCHA render error:', e);
        }
    });
};

// Handle modal opening
watch(() => props.show, (newVal) => {
    if (newVal) {
        recaptchaToken.value = '';
        
        // Use nextTick to ensure the v-if slot in Modal has rendered the DOM
        nextTick(() => {
            setTimeout(() => {
                initRecaptcha();
            }, 100);
        });
    }
});

    window.onRecaptchaLoad = () => {
        initRecaptcha();
    };

    // If already loaded (e.g., navigated back)
    if (window.grecaptcha) {
        initRecaptcha();
    }
});

const onRecaptchaSuccess = (token) => {
    recaptchaToken.value = token;
    // Token stored, wait for user to click Submit
};

const onRecaptchaExpired = () => {
    recaptchaToken.value = '';
    Swal.fire({
        icon: 'warning',
        title: 'reCAPTCHA Expired',
        text: 'Please verify the reCAPTCHA again.',
        confirmButtonColor: '#2B7CB3',
    });
};

const onRecaptchaError = () => {
    recaptchaToken.value = '';
    Swal.fire({
        icon: 'error',
        title: 'reCAPTCHA Error',
        text: 'Failed to load reCAPTCHA. Please refresh the page and try again.',
        confirmButtonColor: '#2B7CB3',
    });
};

// Make callbacks available globally for reCAPTCHA
window.onRecaptchaSuccess = onRecaptchaSuccess;
window.onRecaptchaExpired = onRecaptchaExpired;
window.onRecaptchaError = onRecaptchaError;

const submitForm = async () => {
    // Validate fields with SweetAlert
    if (name.value.trim().length < 2) {
        Swal.fire({ icon: 'warning', title: 'Name Required', text: 'Name is required.', confirmButtonColor: '#2B7CB3' });
        return;
    }
    if (!email.value.includes('@') || !email.value.includes('.')) {
        Swal.fire({ icon: 'warning', title: 'Email Required', text: 'A valid email address is required.', confirmButtonColor: '#2B7CB3' });
        return;
    }
    if (message.value.trim().length < 10) {
        Swal.fire({ icon: 'warning', title: 'Message Required', text: 'A message is required.', confirmButtonColor: '#2B7CB3' });
        return;
    }
    if (!acceptTerms.value) {
        Swal.fire({ icon: 'warning', title: 'Terms Required', text: 'Please check the box to accept the Terms & Conditions before sending.', confirmButtonColor: '#2B7CB3' });
        return;
    }

    // Verify reCAPTCHA was completed
    if (recaptchaSiteKey.value && window.grecaptcha && recaptchaWidgetId.value !== null) {
        if (!recaptchaToken.value) {
            Swal.fire({ icon: 'warning', title: 'reCAPTCHA Required', text: 'Please verify that you are not a robot.', confirmButtonColor: '#2B7CB3' });
            return;
        }
    }

    if (isSubmitting.value) return;

    actuallySubmit();
};

const actuallySubmit = async () => {
    if (isSubmitting.value) return;
    isSubmitting.value = true;

    // Show sending loading state (might replace the verifying one)
    Swal.fire({
        title: 'Sending your message...',
        text: 'Please wait while we process your request.',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    try {
        const response = await fetch('/contact', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify({
                name: name.value.trim(),
                email: email.value.trim(),
                message: message.value.trim(),
                recaptcha_token: recaptchaToken.value,
            })
        });

        const data = await response.json();

        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Message Sent!',
                text: 'Thank you for reaching out. We\'ll get back to you soon!',
                confirmButtonColor: '#2B7CB3',
                confirmButtonText: 'Great!',
            });

            // Reset form
            name.value = '';
            email.value = '';
            message.value = '';
            acceptTerms.value = false;
            recaptchaToken.value = '';
            if (window.grecaptcha && recaptchaWidgetId.value !== null) {
                window.grecaptcha.reset(recaptchaWidgetId.value);
            }
            emit('close');
        } else {
            // Reset reCAPTCHA on failure
            recaptchaToken.value = '';
            if (window.grecaptcha && recaptchaWidgetId.value !== null) {
                window.grecaptcha.ready(() => window.grecaptcha.reset(recaptchaWidgetId.value));
            }

            if (data.errors) {
                const firstError = Object.values(data.errors)[0];
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    text: Array.isArray(firstError) ? firstError[0] : firstError,
                    confirmButtonColor: '#2B7CB3',
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Failed to Send',
                    text: data.message || 'Something went wrong. Please try again.',
                    confirmButtonColor: '#2B7CB3',
                });
            }
        }
    } catch (error) {
        console.error('Contact form error:', error);
        recaptchaToken.value = '';
        if (window.grecaptcha && recaptchaWidgetId.value !== null) {
            window.grecaptcha.ready(() => window.grecaptcha.reset(recaptchaWidgetId.value));
        }
        Swal.fire({
            icon: 'error',
            title: 'Connection Error',
            text: 'Could not connect to the server. Please check your internet connection and try again.',
            confirmButtonColor: '#2B7CB3',
        });
    } finally {
        isSubmitting.value = false;
    }
};

const closeModal = () => {
    emit('close');
};
</script>

<template>
    <Modal :show="show" @close="closeModal" maxWidth="lg">
        <div class="relative flex flex-col max-h-[85vh] bg-white overflow-hidden">
            <!-- Top accent bar -->
            <div class="h-1.5 w-full shrink-0 bg-gradient-to-r from-[#2B7CB3] via-[#5EBD6A] to-[#2B7CB3]"></div>

            <div class="p-8 flex flex-col flex-1 min-h-0 overflow-y-auto custom-scrollbar">
                <!-- Logo & Header -->
                <div class="text-center mb-6 shrink-0">
                    <img :src="logoImage" alt="DCMS Logo" class="h-10 mx-auto mb-3">
                    <h2 class="text-2xl font-bold text-gray-900">Contact Us</h2>
                    <p class="text-sm text-gray-500 mt-1">We'd love to hear from you. Send us a message!</p>
                </div>

                <!-- Close Button -->
                <button @click="closeModal" class="absolute top-5 right-5 text-gray-400 hover:text-gray-600 transition-colors z-10 shrink-0">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <!-- Form -->
                <form @submit.prevent="submitForm" class="flex flex-col flex-1 gap-4">
                    <!-- Top section (Name & Email side-by-side) -->
                    <div class="flex flex-col sm:flex-row gap-4 shrink-0">
                        <!-- Name -->
                        <div class="flex-1">
                            <label for="contact_name" class="block text-sm font-semibold text-gray-700 mb-1">Name</label>
                            <input
                                id="contact_name"
                                type="text"
                                v-model="name"
                                placeholder="Your full name"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2B7CB3]/40 focus:border-[#2B7CB3] outline-none transition-colors text-sm"
                            />
                        </div>

                        <!-- Email -->
                        <div class="flex-1">
                            <label for="contact_email" class="block text-sm font-semibold text-gray-700 mb-1">Email</label>
                            <input
                                id="contact_email"
                                type="email"
                                v-model="email"
                                placeholder="your@email.com"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2B7CB3]/40 focus:border-[#2B7CB3] outline-none transition-colors text-sm"
                            />
                        </div>
                    </div>

                    <!-- Message (Scrollable area) -->
                    <div class="flex flex-col flex-1 min-h-[120px] mt-1">
                        <label for="contact_message" class="block text-sm font-semibold text-gray-700 mb-1 shrink-0">Message</label>
                        <textarea
                            id="contact_message"
                            v-model="message"
                            rows="4"
                            placeholder="Tell us what's on your mind..."
                            class="w-full flex-1 min-h-[100px] px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2B7CB3]/40 focus:border-[#2B7CB3] outline-none transition-colors text-sm resize-none custom-scrollbar"
                        ></textarea>
                    </div>

                    <!-- Bottom section (Fixed) -->
                    <div class="shrink-0 flex flex-col gap-4 mt-2">
                        <!-- Terms Checkbox -->
                        <label for="accept_terms_cb" class="flex items-start gap-3 cursor-pointer select-none group">
                            <div class="relative flex items-center justify-center w-5 h-5 mt-0.5 rounded border transition-colors duration-200 shadow-sm flex-shrink-0"
                                 :class="acceptTerms ? 'bg-[#2B7CB3] border-[#2B7CB3]' : 'bg-white border-gray-300 group-hover:border-[#2B7CB3]/50'">
                                <input
                                    id="accept_terms_cb"
                                    type="checkbox"
                                    v-model="acceptTerms"
                                    class="absolute opacity-0 w-0 h-0 cursor-pointer"
                                />
                                <svg v-if="acceptTerms" class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <span class="text-sm text-gray-600 leading-relaxed">
                                I accept the <a href="#" @click.prevent class="text-[#2B7CB3] hover:underline font-medium">Terms & Conditions</a> and <a href="#" @click.prevent class="text-[#2B7CB3] hover:underline font-medium">Privacy Policy</a>
                            </span>
                        </label>

                        <!-- reCAPTCHA v2 Container -->
                        <div class="flex justify-center">
                            <div id="contact-recaptcha-container"></div>
                        </div>

                        <!-- Submit Button -->
                        <button
                            type="submit"
                            :disabled="isSubmitting"
                            :class="[
                                'w-full py-2.5 px-6 rounded-lg font-bold text-white text-sm tracking-wide transition-all duration-200',
                                (!isSubmitting)
                                    ? 'bg-[#2B7CB3] hover:bg-[#24699A] shadow-lg shadow-[#2B7CB3]/25 hover:-translate-y-0.5'
                                    : 'bg-gray-300 cursor-not-allowed opacity-70'
                            ]"
                        >
                            <span v-if="isSubmitting" class="flex items-center justify-center gap-2">
                                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Sending...
                            </span>
                            <span v-else>Send Message</span>
                        </button>
                    </div>
                </form>

                <!-- reCAPTCHA Notice -->
                <p class="text-center text-[10px] text-gray-400 mt-3 shrink-0">
                    This form is protected by Google reCAPTCHA.
                </p>
            </div>
        </div>
    </Modal>
</template>
