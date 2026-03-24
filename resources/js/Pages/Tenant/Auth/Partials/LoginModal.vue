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

const step = ref('login'); // 'login', 'forgot', 'reset-sent'
const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const forgotForm = useForm({
    email: '',
});

const recaptchaTokenLogin = ref('');
const recaptchaTokenForgot = ref('');
const isLoading = ref(false);
const recaptchaLoginRef = ref(null);
const recaptchaForgotRef = ref(null);

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

    script.onload = () => {
        if (props.googleClientId) {
            initializeGoogleSignIn();
        }
    };

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
});

const brandingColor = computed(() => props.tenant?.branding_color || '#3b82f6');
const logoUrl = computed(() => {
    const logopath = props.tenant?.logo_login_path || props.tenant?.logo_path;
    return logopath ? `/storage/${logopath}` : null;
});
const fontFamily = computed(() => props.tenant?.font_family || 'font-sans');

const renderRecaptcha = () => {
    if (step.value === 'login' && recaptchaLoginRef.value) {
        // Only render if not already rendered (check if div is empty)
        if (recaptchaLoginRef.value.children.length === 0 && window.grecaptcha) {
            try {
                window.grecaptcha.render(recaptchaLoginRef.value, {
                    sitekey: props.recaptchaSiteKey,
                    callback: onRecaptchaLoginVerify,
                    'expired-callback': onRecaptchaLoginExpire,
                    theme: 'light'
                });
            } catch (e) {
                console.error('reCAPTCHA render error:', e);
            }
        }
    } else if (step.value === 'forgot' && recaptchaForgotRef.value) {
        // Only render if not already rendered
        if (recaptchaForgotRef.value.children.length === 0 && window.grecaptcha) {
            try {
                window.grecaptcha.render(recaptchaForgotRef.value, {
                    sitekey: props.recaptchaSiteKey,
                    callback: onRecaptchaForgotVerify,
                    'expired-callback': onRecaptchaForgotExpire,
                    theme: 'light'
                });
            } catch (e) {
                console.error('reCAPTCHA render error:', e);
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
    console.log('submitLogin triggered');
    if (!recaptchaTokenLogin.value) {
        console.log('reCAPTCHA missing');
        Swal.fire({
            icon: 'warning',
            title: 'Verification Required',
            text: 'Please verify the reCAPTCHA.',
            confirmButtonColor: brandingColor.value,
        });
        return;
    }

    // Save email if remember is checked
    if (form.remember) {
        localStorage.setItem('rememberClinicEmail', form.email);
    } else {
        localStorage.removeItem('rememberClinicEmail');
    }

    isLoading.value = true;
    console.log('Attempting login for:', form.email);
    
    try {
        const response = await axios.post(route('tenant.login.store'), {
            email: form.email,
            password: form.password,
            remember: form.remember,
            'g-recaptcha-response': recaptchaTokenLogin.value,
        });

        if (response.data.success) {
            console.log('Login success response:', response.data);
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
        console.error('Login error details:', error);
        Swal.fire({
            icon: 'error',
            title: 'Login Failed',
            text: error.response?.data?.message || 'Invalid email or password',
            confirmButtonColor: brandingColor.value,
        });
        // Reset recaptcha on error
        recaptchaTokenLogin.value = '';
        if (window.grecaptcha && recaptchaLoginRef.value) {
            window.grecaptcha.reset();
        }
    } finally {
        isLoading.value = false;
    }
};

const submitForgot = async () => {
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
        const response = await axios.post(route('tenant.password.email'), {
            email: forgotForm.email,
            'g-recaptcha-response': recaptchaTokenForgot.value,
        });

        if (response.data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Sent!',
                text: 'Check your email for the password reset link.',
                confirmButtonColor: brandingColor.value,
            }).then(() => {
                step.value = 'reset-sent';
                recaptchaTokenForgot.value = '';
                if (window.grecaptcha && recaptchaForgotRef.value) {
                    window.grecaptcha.reset();
                }
            });
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.response?.data?.message || 'Unable to send reset link',
            confirmButtonColor: brandingColor.value,
        });
        recaptchaTokenForgot.value = '';
        if (window.grecaptcha && recaptchaForgotRef.value) {
            window.grecaptcha.reset();
        }
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
    // Render forgot recaptcha
    setTimeout(() => {
        renderRecaptcha();
    }, 50);
};

const goBackToLogin = () => {
    step.value = 'login';
    recaptchaTokenForgot.value = '';
    recaptchaTokenLogin.value = '';
    forgotForm.reset();
    if (window.grecaptcha && recaptchaForgotRef.value) {
        window.grecaptcha.reset();
    }
    // Render login recaptcha
    setTimeout(() => {
        renderRecaptcha();
    }, 50);
};

const resetForm = () => {
    if (step.value === 'login') {
        form.reset();
    }
    step.value = 'login';
    recaptchaTokenLogin.value = '';
    recaptchaTokenForgot.value = '';
};




const close = () => {
    emit('close');
    setTimeout(() => {
        resetForm();
    }, 300);
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-[100] overflow-y-auto" :class="fontFamily" role="dialog" aria-modal="true">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="close"></div>

        <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-[2.5rem] bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-md animate-in fade-in zoom-in duration-300">
                
                <!-- Header -->
                <div class="px-8 pt-8 pb-4 flex justify-between items-start border-b border-gray-100">
                    <div>
                        <!-- Logo or Clinic Name -->
                        <div v-if="logoUrl" class="mb-4">
                            <img :src="logoUrl" :alt="tenant?.name" class="h-12 object-contain">
                        </div>
                        <h3 class="text-2xl font-black text-gray-900" v-if="step === 'login'">Clinic Login</h3>
                        <h3 class="text-2xl font-black text-gray-900" v-else-if="step === 'forgot'">Reset Password</h3>
                        <h3 class="text-2xl font-black text-gray-900" v-else>Check Your Email</h3>
                        <p class="text-sm text-gray-500 font-medium mt-1">
                            {{ step === 'login' ? 'Staff login for ' : '' }}{{ tenant?.name }}
                        </p>
                    </div>
                    <button @click="close" class="text-gray-400 hover:text-gray-600">
                        <span class="text-2xl">✕</span>
                    </button>
                </div>

                <!-- Content -->
                <div class="px-8 py-8">
                    <!-- Login Form -->
                    <div v-if="step === 'login'" class="space-y-6">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Email Address</label>
                            <input 
                                v-model="form.email" 
                                type="email" 
                                required 
                                class="w-full bg-gray-50 border-none rounded-2xl focus:ring-2 p-4 transition-all" 
                                :style="{ '--tw-ring-color': brandingColor }"
                                placeholder="your@email.com"
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Password</label>
                            <input 
                                v-model="form.password" 
                                type="password" 
                                required 
                                class="w-full bg-gray-50 border-none rounded-2xl focus:ring-2 p-4 transition-all" 
                                :style="{ '--tw-ring-color': brandingColor }"
                                placeholder="Enter your password"
                            >
                        </div>

                        <!-- Remember Me -->
                        <div class="flex items-center">
                            <input 
                                v-model="form.remember" 
                                type="checkbox" 
                                id="remember" 
                                class="w-4 h-4 rounded"
                                :style="{ 'accent-color': brandingColor }"
                            >
                            <label for="remember" class="ml-2 text-sm text-gray-600">Remember email</label>
                        </div>

                        <!-- reCAPTCHA -->
                        <div class="flex justify-center">
                            <div ref="recaptchaLoginRef"></div>
                        </div>

                        <!-- Submit Button -->
                        <button 
                            @click="submitLogin"
                            :disabled="isLoading"
                            class="w-full py-4 text-white font-black text-lg rounded-full shadow-lg hover:shadow-xl transition-all disabled:opacity-50" 
                            :style="{ backgroundColor: brandingColor }">
                            {{ isLoading ? 'Logging in...' : 'Login to the Clinic' }}
                        </button>


                        <!-- Forgot Password Link -->
                        <div class="text-center">
                            <button @click="goToForgot" class="text-sm font-medium transition-colors" :style="{ color: brandingColor }">
                                Forgot password?
                            </button>
                        </div>
                    </div>

                    <!-- Forgot Password Form -->
                    <div v-if="step === 'forgot'" class="space-y-6">
                        <p class="text-sm text-gray-600">Enter your email address and we'll send you a link to reset your password.</p>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Email Address</label>
                            <input 
                                v-model="forgotForm.email" 
                                type="email" 
                                required 
                                class="w-full bg-gray-50 border-none rounded-2xl focus:ring-2 p-4 transition-all" 
                                :style="{ '--tw-ring-color': brandingColor }"
                                placeholder="your@email.com"
                            >
                        </div>

                        <!-- reCAPTCHA -->
                        <div class="flex justify-center">
                            <div ref="recaptchaForgotRef"></div>
                        </div>

                        <!-- Submit Button -->
                        <button 
                            @click="submitForgot"
                            :disabled="isLoading"
                            class="w-full py-4 text-white font-black text-lg rounded-full shadow-lg hover:shadow-xl transition-all disabled:opacity-50" 
                            :style="{ backgroundColor: brandingColor }">
                            {{ isLoading ? 'Sending...' : 'Send Reset Link' }}
                        </button>

                        <!-- Back Link -->
                        <div class="text-center">
                            <button @click="goBackToLogin" class="text-sm font-medium transition-colors" :style="{ color: brandingColor }">
                                Back to login
                            </button>
                        </div>
                    </div>

                    <!-- Reset Link Sent Confirmation -->
                    <div v-if="step === 'reset-sent'" class="space-y-6 text-center">
                        <div class="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-8 animate-bounce">
                            <span class="text-5xl">✓</span>
                        </div>
                        <div>
                            <h4 class="text-lg font-black text-gray-900 mb-2">Check Your Email</h4>
                            <p class="text-sm text-gray-600">We've sent a password reset link to {{ forgotForm.email }}</p>
                        </div>

                        <!-- Back Link -->
                        <button @click="goBackToLogin" class="w-full py-4 text-white font-black text-lg rounded-full shadow-lg hover:shadow-xl transition-all" 
                            :style="{ backgroundColor: brandingColor }">
                            Back to login
                        </button>
                    </div>
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
</style>
