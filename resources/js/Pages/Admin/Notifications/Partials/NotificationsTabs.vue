<script setup>
import { router } from '@inertiajs/vue3';

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
    <div class="mb-6 border-b border-gray-200">
        <nav class="-mb-px flex space-x-8">
            <button
                v-for="tab in tabs"
                :key="tab.value"
                @click="handleTabChange(tab)"
                :class="[
                    activeTab === tab.value
                        ? 'border-teal-500 text-teal-600'
                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                    'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors'
                ]"
            >
                {{ tab.label }}
                <span 
                    v-if="tab.value === 'unread' && unreadCount > 0"
                    class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-teal-100 text-teal-800"
                >
                    {{ unreadCount }}
                </span>
            </button>
        </nav>
    </div>
</template>
