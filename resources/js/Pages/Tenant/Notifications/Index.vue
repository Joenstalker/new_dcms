<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    notifications: Object,
    unread_count: Number,
    filters: Object,
});

const page = usePage();
const activeTab = ref(props.filters?.read || 'all');

const tabs = [
    { value: 'all', label: 'All' },
    { value: 'unread', label: 'Unread' },
    { value: 'read', label: 'Read' },
];

const formatDate = (date) => {
    const d = new Date(date);
    return d.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getIcon = (type) => {
    const icons = {
        calendar: `<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />`,
        'calendar-plus': `<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zm0 2.25h.008v.008H12v-.008zM9.75 15h.008v.008H9.75V15zm0 2.25h.008v.008H9.75v-.008zM7.5 15h.008v.008H7.5V15zm0 2.25h.008v.008H7.5v-.008zm6.75-4.5h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V15zm0 2.25h.008v.008h-.008v-.008zm2.25-4.5h.008v.008H16.5v-.008zm0 2.25h.008v.008H16.5V15z" />`,
        'calendar-x': `<path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />`,
        user: `<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />`,
        'user-plus': `<path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM1 19.128v-3.757a2.25 2.25 0 0 1 1.883-2.542l.696-1.9A2.25 2.25 0 0 1 5.135-.75l3.75 1.25-2.625 6.375a2.25 2.25 0 0 1-2.245 2.297H1Z" />`,
        'check-circle': `<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />`,
        star: `<path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />`,
        'credit-card': `<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" />`,
        message: `<path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334a2.126 2.126 0 0 0-.476-.095 48.64 48.64 0 0 0-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0 0 11.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" />`,
        bell: `<path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />`,
    };

    const iconMap = {
        appointment_created: 'calendar-plus',
        appointment_updated: 'calendar',
        appointment_cancelled: 'calendar-x',
        patient_added: 'user-plus',
        patient_updated: 'user',
        treatment_completed: 'check-circle',
        new_feature: 'star',
        subscription_reminder: 'credit-card',
        payment_due: 'credit-card',
        staff_message: 'message',
        system_notification: 'bell',
    };

    return icons[iconMap[type] || 'bell'];
};

const markAsRead = (id) => {
    router.put(route('notifications.mark-read', { id }), {}, {
        onSuccess: () => {
            // Reload to get updated data
        },
    });
};

const markAllAsRead = () => {
    router.put(route('notifications.mark-all-read'), {}, {
        onSuccess: () => {
            // Reload to get updated data
        },
    });
};

const deleteNotification = (id) => {
    if (confirm('Are you sure you want to delete this notification?')) {
        router.delete(route('notifications.destroy', { id }), {}, {
            onSuccess: () => {
                // Reload to get updated data
            },
        });
    }
};

const getSenderInfo = (notification) => {
    if (notification.sender_type === 'User' && notification.sender_id) {
        return 'From Staff';
    }
    if (notification.type === 'new_feature' || notification.type === 'subscription_reminder' || notification.type === 'payment_due') {
        return 'From System';
    }
    return '';
};
</script>

<template>
    <Head title="Notifications" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Notifications</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Notifications</h1>
                    <p class="mt-1 text-sm text-gray-500">
                        Stay updated with your clinic activities
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        v-if="unread_count > 0"
                        @click="markAllAsRead"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 rounded-lg text-sm font-medium text-white hover:bg-blue-700 transition-colors"
                    >
                        Mark All as Read
                    </button>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="mb-6 border-b border-gray-200">
            <nav class="-mb-px flex space-x-8">
                <button
                    v-for="tab in tabs"
                    :key="tab.value"
                    @click="activeTab = tab.value; router.visit(route('notifications.index', { read: tab.value }))"
                    :class="[
                        activeTab === tab.value
                            ? 'border-blue-500 text-blue-600'
                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                        'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors'
                    ]"
                >
                    {{ tab.label }}
                    <span 
                        v-if="tab.value === 'unread' && unread_count > 0"
                        class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
                    >
                        {{ unread_count }}
                    </span>
                </button>
            </nav>
        </div>

        <!-- Notifications List -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div v-if="notifications.data.length === 0" class="flex flex-col items-center justify-center py-16">
                <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-1">No notifications</h3>
                <p class="text-gray-500">You're all caught up!</p>
            </div>

            <ul v-else class="divide-y divide-gray-200">
                <li 
                    v-for="notification in notifications.data" 
                    :key="notification.id"
                    :class="[
                        'group hover:bg-gray-50 transition-colors relative',
                        !notification.is_read ? 'bg-blue-50/50' : ''
                    ]"
                >
                    <div class="flex items-start gap-4 p-4 sm:px-6">
                        <!-- Icon -->
                        <div 
                            :class="[
                                'flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center',
                                !notification.is_read ? 'bg-blue-100 text-blue-600' : 'bg-gray-100 text-gray-500'
                            ]"
                        >
                            <svg 
                                class="w-5 h-5" 
                                fill="none" 
                                viewBox="0 0 24 24" 
                                stroke="currentColor"
                                v-html="getIcon(notification.type)"
                            ></svg>
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p :class="['text-sm', !notification.is_read ? 'font-semibold text-gray-900' : 'text-gray-700']">
                                        {{ notification.title }}
                                    </p>
                                    <p class="text-sm text-gray-500 mt-1">
                                        {{ notification.message }}
                                    </p>
                                    <div class="flex items-center gap-3 mt-2">
                                        <p class="text-xs text-gray-400">
                                            {{ formatDate(notification.created_at) }}
                                        </p>
                                        <span 
                                            v-if="getSenderInfo(notification)"
                                            class="text-xs text-gray-400"
                                        >
                                            • {{ getSenderInfo(notification) }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button
                                        v-if="!notification.is_read"
                                        @click="markAsRead(notification.id)"
                                        class="p-1.5 text-gray-400 hover:text-blue-600 rounded-lg hover:bg-gray-100"
                                        title="Mark as read"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </button>
                                    <button
                                        @click="deleteNotification(notification.id)"
                                        class="p-1.5 text-gray-400 hover:text-red-600 rounded-lg hover:bg-gray-100"
                                        title="Delete"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Unread Indicator -->
                            <div 
                                v-if="!notification.is_read"
                                class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-8 bg-blue-500 rounded-r-full"
                            ></div>
                        </div>
                    </div>
                </li>
            </ul>

            <!-- Pagination -->
            <div v-if="notifications.links && notifications.links.length > 1" class="px-6 py-4 border-t border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-500">
                        Showing {{ notifications.from }} to {{ notifications.to }} of {{ notifications.total }} results
                    </div>
                    <div class="flex gap-1">
                        <template v-for="(link, index) in notifications.links" :key="index">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                :class="[
                                    'px-3 py-1 rounded-lg text-sm font-medium transition-colors',
                                    link.active
                                        ? 'bg-blue-600 text-white'
                                        : 'text-gray-500 hover:bg-gray-100'
                                ]"
                                v-html="link.label"
                            />
                            <span 
                                v-else
                                class="px-3 py-1 rounded-lg text-sm font-medium text-gray-500 opacity-50 cursor-not-allowed"
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
            </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
