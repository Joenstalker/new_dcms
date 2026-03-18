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
        <div v-if="feature" class="p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-gray-900">
                    {{ feature.name }}
                </h3>
                <button @click="emit('close')" class="text-gray-400 hover:text-gray-500">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Feature Details -->
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Key</p>
                        <p class="text-sm font-medium text-gray-900">{{ feature.key }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Type</p>
                        <p class="text-sm font-medium text-gray-900">{{ getTypeLabel(feature.type) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Category</p>
                        <p class="text-sm font-medium text-gray-900">{{ categoryLabels[feature.category] || feature.category }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Status</p>
                        <span 
                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                            :class="feature.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                        >
                            {{ feature.is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                </div>
                <div v-if="feature.description" class="mt-4">
                    <p class="text-xs text-gray-500 uppercase">Description</p>
                    <p class="text-sm text-gray-700">{{ feature.description }}</p>
                </div>
                <div v-if="feature.options && feature.options.length" class="mt-4">
                    <p class="text-xs text-gray-500 uppercase">Options</p>
                    <p class="text-sm text-gray-700">{{ feature.options.join(', ') }}</p>
                </div>
            </div>

            <!-- Plan Assignments Preview -->
            <h4 class="text-sm font-semibold text-gray-900 mb-4">Plan Assignments Preview</h4>
            <div class="space-y-3">
                <div 
                    v-for="plan in plans" 
                    :key="plan.id"
                    class="flex items-center justify-between p-3 rounded-lg border"
                    :class="getFeatureValueForPlan(feature, plan) ? 'bg-green-50 border-green-200' : 'bg-gray-50 border-gray-200'"
                >
                    <div class="flex items-center gap-3">
                        <div 
                            class="h-8 w-8 rounded-full flex items-center justify-center"
                            :class="getFeatureValueForPlan(feature, plan) ? 'bg-green-100 text-green-600' : 'bg-gray-200 text-gray-500'"
                        >
                            <svg v-if="getFeatureValueForPlan(feature, plan)" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <svg v-else class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ plan.name }}</p>
                            <p class="text-xs text-gray-500">{{ formatFeatureValue(feature, getFeatureValueForPlan(feature, plan)) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Modal>
</template>
