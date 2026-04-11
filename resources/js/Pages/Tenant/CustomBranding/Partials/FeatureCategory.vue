<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    category: {
        type: String,
        required: true
    },
    categoryFeatures: {
        type: Array,
        required: true
    },
    categoryLabels: {
        type: Object,
        required: true
    },
    categoryDescriptions: {
        type: Object,
        required: true
    },
    status: {
        type: String,
        required: true
    }
});

const isProcessing = ref(null);

const formatValue = (feature) => {
    if (feature.type === 'boolean') {
        return feature.is_enabled ? 'Enabled' : 'Disabled';
    }
    if (feature.type === 'numeric') {
        if (feature.value === null) return 'Unlimited';
        return feature.value.toLocaleString();
    }
    if (feature.type === 'tiered') {
        return feature.value ? feature.value.charAt(0).toUpperCase() + feature.value.slice(1) : 'None';
    }
    return feature.value;
};

const getFeatureIcon = (key) => {
    const icons = {
        qr_booking: 'M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h2M4 12h2m-2 4h.01M12 20h.01M4 4h2v2H4V4zm10 0h2v2h-2V4z',
        appointment_scheduling: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
        patient_records: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        billing_pos: 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
        clinic_setup: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
        role_based_access: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
        max_users: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
        max_patients: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
        max_appointments: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
        sms_notifications: 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z',
        custom_branding: 'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01',
        priority_support: 'M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z',
        report_level: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
        advanced_analytics: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
        multi_branch: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
        max_storage_mb: 'M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4',
    };
    return icons[key] || icons.clinic_setup;
};

const getCategoryStatusColor = (status) => {
    switch (status) {
        case 'full': return 'bg-green-50 border-green-200';
        case 'partial': return 'bg-yellow-50 border-yellow-200';
        case 'locked': return 'bg-base-200 border-base-300';
        default: return 'bg-base-100 border-base-300';
    }
};

const updateForm = useForm({
    feature_ids: [],
});

const applyUpdate = (featureId) => {
    isProcessing.value = featureId;
    updateForm.feature_ids = [featureId];
    updateForm.post(route('settings.updates.apply'), {
        preserveScroll: true,
        onSuccess: () => {
            isProcessing.value = null;
        },
        onError: () => {
            isProcessing.value = null;
        }
    });
};
</script>

<template>
    <div 
        class="rounded-lg border"
        :class="getCategoryStatusColor(status)"
    >
        <!-- Category Header -->
        <div class="px-6 py-4 border-b" :class="status === 'locked' ? 'border-base-300' : 'border-base-300'">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-base-content">
                        {{ categoryLabels[category] || category }}
                    </h3>
                    <p class="text-sm text-base-content/60 mt-1">
                        {{ categoryDescriptions[category] }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <span 
                        v-if="status === 'full'"
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"
                    >
                        All Available
                    </span>
                    <span 
                        v-else-if="status === 'partial'"
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800"
                    >
                        Some Available
                    </span>
                    <span 
                        v-else-if="status === 'locked'"
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-base-200 text-base-content/80"
                    >
                        Upgrade to Unlock
                    </span>
                </div>
            </div>
        </div>

        <!-- Features List -->
        <div class="divide-y divide-base-300">
            <div 
                v-for="feature in categoryFeatures" 
                :key="feature.key"
                class="px-6 py-4 flex items-center justify-between"
                :class="!feature.is_enabled && !feature.has_pending_update ? 'opacity-60' : ''"
            >
                <div class="flex items-center gap-4">
                    <!-- Icon -->
                    <div 
                        class="flex-shrink-0 h-10 w-10 rounded-lg flex items-center justify-center"
                        :class="feature.is_enabled ? 'bg-primary/15 text-primary' : (feature.has_pending_update ? 'bg-warning/20 text-warning' : 'bg-base-200 text-base-content/40')"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" :d="getFeatureIcon(feature.key)" />
                        </svg>
                    </div>
                    
                    <!-- Name & Description -->
                    <div>
                        <div class="flex items-center gap-2">
                            <h4 class="text-sm font-medium text-base-content">
                                {{ feature.name }}
                            </h4>
                            <span 
                                v-if="feature.has_pending_update" 
                                class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-orange-100 text-orange-800 animate-pulse"
                            >
                                Update Available
                            </span>
                        </div>
                        <p v-if="feature.description" class="text-xs text-base-content/60">
                            {{ feature.description }}
                        </p>
                    </div>
                </div>

                <!-- Value / Status / Action -->
                <div class="flex items-center gap-3">
                    <template v-if="feature.has_pending_update">
                        <button 
                            @click="applyUpdate(feature.id)"
                            :disabled="isProcessing === feature.id"
                            class="btn btn-sm btn-warning gap-2"
                            :class="{ 'loading': isProcessing === feature.id }"
                        >
                            <svg v-if="isProcessing !== feature.id" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Apply Now
                        </button>
                    </template>
                    <template v-else>
                        <span 
                            class="text-sm font-medium"
                            :class="feature.is_enabled ? 'text-base-content' : 'text-base-content/60'"
                        >
                            {{ formatValue(feature) }}
                        </span>
                        <svg 
                            v-if="feature.is_enabled" 
                            class="h-5 w-5 text-green-500" 
                            fill="none" 
                            viewBox="0 0 24 24" 
                            stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <svg 
                            v-else 
                            class="h-5 w-5 text-base-content/40" 
                            fill="none" 
                            viewBox="0 0 24 24" 
                            stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>
