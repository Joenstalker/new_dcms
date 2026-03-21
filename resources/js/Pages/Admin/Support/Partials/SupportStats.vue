<script setup>
defineProps({
    stats: {
        type: Object,
        required: true
    },
    loading: {
        type: Boolean,
        default: false
    }
});
</script>

<template>
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <!-- Total Messages -->
        <div class="bg-base-100 rounded-2xl shadow-sm border border-base-300 p-6 transition-all duration-300 hover:shadow-md">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[10px] font-black text-base-content/30 uppercase tracking-widest">Total Messages</p>
                    <p v-if="!loading" class="text-3xl font-black text-base-content mt-1 tracking-tight">{{ stats.total }}</p>
                    <div v-else class="h-10 w-16 bg-base-300 rounded-lg animate-pulse mt-1"></div>
                </div>
                <div class="w-14 h-14 bg-info/10 rounded-2xl flex items-center justify-center border border-info/20 shadow-sm shadow-info/5 text-info">
                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Unread -->
        <div class="bg-base-100 rounded-2xl shadow-sm border border-base-300 p-6 transition-all duration-300 hover:shadow-md">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[10px] font-black text-base-content/30 uppercase tracking-widest">Pending Review</p>
                    <p v-if="!loading" class="text-3xl font-black text-warning mt-1 tracking-tight">{{ stats.unread }}</p>
                    <div v-else class="h-10 w-16 bg-base-300 rounded-lg animate-pulse mt-1"></div>
                </div>
                <div class="w-14 h-14 bg-warning/10 rounded-2xl flex items-center justify-center border border-warning/20 shadow-sm shadow-warning/5 text-warning">
                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Replied -->
        <div class="bg-base-100 rounded-2xl shadow-sm border border-base-300 p-6 transition-all duration-300 hover:shadow-md">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[10px] font-black text-base-content/30 uppercase tracking-widest">Successfully Replied</p>
                    <p v-if="!loading" class="text-3xl font-black text-success mt-1 tracking-tight">{{ stats.replied }}</p>
                    <div v-else class="h-10 w-16 bg-base-300 rounded-lg animate-pulse mt-1"></div>
                </div>
                <div class="w-14 h-14 bg-success/10 rounded-2xl flex items-center justify-center border border-success/20 shadow-sm shadow-success/5 text-success">
                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Archived -->
        <div class="bg-base-100 rounded-2xl shadow-sm border border-base-300 p-6 transition-all duration-300 hover:shadow-md">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[10px] font-black text-base-content/30 uppercase tracking-widest">Archived Support</p>
                    <p v-if="!loading" class="text-3xl font-black text-base-content/50 mt-1 tracking-tight">{{ stats.archived }}</p>
                    <div v-else class="h-10 w-16 bg-base-300 rounded-lg animate-pulse mt-1"></div>
                </div>
                <div class="w-14 h-14 bg-base-200 rounded-2xl flex items-center justify-center border border-base-300 shadow-sm text-base-content/40">
                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                    </svg>
                </div>
            </div>
        </div>
    </div>
</template>
