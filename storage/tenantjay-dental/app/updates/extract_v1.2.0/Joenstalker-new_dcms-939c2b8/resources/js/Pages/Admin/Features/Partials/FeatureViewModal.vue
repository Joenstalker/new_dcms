<script setup>
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    show: {
        type: Boolean,
        required: true,
    },
    feature: {
        type: Object,
        default: null,
    },
    plans: {
        type: Array,
        default: () => [],
    },
    getTypeLabel: {
        type: Function,
        required: true,
    },
    getFeatureValueForPlan: {
        type: Function,
        required: true,
    },
    formatFeatureValue: {
        type: Function,
        required: true,
    },
    categoryLabels: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['close']);
</script>

<template>
    <Modal :show="show" @close="emit('close')" maxWidth="lg">
        <div v-if="feature" class="p-6 bg-base-100 border border-base-300 rounded-lg">
            <div class="flex items-center justify-between mb-8">
                <h3 class="text-2xl font-black text-base-content tracking-tight">
                    {{ feature.name }}
                </h3>
                <button @click="emit('close')" class="btn btn-ghost btn-sm btn-circle text-base-content/40 hover:text-base-content/100">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Feature Details -->
            <div class="bg-base-200/50 rounded-2xl p-6 mb-8 border border-base-300">
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <p class="text-[10px] font-black text-base-content/30 uppercase tracking-widest mb-1">Key Identifier</p>
                        <p class="text-sm font-bold text-base-content">{{ feature.key }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-base-content/30 uppercase tracking-widest mb-1">Value Type</p>
                        <p class="text-sm font-bold text-base-content">{{ getTypeLabel(feature.type) }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-base-content/30 uppercase tracking-widest mb-1">Category</p>
                        <p class="text-sm font-bold text-base-content">{{ categoryLabels[feature.category] || feature.category }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-base-content/30 uppercase tracking-widest mb-1">Current Status</p>
                        <span 
                            class="badge badge-sm font-bold"
                            :class="feature.is_active ? 'badge-success bg-success/10 text-success border-success/20' : 'badge-error bg-error/10 text-error border-error/20'"
                        >
                            {{ feature.is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                </div>
                <div v-if="feature.description" class="mt-6 pt-6 border-t border-base-300">
                    <p class="text-[10px] font-black text-base-content/30 uppercase tracking-widest mb-1">Description</p>
                    <p class="text-sm text-base-content/70 leading-relaxed">{{ feature.description }}</p>
                </div>
                <div v-if="feature.options && feature.options.length" class="mt-6 pt-6 border-t border-base-300">
                    <p class="text-[10px] font-black text-base-content/30 uppercase tracking-widest mb-2 text-primary">Available Options</p>
                    <div class="flex flex-wrap gap-2">
                        <span v-for="option in feature.options" :key="option" class="badge badge-primary badge-outline badge-md font-bold">
                            {{ option }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Plan Assignments Preview -->
            <h4 class="text-xs font-black text-base-content/50 uppercase tracking-widest mb-4">Availability Across Subscription Plans</h4>
            <div class="space-y-3">
                <div 
                    v-for="plan in plans" 
                    :key="plan.id"
                    class="flex items-center justify-between p-4 rounded-xl border transition-all duration-300"
                    :class="getFeatureValueForPlan(feature, plan) ? 'bg-success/5 border-success/30' : 'bg-base-200/50 border-base-300'"
                >
                    <div class="flex items-center gap-4">
                        <div 
                            class="h-10 w-10 rounded-full flex items-center justify-center border shadow-sm"
                            :class="getFeatureValueForPlan(feature, plan) ? 'bg-success text-success-content border-success' : 'bg-base-300 text-base-content/30 border-base-300'"
                        >
                            <svg v-if="getFeatureValueForPlan(feature, plan)" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            <svg v-else class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-base-content">{{ plan.name }}</p>
                            <p class="text-xs font-medium" :class="getFeatureValueForPlan(feature, plan) ? 'text-success' : 'text-base-content/40'">
                                {{ formatFeatureValue(feature, getFeatureValueForPlan(feature, plan)) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Modal>
</template>
