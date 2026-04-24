<script setup>
import { computed } from 'vue';
import { usePage, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    featureName: {
        type: String,
        default: 'This feature'
    }
});

const page = usePage();
const primaryColor = computed(() => page.props.branding_computed?.primary_color || '#0ea5e9');
const contrastColor = computed(() => page.props.branding_computed?.contrast_color || '#ffffff');
</script>

<template>
    <div class="flex flex-col items-center justify-center min-h-[60vh] text-center px-4 animate-fade-in">
        <div 
            class="w-24 h-24 rounded-3xl flex items-center justify-center mb-8 shadow-2xl rotate-3 hover:rotate-0 transition-transform duration-500"
            :style="{ backgroundColor: primaryColor + '10', border: `2px dashed ${primaryColor}` }"
        >
            <div 
                class="w-16 h-16 rounded-2xl flex items-center justify-center shadow-lg"
                :style="{ backgroundColor: primaryColor }"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-8 h-8" :style="{ color: contrastColor }">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.423 20.35a2.124 2.124 0 0 0 2.154 0l6.598-3.811a2.125 2.125 0 0 0 1.062-1.839V7.3a2.125 2.125 0 0 0-1.062-1.839l-6.598-3.811a2.124 2.124 0 0 0-2.154 0L4.825 5.461a2.125 2.125 0 0 0-1.062 1.839v7.39c0 .732.378 1.41 1.062 1.839l6.598 3.811Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 2.25V15m0 0-3-3m3 3 3-3" />
                </svg>
            </div>
        </div>

        <h2 class="text-4xl font-black text-base-content uppercase tracking-tighter mb-4">
            Under <span :style="{ color: primaryColor }">Maintenance</span>
        </h2>
        
        <p class="text-base-content/50 max-w-lg mx-auto font-medium text-lg leading-relaxed mb-8">
            We are currently polishing and improving the <span class="text-base-content font-bold underline decoration-primary decoration-2 underline-offset-4">{{ featureName }}</span> module to provide you with a better experience.
        </p>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 w-full max-w-2xl mb-12">
            <div class="bg-base-100 p-6 rounded-3xl border border-base-300 shadow-sm">
                <div class="text-primary font-black text-2xl mb-1">UI</div>
                <div class="text-[10px] uppercase tracking-widest font-bold opacity-30">Improvements</div>
            </div>
            <div class="bg-base-100 p-6 rounded-3xl border border-base-300 shadow-sm">
                <div class="text-primary font-black text-2xl mb-1">New</div>
                <div class="text-[10px] uppercase tracking-widest font-bold opacity-30">Capabilities</div>
            </div>
            <div class="bg-base-100 p-6 rounded-3xl border border-base-300 shadow-sm">
                <div class="text-primary font-black text-2xl mb-1">Core</div>
                <div class="text-[10px] uppercase tracking-widest font-bold opacity-30">Optimizations</div>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-center gap-4">
            <Link 
                :href="route('tenant.dashboard')"
                class="btn btn-primary px-10 rounded-2xl font-black uppercase tracking-widest shadow-lg shadow-primary/20"
            >
                Back to Dashboard
            </Link>
            <button 
                @click="router.reload()"
                class="btn btn-ghost px-10 rounded-2xl font-black uppercase tracking-widest"
            >
                Check Status
            </button>
        </div>
        
        <div class="mt-16 pt-8 border-t border-base-300 w-full max-w-md">
            <div class="flex items-center justify-center gap-2 text-[10px] font-black uppercase tracking-widest opacity-20">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3 h-3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                </svg>
                Expected downtime is minimal
            </div>
        </div>
    </div>
</template>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.8s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
