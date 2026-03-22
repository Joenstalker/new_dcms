<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    batchId: {
        type: String,
        required: true,
    },
    title: {
        type: String,
        default: 'Syncing...',
    },
});

const emit = defineEmits(['finished', 'error']);

const progress = ref(0);
const details = ref(null);
const isFinished = ref(false);
const intervalId = ref(null);

const fetchStatus = async () => {
    try {
        const response = await axios.get(route('admin.features.batch-status', props.batchId));
        const data = response.data;
        
        if (data.status === 'not_found') {
            stopPolling();
            emit('error', 'Batch not found.');
            return;
        }

        progress.value = data.progress;
        details.value = data;

        if (data.finished || data.cancelled) {
            isFinished.value = true;
            stopPolling();
            emit('finished', data);
        }
    } catch (error) {
        console.error('Error fetching batch status:', error);
        stopPolling();
        emit('error', 'Failed to fetch status.');
    }
};

const startPolling = () => {
    fetchStatus(); // Initial fetch
    intervalId.value = setInterval(fetchStatus, 2000); // Every 2 seconds
};

const stopPolling = () => {
    if (intervalId.value) {
        clearInterval(intervalId.value);
        intervalId.value = null;
    }
};

onMounted(startPolling);
onUnmounted(stopPolling);
</script>

<template>
    <div class="bg-base-100 border border-base-300 rounded-2xl p-6 shadow-sm mb-6 animate-in fade-in slide-in-from-top-4 duration-500">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <div class="h-8 w-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                    <svg v-if="!isFinished" class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <svg v-else class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-base-content">{{ title }}</h4>
                    <p class="text-[10px] font-black text-base-content/30 uppercase tracking-widest">
                        Batch ID: {{ batchId.substring(0, 8) }}...
                    </p>
                </div>
            </div>
            <div class="text-right">
                <span class="text-2xl font-black text-base-content tracking-tighter">{{ progress }}%</span>
            </div>
        </div>

        <!-- Progress Bar Background -->
        <div class="w-full bg-base-300 rounded-full h-3 overflow-hidden">
            <!-- Animated Progress Fill -->
            <div 
                class="bg-primary h-full transition-all duration-700 ease-out relative"
                :style="{ width: `${progress}%` }"
            >
                <div class="absolute inset-0 bg-white/20 animate-pulse"></div>
            </div>
        </div>

        <div v-if="details" class="mt-4 flex items-center justify-between text-[11px] font-bold text-base-content/50 uppercase tracking-tight">
            <div class="flex gap-4">
                <span>Total: {{ details.total_jobs }}</span>
                <span class="text-success">Processed: {{ details.processed_jobs }}</span>
                <span v-if="details.failed_jobs > 0" class="text-error">Failed: {{ details.failed_jobs }}</span>
            </div>
            <div v-if="isFinished" class="text-success flex items-center gap-1">
                <span class="w-1.5 h-1.5 rounded-full bg-success"></span>
                COMPLETED
            </div>
        </div>
    </div>
</template>
