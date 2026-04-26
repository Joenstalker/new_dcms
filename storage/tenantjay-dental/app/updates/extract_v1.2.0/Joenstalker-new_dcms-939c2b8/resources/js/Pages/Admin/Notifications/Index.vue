<script setup>
import { ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

import NotificationsHeader from './Partials/NotificationsHeader.vue';
import NotificationsTabs from './Partials/NotificationsTabs.vue';
import NotificationsList from './Partials/NotificationsList.vue';

const props = defineProps({
    notifications: Object,
    unread_count: Number,
    filters: Object,
});

const page = usePage();
const activeTab = ref(props.filters?.read || 'all');

const markAsRead = (id) => {
    router.put(route('admin.notifications.mark-read', { id }), {}, {
        onSuccess: () => {
            // Reload to get updated data
        },
    });
};

const markAllAsRead = () => {
    router.put(route('admin.notifications.mark-all-read'), {}, {
        onSuccess: () => {
            // Reload to get updated data
        },
    });
};

const deleteNotification = (id) => {
    if (confirm('Are you sure you want to delete this notification?')) {
        router.delete(route('admin.notifications.destroy', { id }), {}, {
            onSuccess: () => {
                // Reload to get updated data
            },
        });
    }
};
</script>

<template>
    <Head title="Notifications" />
    <AdminLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-gray-900">Notifications</h1>
        </template>
        
        <div class="max-w-7xl mx-auto py-6">
            <!-- Header -->
            <NotificationsHeader 
                :unread-count="unread_count"
                @mark-all-read="markAllAsRead"
            />

            <!-- Tabs -->
            <NotificationsTabs 
                :active-tab="activeTab"
                :unread-count="unread_count"
            />

            <!-- Notifications List -->
            <NotificationsList 
                :notifications="notifications"
                @mark-as-read="markAsRead"
                @delete="deleteNotification"
            />
        </div>
    </AdminLayout>
</template>
