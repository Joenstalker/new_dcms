<script setup>
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const primaryColor = page.props.branding?.primary_color || '#0ea5e9';

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
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-black text-base-content tracking-tight">Notifications</h1>
                <p class="mt-1 text-sm text-base-content/50 font-medium">
                    Manage your system alerts and notification preferences
                </p>
            </div>
            <div class="flex items-center gap-3">
                <Link
                    :href="route('admin.notifications.settings')"
                    class="btn btn-ghost btn-sm font-bold text-base-content/70 hover:text-base-content"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 0 0-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 0 0-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 0 0-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 0 0-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 0 0 1.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                    Settings
                </Link>
                <button
                    v-if="unreadCount > 0"
                    @click="handleMarkAllRead"
                    class="btn btn-sm font-bold shadow-sm text-white border-transparent hover:brightness-110"
                    :style="{ backgroundColor: primaryColor }"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    Mark All as Read
                </button>
            </div>
        </div>
    </div>
</template>
