<script setup>
import { ref, onMounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const props = defineProps({
    type: {
        type: String,
        default: 'admin', // 'admin' or 'tenant'
    },
    placement: {
        type: String,
        default: 'bottom-end',
    },
});

const page = usePage();
const isOpen = ref(false);
const notifications = ref([]);
const unreadCount = ref(0);
const loading = ref(false);

const getRoute = () => {
    if (props.type === 'admin') {
        return {
            recent: route('admin.notifications.recent'),
            count: route('admin.notifications.count'),
            markRead: (id) => route('admin.notifications.mark-read', { id }),
            markAllRead: route('admin.notifications.mark-all-read'),
            index: route('admin.notifications.index'),
            settings: route('admin.notifications.settings'),
        };
    } else {
        return {
            recent: route('notifications.recent'),
            count: route('notifications.count'),
            markRead: (id) => route('notifications.mark-read', { id }),
            markAllRead: route('notifications.mark-all-read'),
            index: route('notifications.index'),
            settings: route('notifications.settings'),
        };
    }
};

const fetchNotifications = async () => {
    loading.value = true;
    try {
        const response = await fetch(getRoute().recent);
        const data = await response.json();
        notifications.value = data.notifications;
        unreadCount.value = data.unread_count;
    } catch (error) {
        console.error('Failed to fetch notifications:', error);
    } finally {
        loading.value = false;
    }
};

const markAsRead = async (id) => {
    try {
        await fetch(getRoute().markRead(id), {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': page.props.csrf_token,
            },
        });
        await fetchNotifications();
    } catch (error) {
        console.error('Failed to mark notification as read:', error);
    }
};

const markAllAsRead = async () => {
    try {
        await fetch(getRoute().markAllRead, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': page.props.csrf_token,
            },
        });
        await fetchNotifications();
    } catch (error) {
        console.error('Failed to mark all as read:', error);
    }
};

const formatDate = (date) => {
    const d = new Date(date);
    const now = new Date();
    const diff = now - d;
    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(diff / 3600000);
    const days = Math.floor(diff / 86400000);

    if (minutes < 1) return 'Just now';
    if (minutes < 60) return `${minutes}m ago`;
    if (hours < 24) return `${hours}h ago`;
    if (days < 7) return `${days}d ago`;
    return d.toLocaleDateString();
};

const getIcon = (type) => {
    const icons = {
        building: `<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />`,
        'credit-card': `<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" />`,
        ticket: `<path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 0 1 0 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 0 1 0-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375Z" />`,
        star: `<path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />`,
        'check-circle': `<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />`,
        'exclamation-circle': `<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />`,
        calendar: `<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />`,
        bell: `<path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />`,
        user: `<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />`,
        message: `<path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334a2.126 2.126 0 0 0-.476-.095 48.64 48.64 0 0 0-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0 0 11.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" />`,
    };
    return icons[type] || icons.bell;
};

const getNotificationTypeIcon = (type) => {
    const iconMap = {
        new_tenant: 'building',
        tenant_suspended: 'exclamation-circle',
        subscription_created: 'credit-card',
        subscription_expiring: 'exclamation-circle',
        subscription_expired: 'exclamation-circle',
        new_support_ticket: 'ticket',
        feature_request: 'star',
        new_feature: 'star',
        payment_received: 'check-circle',
        payment_failed: 'exclamation-circle',
        appointment_created: 'calendar',
        appointment_updated: 'calendar',
        appointment_cancelled: 'calendar',
        patient_added: 'user',
        treatment_completed: 'check-circle',
        staff_message: 'message',
        system_notification: 'bell',
    };
    return getIcon(iconMap[type] || 'bell');
};

onMounted(() => {
    fetchNotifications();
});
</script>

<template>
    <div class="relative">
        <!-- Bell Button -->
        <button
            @click="isOpen = !isOpen; isOpen && fetchNotifications()"
            class="relative p-2 text-base-content/50 hover:text-base-content hover:bg-base-200 transition-all duration-200 rounded-xl"
        >
            <svg 
                class="w-6 h-6" 
                fill="none" 
                viewBox="0 0 24 24" 
                stroke="currentColor"
                v-html="getIcon('bell')"
            ></svg>
            <!-- Unread Badge -->
            <span 
                v-if="unreadCount > 0"
                class="absolute -top-0.5 -right-0.5 flex items-center justify-center min-w-[18px] h-[18px] px-1 text-[10px] font-black text-white bg-error rounded-lg shadow-sm border-2 border-base-100"
            >
                {{ unreadCount > 99 ? '99+' : unreadCount }}
            </span>
        </button>

        <!-- Dropdown -->
        <div
            v-if="isOpen"
            class="absolute right-0 mt-3 w-80 bg-base-100 rounded-2xl shadow-2xl ring-1 ring-base-content/10 focus:outline-none z-50 overflow-hidden border border-base-300 transform origin-top-right transition-all duration-200"
            :class="placement === 'bottom-start' ? 'origin-bottom-left' : 'origin-top-right'"
        >
            <!-- Header -->
            <div class="flex items-center justify-between px-5 py-4 border-b border-base-300 bg-base-200/30">
                <h3 class="text-xs font-black uppercase tracking-widest text-base-content/70">Notifications</h3>
                <button
                    v-if="unreadCount > 0"
                    @click="markAllAsRead"
                    class="text-xs text-teal-600 hover:text-teal-700 font-medium"
                >
                    Mark all as read
                </button>
            </div>

            <!-- Notifications List -->
            <div class="max-h-96 overflow-y-auto">
                <div v-if="loading" class="flex items-center justify-center py-8">
                    <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-teal-600"></div>
                </div>
                
                <ul v-else class="divide-y divide-base-300">
                    <li 
                        v-for="notification in notifications" 
                        :key="notification.id"
                        class="relative"
                    >
                        <div 
                            :class="[
                                'flex items-start gap-4 px-5 py-4 hover:bg-base-200 transition-colors cursor-pointer',
                                !notification.is_read ? 'bg-primary/5' : ''
                            ]"
                            @click="!notification.is_read && markAsRead(notification.id)"
                        >
                            <!-- Icon -->
                            <div 
                                :class="[
                                    'flex-shrink-0 w-9 h-9 rounded-xl flex items-center justify-center shadow-sm',
                                    !notification.is_read ? 'bg-primary/10 text-primary' : 'bg-base-200 text-base-content/40'
                                ]"
                            >
                                <svg 
                                    class="w-4.5 h-4.5" 
                                    fill="none" 
                                    viewBox="0 0 24 24" 
                                    stroke="currentColor"
                                    v-html="getNotificationTypeIcon(notification.type)"
                                ></svg>
                            </div>
 
                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                                <p :class="['text-xs', !notification.is_read ? 'font-black text-base-content' : 'font-medium text-base-content/70']">
                                    {{ notification.title }}
                                </p>
                                <p class="text-[11px] text-base-content/50 truncate mt-1">
                                    {{ notification.message }}
                                </p>
                                <p class="text-[9px] uppercase tracking-wider font-bold text-base-content/30 mt-2">
                                    {{ formatDate(notification.created_at) }}
                                </p>
                            </div>
 
                            <!-- Unread Indicator -->
                            <div 
                                v-if="!notification.is_read"
                                class="flex-shrink-0 w-2 h-2 bg-primary rounded-full mt-2 animate-pulse"
                            ></div>
                        </div>
                    </li>
                </ul>
            </div>

            <!-- Footer -->
            <div class="border-t border-gray-100 px-4 py-3">
                <Link
                    :href="getRoute().index"
                    class="flex items-center justify-center text-sm text-teal-600 hover:text-teal-700 font-medium"
                    @click="isOpen = false"
                >
                    View all notifications
                    <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </Link>
            </div>
        </div>

        <!-- Click Outside Handler -->
        <div 
            v-if="isOpen" 
            class="fixed inset-0 z-40"
            @click="isOpen = false"
        ></div>
    </div>
</template>
