<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    messages: {
        type: Object,
        required: true
    },
    loading: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['select-message']);

const formatDate = (date) => {
    const d = new Date(date);
    const now = new Date();
    const diffMs = now - d;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins}m ago`;
    if (diffHours < 24) return `${diffHours}h ago`;
    if (diffDays < 7) return `${diffDays}d ago`;
    
    return d.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: d.getFullYear() !== now.getFullYear() ? 'numeric' : undefined
    });
};

const getStatusColor = (status) => {
    const colors = {
        unread: 'bg-orange-100 text-orange-700 border-orange-200',
        read: 'bg-blue-100 text-blue-700 border-blue-200',
        replied: 'bg-teal-100 text-teal-700 border-teal-200',
        archived: 'bg-gray-100 text-gray-600 border-gray-200'
    };
    return colors[status] || colors.unread;
};

const handleSelect = (message) => {
    emit('select-message', message);
};
</script>

<template>
    <!-- Messages List -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <!-- Loading State -->
        <div v-if="loading" class="p-6">
            <div class="animate-pulse space-y-4">
                <div v-for="i in 5" :key="i" class="flex gap-4">
                    <div class="w-10 h-10 bg-gray-200 rounded-full"></div>
                    <div class="flex-1 space-y-2">
                        <div class="h-4 bg-gray-200 rounded w-1/4"></div>
                        <div class="h-3 bg-gray-200 rounded w-3/4"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else-if="messages.data.length === 0" class="flex flex-col items-center justify-center py-16">
            <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
            </svg>
            <h3 class="text-lg font-medium text-gray-900 mb-1">No messages found</h3>
            <p class="text-gray-500">There are no support messages in this category.</p>
        </div>

        <!-- Messages Table -->
        <table v-else class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        Status
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        Name
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        Subject
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        Date
                    </th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <tr 
                    v-for="message in messages.data" 
                    :key="message.id"
                    @click="handleSelect(message)"
                    class="hover:bg-gray-50 cursor-pointer transition-colors"
                    :class="{ 'bg-teal-50/30': message.status === 'unread' }"
                >
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span 
                            :class="[
                                'px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full border',
                                getStatusColor(message.status)
                            ]"
                        >
                            {{ message.status }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center">
                                <span class="text-xs font-medium text-gray-600">
                                    {{ message.name.charAt(0).toUpperCase() }}
                                </span>
                            </div>
                            <div class="ml-3">
                                <div class="text-sm font-medium text-gray-900">{{ message.name }}</div>
                                <div class="text-xs text-gray-500">{{ message.email }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-900 truncate max-w-xs">{{ message.subject }}</div>
                        <div class="text-xs text-gray-500 truncate max-w-xs">{{ message.message.substring(0, 60) }}...</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-500">{{ formatDate(message.created_at) }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <button 
                            class="text-teal-600 hover:text-teal-900 font-medium text-sm"
                            @click.stop="handleSelect(message)"
                        >
                            View
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Pagination -->
        <div v-if="messages.links && messages.links.length > 1" class="px-6 py-4 border-t border-gray-200">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-500">
                    Showing {{ messages.from }} to {{ messages.to }} of {{ messages.total }} results
                </div>
                <div class="flex gap-1">
                    <template v-for="(link, index) in messages.links" :key="index">
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
