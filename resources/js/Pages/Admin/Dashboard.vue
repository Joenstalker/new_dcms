<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    stats: {
        type: Object,
        required: true,
    },
});

const statCards = [
    { key: 'total_clinics', label: 'Total Clinics', icon: 'building', textColor: 'text-primary', bgLight: 'bg-primary/10' },
    { key: 'active_subscriptions', label: 'Active Subscriptions', icon: 'check', textColor: 'text-success', bgLight: 'bg-success/10' },
    { key: 'new_this_month', label: 'New This Month', icon: 'sparkle', textColor: 'text-info', bgLight: 'bg-info/10' },
    { key: 'suspended_clinics', label: 'Suspended', icon: 'pause', textColor: 'text-warning', bgLight: 'bg-warning/10' },
    { key: 'pending_clinics', label: 'Pending Payment', icon: 'clock', textColor: 'text-secondary', bgLight: 'bg-secondary/10' },
    { key: 'monthly_revenue', label: 'Monthly Revenue', icon: 'cash', textColor: 'text-success', bgLight: 'bg-success/10', prefix: '₱' },
];
</script>

<template>
    <Head title="Admin Dashboard" />
    <AdminLayout>
        <template #header>
            <h1 class="text-xl font-bold text-base-content">Dashboard</h1>
        </template>

        <div class="max-w-7xl mx-auto space-y-8">
            <!-- Welcome Banner -->
            <div class="bg-primary rounded-2xl p-8 text-primary-content relative overflow-hidden shadow-lg border border-primary/20">
                <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/4"></div>
                <div class="absolute bottom-0 left-1/2 w-48 h-48 bg-white/10 rounded-full translate-y-1/2"></div>
                <div class="relative">
                    <h2 class="text-2xl font-bold">Welcome back, Admin! 👋</h2>
                    <p class="mt-2 text-primary-content/80 text-sm max-w-xl">Here's an overview of your DCMS platform. Monitor tenant clinics, subscriptions, and platform health from this dashboard.</p>
                </div>
            </div>

            <!-- Stat Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                <div 
                    v-for="card in statCards" 
                    :key="card.key"
                    class="bg-base-100 rounded-2xl p-6 border border-base-300 shadow-sm hover:shadow-md hover:border-primary/30 transition-all duration-300 group"
                >
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm font-bold text-base-content/50 uppercase tracking-tight">{{ card.label }}</p>
                            <p class="mt-2 text-3xl font-black text-base-content tracking-tighter">
                                {{ card.prefix || '' }}{{ stats[card.key]?.toLocaleString() || '0' }}
                            </p>
                        </div>
                        <div :class="[card.bgLight]" class="p-4 rounded-2xl group-hover:scale-110 transition-all duration-500 ease-out">
                            <!-- Building icon -->
                            <svg v-if="card.icon === 'building'" :class="[card.textColor]" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                            </svg>
                            <!-- Check icon -->
                            <svg v-if="card.icon === 'check'" :class="[card.textColor]" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            <!-- Sparkle icon -->
                            <svg v-if="card.icon === 'sparkle'" :class="[card.textColor]" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 0 0-2.455 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z" />
                            </svg>
                            <!-- Pause icon -->
                            <svg v-if="card.icon === 'pause'" :class="[card.textColor]" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.25 9v6m-4.5 0V9M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            <!-- Clock icon -->
                            <svg v-if="card.icon === 'clock'" :class="[card.textColor]" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            <!-- Cash icon -->
                            <svg v-if="card.icon === 'cash'" :class="[card.textColor]" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Placeholder Sections -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Recent Activity -->
                <div class="bg-base-100 rounded-2xl p-6 border border-base-300 shadow-sm">
                    <h3 class="text-lg font-semibold text-base-content mb-4">Recent Activity</h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-center h-40 text-base-content/40">
                            <div class="text-center">
                                <svg class="w-12 h-12 mx-auto mb-2 text-base-content/30" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                                <p class="text-sm">Activity feed coming in Phase 2</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Overview -->
                <div class="bg-base-100 rounded-2xl p-6 border border-base-300 shadow-sm">
                    <h3 class="text-lg font-semibold text-base-content mb-4">Subscription Distribution</h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-center h-40 text-base-content/40">
                            <div class="text-center">
                                <svg class="w-12 h-12 mx-auto mb-2 text-base-content/30" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 1 0 7.5 7.5h-7.5V6Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0 0 13.5 3v7.5Z" />
                                </svg>
                                <p class="text-sm">Charts coming in Phase 4</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
