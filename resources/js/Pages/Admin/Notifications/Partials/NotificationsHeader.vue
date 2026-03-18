<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    unreadCount: {
        type: Number,
        default: 0
    }
});

const emit = defineEmits(['mark-all-read']);

const handleMarkAllRead = () => {
    emit('mark-all-read');
};
</script>

<template>
    <!-- Page Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Notifications</h1>
                <p class="mt-1 text-sm text-gray-500">
                    Manage your notifications and preferences
                </p>
            </div>
            <div class="flex items-center gap-3">
                <Link
                    :href="route('admin.notifications.settings')"
                    class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 0 0-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 0 0-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 0 0-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 0 0-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 0 0 1.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                    Notification Settings
                </Link>
                <button
                    v-if="unreadCount > 0"
                    @click="handleMarkAllRead"
                    class="inline-flex items-center px-4 py-2 bg-teal-600 rounded-lg text-sm font-medium text-white hover:bg-teal-700 transition-colors"
                >
                    Mark All as Read
                </button>
            </div>
        </div>
    </div>
</template>
