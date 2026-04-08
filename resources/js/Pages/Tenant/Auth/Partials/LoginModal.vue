<script setup>
import { ref, computed, watch, onUnmounted, onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps({
    show: Boolean,
    tenant: Object,
    recaptchaSiteKey: String,
});

const emit = defineEmits(['close']);

// Steps: 'login', 'forgot', 'verify-code', 'new-password', 'reset-sent'
const step = ref('login'); 
const isLoading = ref(false);

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const forgotForm = useForm({
    email: '',
    code: '',
    password: '',
    password_confirmation: '',
});

const recaptchaTokenLogin = ref('');
const recaptchaTokenForgot = ref('');
const recaptchaLoginRef = ref(null);
const recaptchaForgotRef = ref(null);
const recaptchaError = ref(false);
const lockoutCountdownInterval = ref(null);

const csrfHeaders = () => ({
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
});

const clearLockoutCountdown = () => {
    if (lockoutCountdownInterval.value) {
        clearInterval(lockoutCountdownInterval.value);
        lockoutCountdownInterval.value = null;
    }
};

const formatCountdown = (totalSeconds) => {
    const seconds = Math.max(Number(totalSeconds) || 0, 0);
    const minutesPart = Math.floor(seconds / 60);
    const secondsPart = seconds % 60;

    return `${minutesPart}:${String(secondsPart).padStart(2, '0')}`;
};

const showLockoutAlert = (seconds) => {
    clearLockoutCountdown();

    let remaining = Math.max(Number(seconds) || 0, 0);

    Swal.fire({
        icon: 'warning',
        title: '429 Too Many Requests',
        html: `
            <div style="text-align:center;line-height:1.7;">
                <div>Try again in:</div>
                <div id="tenant-lockout-countdown" style="font-size:1.8rem;font-weight:700;color:#dc2626;">${formatCountdown(remaining)}</div>
            </div>
        `,
        confirmButtonColor: brandingColor.value,
        confirmButtonText: 'OK',
        allowOutsideClick: false,
        didOpen: () => {
            const el = document.getElementById('tenant-lockout-countdown');

            lockoutCountdownInterval.value = setInterval(() => {
                remaining = Math.max(remaining - 1, 0);

                if (el) {
                    el.textContent = formatCountdown(remaining);
                }

                if (remaining <= 0) {
                    clearLockoutCountdown();
                }
            }, 1000);
        },
        willClose: () => {
            clearLockoutCountdown();
        },
    });
};

// Remember email in localStorage
const savedEmail = ref('');

// Load scripts on mount
onMounted(() => {
    // Load Google GIS script
    const script = document.createElement('script');
    script.src = 'https://accounts.google.com/gsi/client';
    script.async = true;
    script.defer = true;
    document.head.appendChild(script);

    // reCAPTCHA logic...
    const checkRecaptcha = setInterval(() => {
        if (window.grecaptcha && props.recaptchaSiteKey) {
            clearInterval(checkRecaptcha);
            renderRecaptcha();
        }
    }, 100);
    
    // Timeout after 5 seconds
    setTimeout(() => clearInterval(checkRecaptcha), 5000);
});

watch(() => props.show, (newVal) => {
    if (newVal) {
        document.body.style.overflow = 'hidden';
        // Load saved email
        savedEmail.value = localStorage.getItem('rememberClinicEmail') || '';
        if (savedEmail.value) {
            form.email = savedEmail.value;
        }
        
        // Render reCAPTCHA when modal shows
        setTimeout(() => {
            if (window.grecaptcha && props.recaptchaSiteKey) {
                renderRecaptcha();
            }
        }, 200);
    } else {
        document.body.style.overflow = '';
    }
});

onUnmounted(() => {
    document.body.style.overflow = '';
    clearLockoutCountdown();
});

const brandingColor = computed(() => props.tenant?.branding_color || '#3b82f6');
const logoUrl = computed(() => {
    const logopath = props.tenant?.logo_login_path || props.tenant?.logo_path;
    if (!logopath) return null;
    
    // If it's already a full URL or a relative path starting with / (like our branding route)
    if (logopath.startsWith('http') || logopath.startsWith('/')) {
        return logopath;
    }
    
    return `/tenant-storage/${logopath}`;
});
const fontFamily = computed(() => props.tenant?.font_family || 'font-sans');

const renderRecaptcha = () => {
    recaptchaError.value = false;
    if (step.value === 'login' && recaptchaLoginRef.value) {
        if (recaptchaLoginRef.value.children.length === 0 && window.grecaptcha) {
            try {
                window.grecaptcha.render(recaptchaLoginRef.value, {
                    sitekey: props.recaptchaSiteKey,
                    callback: onRecaptchaLoginVerify,
                    'expired-callback': onRecaptchaLoginExpire,
                    'error-callback': () => { recaptchaError.value = true; },
                    theme: 'light'
                });
            } catch (e) {
                console.error('reCAPTCHA render error:', e);
                recaptchaError.value = true;
            }
        }
    } else if (step.value === 'forgot' && recaptchaForgotRef.value) {
        if (recaptchaForgotRef.value.children.length === 0 && window.grecaptcha) {
            try {
                window.grecaptcha.render(recaptchaForgotRef.value, {
                    sitekey: props.recaptchaSiteKey,
                    callback: onRecaptchaForgotVerify,
                    'expired-callback': onRecaptchaForgotExpire,
                    'error-callback': () => { recaptchaError.value = true; },
                    theme: 'light'
                });
            } catch (e) {
                console.error('reCAPTCHA render error:', e);
                recaptchaError.value = true;
            }
        }
    }
};

const onRecaptchaLoginVerify = (token) => {
    recaptchaTokenLogin.value = token;
};

const onRecaptchaLoginExpire = () => {
    recaptchaTokenLogin.value = '';
};

const onRecaptchaForgotVerify = (token) => {
    recaptchaTokenForgot.value = token;
};

const onRecaptchaForgotExpire = () => {
    recaptchaTokenForgot.value = '';
};

const submitLogin = async () => {
    if (!recaptchaTokenLogin.value) {
        Swal.fire({
            icon: 'warning',
            title: 'Verification Required',
            text: 'Please verify the reCAPTCHA.',
            confirmButtonColor: brandingColor.value,
        });
        return;
    }

    if (form.remember) {
        localStorage.setItem('rememberClinicEmail', form.email);
    } else {
        localStorage.removeItem('rememberClinicEmail');
    }

    isLoading.value = true;
    
    try {
        const response = await axios.post(route('tenant.login.store'), {
            email: form.email,
            password: form.password,
            remember: form.remember,
            'g-recaptcha-response': recaptchaTokenLogin.value,
        }, {
            headers: csrfHeaders(),
        });

        if (response.data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Login Successful',
                text: 'Redirecting to your dashboard...',
                timer: 1500,
                showConfirmButton: false,
            }).then(() => {
                window.location.href = response.data.redirect;
            });
        }
    } catch (error) {
        const lockoutSeconds = Number(error.response?.data?.lockout_seconds || 0);
        if (error.response?.status === 429 && lockoutSeconds > 0) {
            showLockoutAlert(lockoutSeconds);
            return;
        }

        Swal.fire({
            icon: 'error',
            title: 'Login Failed',
            text: error.response?.data?.message || 'Invalid email or password',
            confirmButtonColor: brandingColor.value,
        });
        recaptchaTokenLogin.value = '';
        if (window.grecaptcha && recaptchaLoginRef.value) {
            window.grecaptcha.reset();
        }
    } finally {
        isLoading.value = false;
    }
};

const submitSendCode = async () => {
    if (!recaptchaTokenForgot.value) {
        Swal.fire({
            icon: 'warning',
            title: 'Verification Required',
            text: 'Please verify the reCAPTCHA.',
            confirmButtonColor: brandingColor.value,
        });
        return;
    }

    isLoading.value = true;
    
    try {
        const response = await axios.post(route('tenant.password.send-code'), {
            email: forgotForm.email,
            'g-recaptcha-response': recaptchaTokenForgot.value,
        }, {
            headers: csrfHeaders(),
        });

        if (response.data.success) {
            step.value = 'verify-code';
            Swal.fire({
                icon: 'success',
                title: 'Code Sent!',
                text: 'Please check your email for the 6-digit verification code.',
                timer: 2000,
                showConfirmButton: false,
            });
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.response?.data?.message || 'Unable to send reset code',
            confirmButtonColor: brandingColor.value,
        });
    } finally {
        isLoading.value = false;
    }
};

const submitVerifyCode = async () => {
    if (forgotForm.code.length !== 6) return;
    
    isLoading.value = true;
    try {
        const response = await axios.post(route('tenant.password.verify-code'), {
            email: forgotForm.email,
            code: forgotForm.code,
        }, {
            headers: csrfHeaders(),
        });

        if (response.data.success) {
            step.value = 'new-password';
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Invalid Code',
            text: error.response?.data?.message || 'The code you entered is invalid or expired.',
            confirmButtonColor: brandingColor.value,
        });
    } finally {
        isLoading.value = false;
    }
};

const submitResetPassword = async () => {
    if (!forgotForm.password || forgotForm.password.length < 8) {
        Swal.fire({
            icon: 'warning',
            title: 'Invalid Password',
            text: 'Password must be at least 8 characters.',
            confirmButtonColor: brandingColor.value,
        });
        return;
    }

    if (forgotForm.password !== forgotForm.password_confirmation) {
        Swal.fire({
            icon: 'warning',
            title: 'Mismatch',
            text: 'Passwords do not match.',
            confirmButtonColor: brandingColor.value,
        });
        return;
    }

    isLoading.value = true;
    try {
        const response = await axios.post(route('tenant.password.reset-with-code'), {
            email: forgotForm.email,
            code: forgotForm.code,
            password: forgotForm.password,
            password_confirmation: forgotForm.password_confirmation,
        }, {
            headers: csrfHeaders(),
        });

        if (response.data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Password Updated!',
                text: 'Your password has been successfully reset. You can now login.',
                confirmButtonColor: brandingColor.value,
            }).then(() => {
                goBackToLogin();
            });
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Reset Failed',
            text: error.response?.data?.message || 'Unable to reset password.',
            confirmButtonColor: brandingColor.value,
        });
    } finally {
        isLoading.value = false;
    }
};

const goToForgot = () => {
    step.value = 'forgot';
    recaptchaTokenForgot.value = '';
    recaptchaTokenLogin.value = '';
    if (window.grecaptcha && recaptchaLoginRef.value) {
        window.grecaptcha.reset();
    }
    setTimeout(() => renderRecaptcha(), 50);
};

const goBackToLogin = () => {
    step.value = 'login';
    recaptchaTokenForgot.value = '';
    recaptchaTokenLogin.value = '';
    forgotForm.reset();
    if (window.grecaptcha && recaptchaForgotRef.value) {
        window.grecaptcha.reset();
    }
    setTimeout(() => renderRecaptcha(), 50);
};

// Auto-verify when 6 digits are entered
watch(() => forgotForm.code, (newVal) => {
    if (newVal && newVal.length === 6 && step.value === 'verify-code') {
        submitVerifyCode();
    }
});

const resetForm = () => {
    if (step.value === 'login') form.reset();
    step.value = 'login';
    recaptchaTokenLogin.value = '';
    recaptchaTokenForgot.value = '';
};

const close = () => {
    clearLockoutCountdown();
    emit('close');
    setTimeout(() => resetForm(), 300);
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6" :class="fontFamily" role="dialog" aria-modal="true">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-md transition-opacity duration-300" @click="close"></div>

        <!-- Modal Container -->
        <div class="relative w-full max-w-md transform flex flex-col bg-white rounded-3xl shadow-[0_20px_50px_rgba(0,0,0,0.1)] transition-all animate-in fade-in zoom-in duration-300 max-h-[calc(100vh-2rem)] sm:max-h-[min(90vh,750px)]">
            
            <!-- Sticky Header -->
            <div class="px-8 py-4 flex justify-between items-center border-b border-slate-100 flex-shrink-0">
                <div class="flex flex-col gap-0.5">
                    <div v-if="logoUrl" class="mb-1">
                        <img :src="logoUrl" :alt="tenant?.name" class="h-8 w-full object-contain object-left">
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 leading-tight" v-if="step === 'login'">Clinic Access</h3>
                    <h3 class="text-lg font-bold text-slate-900 leading-tight" v-else-if="step === 'forgot'">Reset Password</h3>
                    <h3 class="text-lg font-bold text-slate-900 leading-tight" v-else-if="step === 'verify-code'">Verify Code</h3>
                    <h3 class="text-lg font-bold text-slate-900 leading-tight" v-else>New Password</h3>
                    
                    <p class="text-xs text-slate-500 font-medium">
                        {{ step === 'login' ? 'Staff Portal •' : '' }} {{ tenant?.name }}
                    </p>
                </div>
                <button @click="close" class="p-2 rounded-full hover:bg-slate-50 text-slate-400 hover:text-slate-600 transition-colors group">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Scrollable Content -->
            <div class="px-8 py-6 overflow-y-auto custom-scrollbar flex-grow">
                <!-- Login Form -->
                <div v-if="step === 'login'" class="space-y-4">
                    <div>
                        <label class="block text-[12px] font-bold text-slate-700 mb-1 ml-1">Email Address</label>
                        <input 
                            v-model="form.email" 
                            type="email" 
                            required 
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 p-3.5 transition-all text-sm font-medium" 
                            :style="{ '--tw-ring-color': brandingColor }"
                            placeholder="name@clinic.com"
                        >
                    </div>

                    <div>
                        <label class="block text-[12px] font-bold text-slate-700 mb-1 ml-1">Password</label>
                        <input 
                            v-model="form.password" 
                            type="password" 
                            required 
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 p-3.5 transition-all text-sm font-medium" 
                            :style="{ '--tw-ring-color': brandingColor }"
                            placeholder="••••••••"
                        >
                    </div>

                    <!-- Forgot Password Link -->
                    <div class="flex justify-end -mt-3">
                        <button @click="goToForgot" type="button" class="text-xs font-bold transition-colors hover:opacity-80" :style="{ color: brandingColor }">
                            Forgot password?
                        </button>
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center group cursor-pointer" @click="form.remember = !form.remember">
                        <div class="relative flex items-center">
                            <input 
                                v-model="form.remember" 
                                type="checkbox" 
                                id="remember" 
                                class="w-4 h-4 rounded-md border-slate-200 transition-all cursor-pointer"
                                :style="{ 'accent-color': brandingColor }"
                                @click.stop
                            >
                        </div>
                        <label for="remember" class="ml-2.5 text-[13px] text-slate-600 font-medium cursor-pointer select-none">Remember this email</label>
                    </div>

                    <!-- reCAPTCHA -->
                    <div class="flex flex-col gap-2">
                        <div v-if="recaptchaError" class="text-center p-4 bg-orange-50 border border-orange-100 rounded-xl space-y-2">
                            <p class="text-[11px] text-orange-700 font-medium">Verification failed to load. Please check your connection.</p>
                            <button @click="renderRecaptcha" class="text-xs font-bold text-orange-600 hover:text-orange-800 underline active:scale-95 transition-all">
                                Retry Verification
                            </button>
                        </div>
                        <div v-show="!recaptchaError" class="flex justify-center bg-slate-50 p-2 rounded-xl border border-dashed border-slate-200">
                            <div ref="recaptchaLoginRef" class="scale-[0.85] origin-center h-[65px] flex items-center overflow-hidden"></div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button 
                        @click="submitLogin"
                        :disabled="isLoading"
                        class="w-full py-3.5 text-white font-bold text-base rounded-xl shadow-[0_10px_20px_rgba(0,0,0,0.1)] hover:shadow-[0_15px_30px_rgba(0,0,0,0.15)] transition-all active:scale-[0.98] disabled:opacity-50 flex items-center justify-center gap-2" 
                        :style="{ backgroundColor: brandingColor }">
                        <span v-if="isLoading" class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                        {{ isLoading ? 'Entering Portal...' : 'Login to the Clinic' }}
                    </button>
                </div>

                <!-- Forgot Password Form -->
                <div v-if="step === 'forgot'" class="space-y-4">
                    <div class="bg-blue-50/50 p-3 rounded-xl border border-blue-100/50">
                        <p class="text-[12px] text-blue-700 font-bold leading-tight">
                            Recovery Access
                        </p>
                        <p class="text-[11px] text-blue-600 font-medium leading-relaxed mt-1">
                            Enter your email to receive a secure 6-digit verification code.
                        </p>
                    </div>

                    <div>
                        <label class="block text-[12px] font-bold text-slate-700 mb-1 ml-1">Email Address</label>
                        <input 
                            v-model="forgotForm.email" 
                            type="email" 
                            required 
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 p-3.5 transition-all text-sm font-medium" 
                            :style="{ '--tw-ring-color': brandingColor }"
                            placeholder="name@clinic.com"
                        >
                    </div>

                    <!-- reCAPTCHA -->
                    <div class="flex flex-col gap-2">
                        <div v-if="recaptchaError" class="text-center p-4 bg-orange-50 border border-orange-100 rounded-xl space-y-2">
                            <p class="text-[11px] text-orange-700 font-medium">Verification failed to load. Please check your connection.</p>
                            <button @click="renderRecaptcha" class="text-xs font-bold text-orange-600 hover:text-orange-800 underline active:scale-95 transition-all">
                                Retry Verification
                            </button>
                        </div>
                        <div v-show="!recaptchaError" class="flex justify-center bg-slate-50 p-2 rounded-xl border border-dashed border-slate-200">
                            <div ref="recaptchaForgotRef" class="scale-[0.85] origin-center h-[65px] flex items-center overflow-hidden"></div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button 
                        @click="submitSendCode"
                        :disabled="isLoading"
                        class="w-full py-3.5 text-white font-bold text-base rounded-xl shadow-[0_10px_20px_rgba(0,0,0,0.1)] hover:shadow-[0_15px_30px_rgba(0,0,0,0.15)] transition-all active:scale-[0.98] disabled:opacity-50 flex items-center justify-center gap-2" 
                        :style="{ backgroundColor: brandingColor }">
                        <span v-if="isLoading" class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                        {{ isLoading ? 'Sending Code...' : 'Send Verification Code' }}
                    </button>

                    <!-- Back Link -->
                    <div class="text-center">
                        <button @click="goBackToLogin" class="text-xs font-bold text-slate-400 hover:text-slate-600 transition-colors flex items-center justify-center gap-2 mx-auto">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            Back to login
                        </button>
                    </div>
                </div>

                <!-- Verify Code Form -->
                <div v-if="step === 'verify-code'" class="space-y-6 text-center">
                    <div class="space-y-2">
                        <h4 class="text-base font-bold text-slate-900">Enter Verification Code</h4>
                        <p class="text-xs text-slate-500">We've sent a 6-digit code to <br><span class="font-bold text-slate-700">{{ forgotForm.email }}</span></p>
                    </div>

                    <div class="relative max-w-[240px] mx-auto">
                        <input 
                            v-model="forgotForm.code"
                            type="text"
                            maxlength="6"
                            class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl p-4 text-center text-3xl font-black tracking-[0.5em] focus:border-blue-500 focus:ring-0 transition-all"
                            :style="{ borderColor: brandingColor }"
                            placeholder="000000"
                        >
                    </div>

                    <button 
                        @click="submitVerifyCode"
                        :disabled="isLoading || forgotForm.code.length !== 6"
                        class="w-full py-3.5 text-white font-bold text-base rounded-xl shadow-lg transition-all active:scale-[0.98] disabled:opacity-50" 
                        :style="{ backgroundColor: brandingColor }">
                        {{ isLoading ? 'Verifying...' : 'Verify Code' }}
                    </button>

                    <button @click="step = 'forgot'" class="text-xs font-bold text-slate-400 hover:text-slate-600">
                        Didn't get the code? Resend
                    </button>
                </div>

                <!-- New Password Form -->
                <div v-if="step === 'new-password'" class="space-y-4">
                    <div class="bg-emerald-50/50 p-3 rounded-xl border border-emerald-100/50">
                        <p class="text-[12px] text-emerald-700 font-bold leading-tight">
                            Identity Verified
                        </p>
                        <p class="text-[11px] text-emerald-600 font-medium leading-relaxed mt-1">
                            Choose a strong new password for your account.
                        </p>
                    </div>

                    <div>
                        <label class="block text-[12px] font-bold text-slate-700 mb-1 ml-1">New Password</label>
                        <input 
                            v-model="forgotForm.password" 
                            type="password" 
                            required 
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 p-3.5 transition-all text-sm font-medium" 
                            :style="{ '--tw-ring-color': brandingColor }"
                            placeholder="••••••••"
                        >
                    </div>

                    <div>
                        <label class="block text-[12px] font-bold text-slate-700 mb-1 ml-1">Confirm Password</label>
                        <input 
                            v-model="forgotForm.password_confirmation" 
                            type="password" 
                            required 
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 p-3.5 transition-all text-sm font-medium" 
                            :style="{ '--tw-ring-color': brandingColor }"
                            placeholder="••••••••"
                        >
                    </div>

                    <button 
                        @click="submitResetPassword"
                        :disabled="isLoading"
                        class="w-full py-3.5 text-white font-bold text-base rounded-xl shadow-lg transition-all active:scale-[0.98] disabled:opacity-50" 
                        :style="{ backgroundColor: brandingColor }">
                        {{ isLoading ? 'Updating Password...' : 'Reset Password' }}
                    </button>
                </div>

            </div>
        </div>
    </div>
</template>

<style scoped>
.animate-in {
    animation-duration: 0.3s;
}

@keyframes fade-in {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes zoom-in {
    from { transform: scale(0.95); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}

.fade-in { animation-name: fade-in; }
.zoom-in { animation-name: zoom-in; }

.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #cbd5e1;
}
</style>
