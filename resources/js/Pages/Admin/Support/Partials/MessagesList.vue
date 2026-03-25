import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const primaryColor = page.props.branding?.primary_color || '#0ea5e9';

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
    <div class="bg-base-100 rounded-3xl shadow-sm border border-base-300 overflow-hidden">
        <!-- Loading State -->
        <div v-if="loading" class="p-8">
            <div class="animate-pulse space-y-6">
                <div v-for="i in 5" :key="i" class="flex gap-5">
                    <div class="w-12 h-12 bg-base-300 rounded-2xl"></div>
                    <div class="flex-1 space-y-3">
                        <div class="h-4 bg-base-300 rounded-lg w-1/4"></div>
                        <div class="h-3 bg-base-300 rounded-lg w-3/4"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else-if="messages.data.length === 0" class="flex flex-col items-center justify-center py-20 px-6">
            <div class="w-20 h-20 rounded-full bg-base-200 flex items-center justify-center mb-6">
                <svg class="w-10 h-10 text-base-content/20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                </svg>
            </div>
            <h3 class="text-xl font-black text-base-content mb-2 tracking-tight">No messages found</h3>
            <p class="text-base-content/40 font-medium">There are no support messages in this category.</p>
        </div>

        <!-- Messages Table -->
        <div v-else class="overflow-x-auto">
            <table class="table w-full border-collapse">
                <thead>
                    <tr class="bg-base-200/50 border-b border-base-300">
                        <th class="px-8 py-5 text-left text-[10px] font-black text-base-content/40 uppercase tracking-widest border-none">
                            Status
                        </th>
                        <th class="px-8 py-5 text-left text-[10px] font-black text-base-content/40 uppercase tracking-widest border-none">
                            Sender Information
                        </th>
                        <th class="px-8 py-5 text-left text-[10px] font-black text-base-content/40 uppercase tracking-widest border-none">
                            Subject & Content
                        </th>
                        <th class="px-8 py-5 text-left text-[10px] font-black text-base-content/40 uppercase tracking-widest border-none">
                            Received Date
                        </th>
                        <th class="px-8 py-5 text-right text-[10px] font-black text-base-content/40 uppercase tracking-widest border-none">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-base-300/30">
                    <tr 
                        v-for="message in messages.data" 
                        :key="message.id"
                        @click="handleSelect(message)"
                        class="hover:bg-base-200/30 cursor-pointer transition-all duration-300 group relative border-none"
                        :style="message.status === 'unread' ? { backgroundColor: primaryColor + '0A' } : {}"
                    >
                        <td class="px-8 py-6 whitespace-nowrap border-none">
                            <span 
                                :class="[
                                    'badge badge-sm font-black uppercase tracking-widest px-3 py-2.5 h-auto',
                                    message.status === 'unread' ? 'badge-warning shadow-sm shadow-warning/20' : 
                                    message.status === 'read' ? 'badge-info shadow-sm shadow-info/20' :
                                    message.status === 'replied' ? 'badge-success shadow-sm shadow-success/20' :
                                    'badge-ghost bg-base-300 border-none'
                                ]"
                            >
                                {{ message.status }}
                            </span>
                        </td>
                        <td class="px-8 py-6 whitespace-nowrap border-none">
                            <div class="flex items-center gap-4">
                                <div class="avatar placeholder">
                                    <div class="bg-base-300 text-base-content/50 rounded-2xl w-11 h-11 border border-base-300 shadow-sm transition-all duration-300 group-hover:scale-105 group-hover:shadow-md">
                                        <span class="text-xs font-black">{{ message.name.charAt(0).toUpperCase() }}</span>
                                    </div>
                                </div>
                                <div>
                                    <div class="text-sm font-black text-base-content tracking-tight">{{ message.name }}</div>
                                    <div class="text-[11px] text-base-content/40 font-bold lowercase">{{ message.email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-8 py-6 border-none">
                            <div class="text-sm font-bold text-base-content/80 truncate max-w-xs tracking-tight">{{ message.subject }}</div>
                            <div class="text-xs text-base-content/40 truncate max-w-xs mt-1 font-medium">{{ message.message.substring(0, 60) }}...</div>
                        </td>
                        <td class="px-8 py-6 whitespace-nowrap border-none">
                            <div class="text-[11px] font-black uppercase tracking-widest text-base-content/30">{{ formatDate(message.created_at) }}</div>
                        </td>
                        <td class="px-8 py-6 whitespace-nowrap text-right border-none">
                            <button 
                                class="btn btn-ghost btn-sm font-black text-[11px] uppercase tracking-widest hover:text-primary hover:bg-primary/10 transition-all duration-300"
                                @click.stop="handleSelect(message)"
                            >
                                View Ticket
                            </button>
                        </td>
                        
                        <!-- Side Indicator for unread -->
                        <div v-if="message.status === 'unread'" class="absolute left-0 top-3 bottom-3 w-1 bg-warning rounded-r-full"></div>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div v-if="messages.links && messages.links.length > 1" class="px-8 py-6 border-t border-base-300 bg-base-200/30">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-6">
                <div class="text-[10px] font-black text-base-content/40 uppercase tracking-widest">
                    Showing <span class="text-base-content">{{ messages.from }}</span> to <span class="text-base-content">{{ messages.to }}</span> of <span class="font-bold" :style="{ color: primaryColor }">{{ messages.total }}</span> results
                </div>
                <div class="flex gap-2">
                    <template v-for="(link, index) in messages.links" :key="index">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            :class="[
                                'btn btn-xs font-black transition-all duration-300 px-4 h-8 rounded-xl',
                                link.active
                                    ? 'shadow-lg text-white border-transparent'
                                    : 'btn-ghost text-base-content/50 hover:text-base-content hover:bg-base-300 border-none'
                            ]"
                            :style="link.active ? { backgroundColor: primaryColor, boxShadow: `0 4px 14px 0 ${primaryColor}33` } : {}"
                            v-html="link.label"
                        />
                        <span 
                            v-else
                            class="btn btn-xs btn-ghost btn-disabled opacity-30 font-black h-8 px-4 rounded-xl border-none"
                            v-html="link.label"
                        />
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>
