<script setup>
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import ResetPasswordModal from '@/Pages/Tenant/Auth/Partials/ResetPasswordModal.vue';

const props = defineProps({
    token: String,
    email: String,
    tenant: Object,
});

const showModal = ref(true);
</script>

<template>
    <div>
        <Head title="Reset Password" />
        
        <!-- Simple background -->
        <div class="min-h-screen bg-gradient-to-br from-blue-50 to-gray-50 flex items-center justify-center">
            <!-- Back to clinic link -->
            <div class="absolute top-4 left-4">
                <a :href="'/' " class="text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors">
                    ← Back to {{ tenant?.name }}
                </a>
            </div>

            <!-- Modal -->
            <ResetPasswordModal
                :show="showModal"
                :token="token"
                :email="email"
                :tenantSubdomain="tenant?.id"
                @close="showModal = false"
            />
        </div>
    </div>
</template>
