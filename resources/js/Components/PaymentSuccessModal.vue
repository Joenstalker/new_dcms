<script setup>
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    show: Boolean,
    email: {
        type: String,
        default: ''
    }
});

const emit = defineEmits(['close']);
</script>

<template>
    <Modal :show="show" @close="emit('close')" maxWidth="md">
        <div class="p-6">
            <!-- Success Icon -->
            <div class="flex justify-center mb-6">
                <div class="w-20 h-20 bg-emerald-100 rounded-full flex items-center justify-center">
                    <svg class="w-11 h-11 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>

            <!-- Header -->
            <h2 class="text-2xl font-bold text-gray-900 text-center mb-2">
                Payment received
            </h2>
            <p class="text-center text-sm font-semibold text-teal-700 mb-4">
                Registration received — you&apos;re almost there
            </p>

            <!-- Message -->
            <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-4 mb-6">
                <p class="text-sm text-emerald-900 text-center leading-relaxed">
                    <strong>Thank you!</strong> Your payment went through successfully. Your clinic is <strong>pending verification</strong> by our team; we&apos;ll email you when it&apos;s approved.
                </p>
            </div>

            <!-- Email Notice -->
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <p class="text-sm text-gray-700 text-center">
                    <strong>Next Steps:</strong>
                    <ul class="mt-2 text-left text-sm text-gray-600 space-y-2">
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-teal-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            Check your email: <span class="font-medium">{{ email }}</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-teal-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            Wait for at least <strong>1 hour</strong> for verification
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-teal-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            You'll receive a confirmation email once approved
                        </li>
                    </ul>
                </p>
            </div>

            <!-- Close Button -->
            <div class="flex justify-center">
                <PrimaryButton @click="emit('close')" class="bg-teal-600">
                    I Understand
                </PrimaryButton>
            </div>
        </div>
    </Modal>
</template>
