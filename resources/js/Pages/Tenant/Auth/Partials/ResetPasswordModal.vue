<script setup>
import { ref, watch, onUnmounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    show: Boolean,
    token: String,
    email: String,
    tenantSubdomain: String,
});

const emit = defineEmits(['close']);

const password = ref('');
const passwordConfirmation = ref('');
const isLoading = ref(false);
const errorMessage = ref('');
const successMessage = ref('');
const showPassword = ref(false);

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
    setTimeout(() => {
        resetForm();
    }, 300);
};

const submitReset = async () => {
    errorMessage.value = '';
    successMessage.value = '';

    // Validation
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
                // Redirect to tenant landing
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
    <div v-if="show" class="fixed inset-0 z-[100] overflow-y-auto" role="dialog" aria-modal="true">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="close"></div>

        <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-[2.5rem] bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-md animate-in fade-in zoom-in duration-300">
                
                <!-- Header -->
                <div class="px-8 pt-8 pb-4 flex justify-between items-start border-b border-gray-100">
                    <div>
                        <h3 class="text-2xl font-black text-gray-900">Reset Password</h3>
                        <p class="text-sm text-gray-500 font-medium mt-1">Enter a new password for {{ email }}</p>
                    </div>
                    <button @click="close" class="text-gray-400 hover:text-gray-600">
                        <span class="text-2xl">✕</span>
                    </button>
                </div>

                <!-- Content -->
                <div class="px-8 py-8">
                    <!-- Error Message -->
                    <div v-if="errorMessage" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700">
                        {{ errorMessage }}
                    </div>

                    <!-- Success Message -->
                    <div v-if="successMessage" class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl text-sm text-green-700">
                        {{ successMessage }}
                    </div>

                    <!-- Form -->
                    <form @submit.prevent="submitReset" class="space-y-6">
                        <!-- Password Field -->
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">New Password</label>
                            <div class="relative">
                                <input 
                                    v-model="password"
                                    :type="showPassword ? 'text' : 'password'"
                                    required
                                    class="w-full bg-gray-50 border-none rounded-2xl focus:ring-2 p-4 transition-all pr-12"
                                    style="--tw-ring-color: #3b82f6"
                                    placeholder="Enter new password"
                                >
                                <button
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                >
                                    {{ showPassword ? '🙈' : '👁️' }}
                                </button>
                            </div>
                            <p class="text-xs text-gray-500 mt-2">Minimum 8 characters</p>
                        </div>

                        <!-- Confirm Password Field -->
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Confirm Password</label>
                            <input 
                                v-model="passwordConfirmation"
                                type="password"
                                required
                                class="w-full bg-gray-50 border-none rounded-2xl focus:ring-2 p-4 transition-all"
                                style="--tw-ring-color: #3b82f6"
                                placeholder="Confirm new password"
                            >
                        </div>

                        <!-- Submit Button -->
                        <button 
                            type="submit"
                            :disabled="isLoading"
                            class="w-full py-4 text-white font-black text-lg rounded-full shadow-lg hover:shadow-xl transition-all disabled:opacity-50 bg-blue-600 hover:bg-blue-700">
                            {{ isLoading ? 'Resetting...' : 'Reset Password' }}
                        </button>
                    </form>
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
