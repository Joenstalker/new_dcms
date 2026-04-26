<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import ContactModal from '@/Components/ContactModal.vue';

const showContactModal = ref(false);

defineProps({
    tenant_name: String
});
</script>

<template>
    <Head title="Clinic Suspended" />

    <div class="bg-gray-50 flex items-center justify-center min-h-screen p-4">
        <div class="max-w-md w-full bg-white rounded-2xl shadow-xl border border-red-100 p-8 text-center transition-all hover:shadow-2xl">
            <!-- Icon Section -->
            <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6 animate-pulse">
                <svg class="w-10 h-10 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>

            <!-- Content Section -->
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Clinic Suspended</h1>
            <p v-if="tenant_name" class="text-sm font-medium text-red-500 mb-3">{{ tenant_name }}</p>
            <p class="text-gray-600 mb-8 leading-relaxed">
                Access to this platform has been suspended by the administrator. Please contact support to resolve this issue.
            </p>
            
            <!-- Action Button -->
            <button 
                @click="showContactModal = true"
                class="inline-block bg-red-600 hover:bg-red-700 text-white font-semibold px-6 py-3 rounded-lg shadow-lg shadow-red-200 transition-all active:scale-95 w-full"
            >
                Contact Support
            </button>

            <!-- Back to Home -->
            <div class="mt-6 pt-6 border-t border-gray-100">
                <Link href="/" class="text-sm text-gray-500 hover:text-gray-800 transition-colors">
                    Back to Home Page
                </Link>
            </div>
        </div>

        <!-- Contact Support Modal -->
        <ContactModal 
            :show="showContactModal" 
            @close="showContactModal = false"
            submitUrl="/contact-support"
        />
    </div>
</template>

<style scoped>
/* Any additional local styles */
</style>
