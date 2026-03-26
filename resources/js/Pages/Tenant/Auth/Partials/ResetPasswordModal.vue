<script setup>
import { ref, watch, onUnmounted, computed } from 'vue';
import axios from 'axios';

const props = defineProps({
    show: Boolean,
    token: String,
    email: String,
    tenant: Object,
});

const emit = defineEmits(['close']);

const password = ref('');
const passwordConfirmation = ref('');
const isLoading = ref(false);
const errorMessage = ref('');
const successMessage = ref('');
const showPassword = ref(false);

const brandingColor = computed(() => props.tenant?.branding_color || '#3b82f6');
const fontFamily = computed(() => props.tenant?.font_family || 'font-sans');
const logoUrl = computed(() => {
    const logopath = props.tenant?.logo_login_path || props.tenant?.logo_path;
    return logopath ? `/storage/${logopath}` : null;
});

watch(() => props.show, (newVal) => {
    if (newVal) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
        resetForm();
    }
}, { immediate: true });

onUnmounted(() => {
    document.body.style.overflow = '';
});

const resetForm = () => {
    password.value = '';
    passwordConfirmation.value = '';
    errorMessage.value = '';
    successMessage.value = '';
};

const close = () => {
    emit('close');
    setTimeout(() => resetForm(), 300);
};

const submitReset = async () => {
    errorMessage.value = '';
    successMessage.value = '';

    if (!password.value) {
        errorMessage.value = 'Password is required';
        return;
    }

    if (password.value.length < 8) {
        errorMessage.value = 'Password must be at least 8 characters';
        return;
    }

    if (password.value !== passwordConfirmation.value) {
        errorMessage.value = 'Passwords do not match';
        return;
    }

    isLoading.value = true;

    try {
        const response = await axios.post(route('tenant.password.store'), {
            email: props.email,
            token: props.token,
            password: password.value,
            password_confirmation: passwordConfirmation.value,
        });

        if (response.data.success) {
            successMessage.value = 'Password reset successful! Redirecting to login...';
            setTimeout(() => {
                close();
                window.location.href = `/`;
            }, 1500);
        }
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Failed to reset password. Please try again.';
        if (error.response?.data?.errors) {
            const errors = Object.values(error.response.data.errors).flat();
            errorMessage.value = errors.join(', ');
        }
    } finally {
        isLoading.value = false;
    }
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
                    <h3 class="text-lg font-bold text-slate-900 leading-tight">Create New Password</h3>
                    <p class="text-xs text-slate-500 font-medium">Securing access for {{ email }}</p>
                </div>
                <button @click="close" class="p-2 rounded-full hover:bg-slate-50 text-slate-400 hover:text-slate-600 transition-colors group">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Scrollable Content -->
            <div class="px-8 py-6 overflow-y-auto custom-scrollbar flex-grow">
                <!-- Status Messages -->
                <transition name="fade">
                    <div v-if="errorMessage" class="mb-5 p-3.5 bg-red-50 border border-red-100 rounded-xl text-xs text-red-600 font-medium flex gap-3 items-start">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="flex-1">{{ errorMessage }}</span>
                    </div>
                </transition>

                <transition name="fade">
                    <div v-if="successMessage" class="mb-5 p-3.5 bg-emerald-50 border border-emerald-100 rounded-xl text-xs text-emerald-600 font-medium flex gap-3 items-start">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span class="flex-1">{{ successMessage }}</span>
                    </div>
                </transition>

                <!-- Reset Form -->
                <form @submit.prevent="submitReset" class="space-y-5">
                    <div>
                        <label class="block text-[12px] font-bold text-slate-700 mb-1 ml-1">New Password</label>
                        <div class="relative">
                            <input 
                                v-model="password"
                                :type="showPassword ? 'text' : 'password'"
                                required
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 p-3.5 transition-all text-sm font-medium pr-12"
                                :style="{ '--tw-ring-color': brandingColor }"
                                placeholder="••••••••"
                            >
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute right-4 top-1/2 transform -translate-y-1/2 text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-white transition-all"
                            >
                                <svg v-if="!showPassword" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg v-else xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.04m4.066-1.426A10.015 10.015 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21m-2.101-2.101L12 12m0 0L3 3" />
                                </svg>
                            </button>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-2 ml-1">Requirement: Minimum 8 complex characters</p>
                    </div>

                    <div>
                        <label class="block text-[12px] font-bold text-slate-700 mb-1 ml-1">Confirm New Password</label>
                        <input 
                            v-model="passwordConfirmation"
                            type="password"
                            required
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 p-3.5 transition-all text-sm font-medium"
                            :style="{ '--tw-ring-color': brandingColor }"
                            placeholder="••••••••"
                        >
                    </div>

                    <button 
                        type="submit"
                        :disabled="isLoading"
                        class="w-full py-3.5 text-white font-bold text-base rounded-xl shadow-[0_10px_20px_rgba(0,0,0,0.1)] hover:shadow-[0_15px_30px_rgba(0,0,0,0.15)] transition-all active:scale-[0.98] disabled:opacity-50 flex items-center justify-center gap-2" 
                        :style="{ backgroundColor: brandingColor }">
                        <span v-if="isLoading" class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                        {{ isLoading ? 'Saving Password...' : 'Reset My Password' }}
                    </button>
                </form>
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

.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}

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
