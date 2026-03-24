<script setup>
defineProps({
    patient: { type: Object, required: true },
    tabName: { type: String, required: true }
});
</script>

<template>
    <div class="h-full flex flex-col items-center justify-center p-12 text-center text-base-content/40">
        <svg class="w-16 h-16 mb-4 opacity-20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
        </svg>
        <p class="text-sm font-black uppercase tracking-widest">{{ tabName }} coming soon</p>
        <p class="text-xs mt-2 max-w-xs">Data integration for this section is pending in the next phase.</p>
    </div>
</template>
