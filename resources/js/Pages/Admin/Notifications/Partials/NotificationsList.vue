<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    notifications: {
        type: Object,
        required: true
    }
});

const emit = defineEmits(['mark-as-read', 'delete']);

const formatDate = (date) => {
    const d = new Date(date);
    return d.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const getIcon = (type) => {
    const icons = {
        building: `<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />`,
        'credit-card': `<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" />`,
        ticket: `<path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 0 1 0 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 0 1 0-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375Z" />`,
        star: `<path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />`,
        'check-circle': `<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />`,
        'exclamation-circle': `<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />`,
        bell: `<path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />`,
    };

    const iconMap = {
        new_tenant: 'building',
        tenant_suspended: 'exclamation-circle',
        tenant_reactivated: 'check-circle',
        subscription_created: 'credit-card',
        subscription_renewed: 'credit-card',
        subscription_expiring: 'exclamation-circle',
        subscription_expired: 'exclamation-circle',
        subscription_cancelled: 'exclamation-circle',
        new_support_ticket: 'ticket',
        support_ticket_replied: 'ticket',
        feature_request: 'star',
        new_feature_published: 'star',
        payment_received: 'check-circle',
        payment_failed: 'exclamation-circle',
        system_alert: 'bell',
    };

    return icons[iconMap[type] || 'bell'];
};

const handleMarkAsRead = (id) => {
    emit('mark-as-read', id);
};

const handleDelete = (id) => {
    emit('delete', id);
};
</script>

<template>
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
                    'group hover:bg-gray-50 transition-colors',
                    !notification.is_read ? 'bg-teal-50/50' : ''
                ]"
            >
                <div class="flex items-start gap-4 p-4 sm:px-6">
                    <!-- Icon -->
                    <div 
                        :class="[
                            'flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center',
                            !notification.is_read ? 'bg-teal-100 text-teal-600' : 'bg-gray-100 text-gray-500'
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
                    <div class="flex-1 min-w-0 relative">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p :class="['text-sm', !notification.is_read ? 'font-semibold text-gray-900' : 'text-gray-700']">
                                    {{ notification.title }}
                                </p>
                                <p class="text-sm text-gray-500 mt-1">
                                    {{ notification.message }}
                                </p>
                                <p class="text-xs text-gray-400 mt-2">
                                    {{ formatDate(notification.created_at) }}
                                </p>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button
                                    v-if="!notification.is_read"
                                    @click="handleMarkAsRead(notification.id)"
                                    class="p-1.5 text-gray-400 hover:text-teal-600 rounded-lg hover:bg-gray-100"
                                    title="Mark as read"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </button>
                                <button
                                    @click="handleDelete(notification.id)"
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
                            class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-8 bg-teal-500 rounded-r-full"
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
                                    ? 'bg-teal-600 text-white'
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
</template>
