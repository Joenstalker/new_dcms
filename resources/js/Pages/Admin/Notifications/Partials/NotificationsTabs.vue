<script setup>
import { router, usePage } from '@inertiajs/vue3';

const page = usePage();
const primaryColor = page.props.branding?.primary_color || '#0ea5e9';

defineProps({
    activeTab: {
        type: String,
        required: true
    },
    unreadCount: {
        type: Number,
        default: 0
    }
});

const tabs = [
    { value: 'all', label: 'All' },
    { value: 'unread', label: 'Unread' },
    { value: 'read', label: 'Read' }
];

const handleTabChange = (tab) => {
    router.visit(route('admin.notifications.index', { read: tab.value }));
};
</script>

<template>
    <!-- Tabs -->
    <div class="mb-8 border-b border-base-300">
        <nav class="-mb-px flex space-x-10">
            <button
                v-for="tab in tabs"
                :key="tab.value"
                @click="handleTabChange(tab)"
                :class="[
                    activeTab === tab.value
                        ? 'border-b-2 font-black text-sm transition-all duration-300 uppercase tracking-widest'
                        : 'border-transparent text-base-content/50 hover:text-base-content hover:border-base-300 border-b-2 font-black text-sm transition-all duration-300 uppercase tracking-widest',
                    'whitespace-nowrap py-5 px-1'
                ]"
                :style="activeTab === tab.value ? { borderBottomColor: primaryColor, color: primaryColor } : {}"
            >
                {{ tab.label }}
                <span 
                    v-if="tab.value === 'unread' && unreadCount > 0"
                    class="ml-2 badge badge-sm font-bold text-white border-transparent"
                    :style="{ backgroundColor: primaryColor }"
                >
                    {{ unreadCount }}
                </span>
            </button>
        </nav>
    </div>
</template>
