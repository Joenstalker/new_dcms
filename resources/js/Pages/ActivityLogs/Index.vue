<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { computed } from 'vue';

const props = defineProps({
    logs: Object,
    filters: Object,
    users: Array,
});

const page = usePage();

const getEventColor = (event) => {
    switch (event) {
        case 'login': return 'badge-success';
        case 'failed_login': return 'badge-error';
        case 'unauthorized_access': return 'badge-warning';
        case 'deleted': return 'badge-error';
        case 'updated': return 'badge-info';
        case 'created': return 'badge-success';
        default: return 'badge-ghost';
    }
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleString();
};
</script>

<template>
    <Head title="Activity Logs" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center space-x-4 animate-fade-in">
                <div class="h-12 w-12 rounded-2xl bg-primary/10 flex items-center justify-center shadow-inner">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7 text-primary">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-2xl font-black text-base-content uppercase tracking-widest leading-none">Activity Logs</h2>
                    <p class="text-[10px] font-bold text-base-content/40 uppercase tracking-[0.2em] mt-1">Tenant Security & Audit Trail</p>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Table Section -->
            <div class="bg-base-100 rounded-3xl shadow-xl overflow-hidden border border-base-300 animate-slide-up">
                <div class="overflow-x-auto">
                    <table class="table table-zebra w-full border-collapse">
                        <thead>
                            <tr class="bg-base-200/50 border-b border-base-300">
                                <th class="text-[10px] font-black uppercase tracking-widest text-base-content/50 py-6 px-8">Timestamp</th>
                                <th class="text-[10px] font-black uppercase tracking-widest text-base-content/50 py-6 px-8">User</th>
                                <th class="text-[10px] font-black uppercase tracking-widest text-base-content/50 py-6 px-8">Event</th>
                                <th class="text-[10px] font-black uppercase tracking-widest text-base-content/50 py-6 px-8">Description</th>
                                <th class="text-[10px] font-black uppercase tracking-widest text-base-content/50 py-6 px-8">Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="log in logs.data" :key="log.id" class="hover:bg-base-200/30 transition-colors duration-200">
                                <td class="py-5 px-8">
                                    <span class="text-xs font-bold text-base-content/60">{{ formatDate(log.created_at) }}</span>
                                </td>
                                <td class="py-5 px-8">
                                    <div class="flex items-center space-x-3">
                                        <div class="h-8 w-8 rounded-xl bg-primary text-white flex items-center justify-center font-bold text-xs shadow-lg shadow-primary/20">
                                            {{ log.causer?.name?.charAt(0) || '?' }}
                                        </div>
                                        <div>
                                            <div class="text-xs font-black text-base-content">{{ log.causer?.name || 'System' }}</div>
                                            <div class="text-[9px] font-bold text-base-content/40">{{ log.causer?.email || '-' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-5 px-8">
                                    <span :class="['badge badge-sm font-black uppercase tracking-widest py-3 px-4 rounded-xl', getEventColor(log.event)]">
                                        {{ log.event?.replace('_', ' ') || 'Log' }}
                                    </span>
                                </td>
                                <td class="py-5 px-8">
                                    <p class="text-xs font-bold text-base-content/80">{{ log.description }}</p>
                                </td>
                                <td class="py-5 px-8">
                                    <div class="text-[10px] font-medium text-base-content/40 max-w-xs truncate">
                                        {{ log.subject_type?.split('\\').pop() }} #{{ log.subject_id }}
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="logs.data.length === 0">
                                <td colspan="5" class="py-20 text-center">
                                    <div class="flex flex-col items-center opacity-20">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-16 h-16 mb-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                                        </svg>
                                        <p class="text-lg font-black uppercase tracking-widest">No Activities Found</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Footer with Pagination -->
                <div v-if="logs.links && logs.links.length > 3" class="bg-base-200/30 p-8 border-t border-base-300">
                    <div class="flex items-center justify-between">
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-base-content/70 font-medium">
                                    Showing <span class="font-black text-base-content">{{ logs.from || 0 }}</span> to <span class="font-black text-base-content">{{ logs.to || 0 }}</span> of <span class="font-black text-base-content">{{ logs.total }}</span> results
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-xl shadow-sm -space-x-px" aria-label="Pagination">
                                    <template v-for="(link, i) in logs.links" :key="i">
                                        <Link
                                            v-if="link.url"
                                            :href="link.url"
                                            v-html="link.label"
                                            class="relative inline-flex items-center px-4 py-2 border text-xs font-black transition-all duration-200"
                                            :class="[
                                                link.active ? 'z-10 text-white border-transparent bg-primary shadow-lg shadow-primary/20' : 'bg-base-100 border-base-300 text-base-content/70 hover:bg-base-200',
                                                i === 0 ? 'rounded-l-xl' : '',
                                                i === logs.links.length - 1 ? 'rounded-r-xl' : ''
                                            ]"
                                        />
                                        <span
                                            v-else
                                            v-html="link.label"
                                            class="relative inline-flex items-center px-4 py-2 border border-base-300 bg-base-100 text-xs font-black text-base-content/30 cursor-not-allowed"
                                            :class="[
                                                i === 0 ? 'rounded-l-xl' : '',
                                                i === logs.links.length - 1 ? 'rounded-r-xl' : ''
                                            ]"
                                        ></span>
                                    </template>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.5s ease-out;
}

.animate-slide-up {
    animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUp {
    from { 
        opacity: 0;
        transform: translateY(20px);
    }
    to { 
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
