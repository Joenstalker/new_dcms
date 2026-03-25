<script setup>
import { ref, computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    pending_updates: {
        type: Array,
        default: () => []
    },
    subscription: {
        type: Object,
        default: null
    }
});

const isApplying = ref(false);
const applyingIds = ref([]);

const form = useForm({
    feature_ids: [],
});

const hasUpdates = computed(() => props.pending_updates?.length > 0);

const pendingUpdates = computed(() => {
    return props.pending_updates?.map(update => ({
        ...update,
        feature: {
            ...update.feature,
            // Ensure all required fields exist
            name: update.feature?.name || 'Unknown Feature',
            description: update.feature?.description || '',
            implementation_status: update.feature?.implementation_status || 'coming_soon',
            code_identifier: update.feature?.code_identifier || null,
        }
    })) || [];
});

const selectAll = () => {
    if (form.feature_ids.length === pendingUpdates.value.length) {
        form.feature_ids = [];
    } else {
        form.feature_ids = pendingUpdates.value.map(u => u.feature.id);
    }
};

const applyUpdates = () => {
    if (form.feature_ids.length === 0) return;
    
    isApplying.value = true;
    applyingIds.value = [...form.feature_ids];
    
    form.post(route('settings.updates.apply'), {
        onSuccess: () => {
            isApplying.value = false;
            applyingIds.value = [];
        },
        onError: () => {
            isApplying.value = false;
            applyingIds.value = [];
        }
    });
};

const getStatusBadge = (status) => {
    const badges = {
        'coming_soon': { label: 'Coming Soon', class: 'badge-warning', icon: '⏳' },
        'in_development': { label: 'In Development', class: 'badge-info', icon: '🔧' },
        'active': { label: 'Ready', class: 'badge-success', icon: '✅' },
    };
    return badges[status] || { label: status, class: 'badge-ghost', icon: '❓' };
};

const isApplyingFeature = (featureId) => {
    return applyingIds.value.includes(featureId);
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="System Updates" />
        
        <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-gray-900">System Updates</h2>
                <p class="mt-1 text-sm text-gray-600">
                    New features available for your clinic. Click Update to apply.
                </p>
            </div>

            <!-- Plan Info -->
            <div v-if="subscription" class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <p class="text-sm text-blue-800">
                    <strong>Current Plan:</strong> {{ subscription.plan_name }}
                </p>
            </div>

            <!-- No Updates -->
            <div v-if="!hasUpdates" class="text-center py-16 bg-gray-50 rounded-lg border-2 border-dashed border-gray-200">
                <svg class="mx-auto h-16 w-16 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900">You're all up to date!</h3>
                <p class="mt-2 text-gray-500 max-w-sm mx-auto">
                    No new updates available at this time. Check back later for new features.
                </p>
                <div class="mt-6">
                    <a :href="route('settings.features')" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        View Your Features
                    </a>
                </div>
            </div>

            <!-- Updates List -->
            <div v-else class="space-y-4">
                <!-- Select All -->
                <div class="flex items-center justify-between bg-gray-50 p-4 rounded-lg border border-gray-200">
                    <label class="flex items-center cursor-pointer">
                        <input 
                            type="checkbox" 
                            :checked="form.feature_ids.length === pendingUpdates.length && pendingUpdates.length > 0"
                            @change="selectAll"
                            class="checkbox checkbox-primary"
                        />
                        <span class="ml-3 font-medium text-gray-700">Select All Updates</span>
                    </label>
                    <span class="text-sm text-gray-500">{{ pendingUpdates.length }} update(s) available</span>
                </div>

                <!-- Update Cards -->
                <div 
                    v-for="update in pendingUpdates" 
                    :key="update.id"
                    class="border-2 rounded-lg p-5 hover:shadow-md transition-all duration-200"
                    :class="form.feature_ids.includes(update.feature.id) ? 'border-blue-500 bg-blue-50' : 'border-gray-200 bg-white'"
                >
                    <div class="flex items-start gap-4">
                        <input 
                            type="checkbox" 
                            v-model="form.feature_ids"
                            :value="update.feature.id"
                            class="checkbox checkbox-primary mt-1"
                            :disabled="isApplying || update.feature.implementation_status !== 'active'"
                        />
                        
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2 flex-wrap">
                                <h3 class="font-semibold text-lg text-gray-900">{{ update.feature.name }}</h3>
                                <span 
                                    class="badge badge-sm gap-1"
                                    :class="getStatusBadge(update.feature.implementation_status).class"
                                >
                                    <span>{{ getStatusBadge(update.feature.implementation_status).icon }}</span>
                                    {{ getStatusBadge(update.feature.implementation_status).label }}
                                </span>
                            </div>
                            <p class="text-gray-600 text-sm mb-3">{{ update.feature.description }}</p>
                            
                            <!-- Warning for non-active features -->
                            <div v-if="update.feature.implementation_status !== 'active'" class="text-xs text-amber-700 bg-amber-50 px-3 py-2 rounded-lg inline-block mb-2">
                                <span class="font-medium">⚠️ Note:</span> This feature is not yet fully available. You can update now but may need to wait for full functionality.
                            </div>
                            
                            <!-- Code identifier if available -->
                            <div v-if="update.feature.code_identifier" class="text-xs text-gray-400 mt-2">
                                Feature ID: <code class="bg-gray-100 px-1 rounded">{{ update.feature.code_identifier }}</code>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Apply Button -->
                <div class="sticky bottom-4 bg-white border-t-2 border-gray-200 pt-4 mt-6 shadow-lg rounded-lg p-4">
                    <button 
                        @click="applyUpdates"
                        :disabled="form.feature_ids.length === 0 || isApplying"
                        class="btn btn-primary w-full text-lg"
                        :class="{ 'loading': isApplying }"
                    >
                        <svg v-if="!isApplying" class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        {{ isApplying ? 'Applying Updates...' : `Apply Selected Updates (${form.feature_ids.length})` }}
                    </button>
                    
                    <p v-if="form.feature_ids.length === 0" class="text-center text-sm text-gray-500 mt-2">
                        {{ pendingUpdates.every(u => u.feature.implementation_status !== 'active') 
                            ? 'These roadmap features will be available soon.' 
                            : 'Select at least one active update to apply' 
                        }}
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
