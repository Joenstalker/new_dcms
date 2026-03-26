<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Checkbox from '@/Components/Checkbox.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    plan: {
        type: Object,
        required: true,
    },
    allFeatures: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['submit', 'cancel', 'delete']);

const page = usePage();
const primaryColor = page.props.branding?.primary_color || '#0ea5e9';

// Track which feature IDs were actually ACTIVE (assigned=true) when the modal opened
const originalActiveFeatures = new Set(
    props.plan.features?.filter(f => {
        // Boolean features must be explicitly true. Others count if they exist.
        const featureDef = props.allFeatures.find(def => def.id === f.id);
        if (featureDef?.type === 'boolean') {
            return Boolean(f.pivot.value_boolean);
        }
        return true;
    }).map(f => f.id) || []
);

// Initialize form with plan data and features
const form = useForm({
    name: props.plan.name,
    price_monthly: props.plan.price_monthly,
    price_yearly: props.plan.price_yearly,
    yearly_discount_percent: props.plan.yearly_discount_percent || 0,
    max_users: props.plan.max_users,
    max_patients: props.plan.max_patients === null ? 0 : props.plan.max_patients,
    max_appointments: props.plan.max_appointments === null ? 0 : props.plan.max_appointments,
    max_storage_mb: props.plan.max_storage_mb ?? 500,
    has_qr_booking: Boolean(props.plan.has_qr_booking),
    has_sms: Boolean(props.plan.has_sms),
    has_branding: Boolean(props.plan.has_branding),
    has_analytics: Boolean(props.plan.has_analytics),
    has_priority_support: Boolean(props.plan.has_priority_support),
    has_multi_branch: Boolean(props.plan.has_multi_branch),
    report_level: props.plan.report_level || 'basic',
    // Dynamic features map: { feature_id: { assigned: bool, value: mixed } }
    features: props.allFeatures.map(feature => {
        const assigned = props.plan.features?.find(f => f.id === feature.id);
        
        // Use the same logic as above for consistency
        const isAssigned = originalActiveFeatures.has(feature.id);

        return {
            id: feature.id,
            key: feature.key,
            name: feature.name,
            description: feature.description,
            type: feature.type,
            assigned: isAssigned,
            value: assigned ? (assigned.pivot.value_boolean || assigned.pivot.value_numeric || assigned.pivot.value_tier) : null
        };
    })
});

const activeTab = ref('base');

const submit = () => {
    // Check if any feature was EXPLICITLY unassigned during this session
    const unassignedFeatures = form.features.filter((f) => {
        const wasOriginallyActive = originalActiveFeatures.has(f.id);
        const isNowInactive = !f.assigned;
        return wasOriginallyActive && isNowInactive;
    });

    if (unassignedFeatures.length > 0) {
        Swal.fire({
            title: 'Confirm Feature Removal',
            text: `You are about to remove ${unassignedFeatures.length} features from this plan. This will immediately restrict access for all subscribed tenants. Proceed?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, remove them',
        }).then((result) => {
            if (result.isConfirmed) {
                form.put(route('admin.plans.update', props.plan.id), {
                    onSuccess: () => emit('submit'),
                });
            }
        });
    } else {
        form.put(route('admin.plans.update', props.plan.id), {
            onSuccess: () => emit('submit'),
        });
    }
};

const toggleFeature = (index) => {
    form.features[index].assigned = !form.features[index].assigned;
};

// Auto-calculate yearly price when monthly or discount changes
watch(() => [form.price_monthly, form.yearly_discount_percent], ([newMonthly, newDiscount]) => {
    if (newMonthly) {
        const monthly = parseFloat(newMonthly);
        const discount = parseFloat(newDiscount) || 0;
        const baseYearly = monthly * 12;
        const discountAmount = baseYearly * (discount / 100);
        form.price_yearly = (baseYearly - discountAmount).toFixed(2);
    }
});
</script>

<template>
    <div class="bg-base-100 rounded-xl overflow-hidden flex flex-col max-h-[90vh]">
        <!-- Header -->
        <header class="p-6 border-b border-base-300 flex items-center justify-between bg-base-200/30">
            <div>
                <h2 class="text-xl font-bold flex items-center">
                    <svg class="w-6 h-6 mr-2" :style="{ color: primaryColor }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                    </svg>
                    Manage <span class="ml-1.5 uppercase tracking-tight" :style="{ color: primaryColor }">{{ plan.name }}</span> Plan
                </h2>
                <p class="text-xs text-base-content/50 mt-1">Configure pricing, limits, and system-wide features.</p>
            </div>
            <button @click="$emit('cancel')" class="btn btn-ghost btn-sm btn-circle">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </header>

        <!-- Tabs -->
        <div class="flex border-b border-base-300 px-6 bg-base-100">
            <button 
                @click="activeTab = 'base'" 
                :class="['px-4 py-3 text-sm font-bold border-b-2 transition-all', activeTab === 'base' ? 'border-primary text-primary' : 'border-transparent text-base-content/40 hover:text-base-content/70']"
                :style="activeTab === 'base' ? { borderColor: primaryColor, color: primaryColor } : {}"
            >
                1. Base Details
            </button>
            <button 
                @click="activeTab = 'limits'" 
                :class="['px-4 py-3 text-sm font-bold border-b-2 transition-all', activeTab === 'limits' ? 'border-primary text-primary' : 'border-transparent text-base-content/40 hover:text-base-content/70']"
                :style="activeTab === 'limits' ? { borderColor: primaryColor, color: primaryColor } : {}"
            >
                2. Usage Limits
            </button>
            <button 
                @click="activeTab = 'features'" 
                :class="['px-4 py-3 text-sm font-bold border-b-2 transition-all', activeTab === 'features' ? 'border-primary text-primary' : 'border-transparent text-base-content/40 hover:text-base-content/70']"
                :style="activeTab === 'features' ? { borderColor: primaryColor, color: primaryColor } : {}"
            >
                3. Features
            </button>
        </div>

        <!-- Body -->
        <div class="p-6 overflow-y-auto flex-1 custom-scrollbar">
            <form @submit.prevent="submit" class="space-y-6">
                <!-- Section 1: Base Details -->
                <div v-show="activeTab === 'base'" class="space-y-6 animate-in fade-in slide-in-from-bottom-2 duration-300">
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <InputLabel for="name" value="Plan Name" class="text-xs font-bold text-base-content/60" />
                            <TextInput 
                                id="name" 
                                type="text" 
                                class="mt-1 block w-full bg-base-100 border-base-300 focus:border-primary focus:ring-primary" 
                                v-model="form.name" 
                                required
                            />
                            <p class="text-[10px] text-base-content/40 mt-1.5 italic">Plan name will be synchronized with Stripe automatically.</p>
                        </div>

                        <div class="grid grid-cols-3 gap-6 pt-2">
                            <div class="space-y-1">
                                <InputLabel for="price_monthly" value="Monthly Subscription (₱)" class="text-xs font-bold text-base-content/60" />
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-base-content/30 font-bold">₱</span>
                                    <TextInput id="price_monthly" type="number" step="0.01" class="pl-7 block w-full text-base font-bold bg-base-100 border-base-300" v-model="form.price_monthly" required />
                                </div>
                                <InputError :message="form.errors.price_monthly" />
                            </div>
                            <div class="space-y-1">
                                <InputLabel for="yearly_discount_percent" value="Yearly Discount (%)" class="text-xs font-bold text-base-content/60" />
                                <div class="relative">
                                    <TextInput id="yearly_discount_percent" type="number" min="0" max="100" step="0.5" class="pr-8 block w-full text-base font-bold bg-base-100 border-base-300" v-model="form.yearly_discount_percent" />
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-sm text-base-content/30 font-bold">%</span>
                                </div>
                                <InputError :message="form.errors.yearly_discount_percent" />
                            </div>
                            <div class="space-y-1">
                                <InputLabel for="price_yearly" value="Yearly Subscription (₱)" class="text-xs font-bold text-base-content/60" />
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-base-content/30 font-bold">₱</span>
                                    <TextInput id="price_yearly" type="number" step="0.01" class="pl-7 block w-full text-base font-bold bg-base-100 border-base-300" v-model="form.price_yearly" required />
                                </div>
                                <InputError :message="form.errors.price_yearly" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Usage Limits -->
                <div v-show="activeTab === 'limits'" class="space-y-4 animate-in fade-in slide-in-from-bottom-2 duration-300">
                    <div class="grid grid-cols-1 gap-3">
                        <div class="flex items-center justify-between p-4 bg-base-200/30 rounded-xl border border-base-300 hover:border-primary/30 transition-colors group">
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center mr-4 group-hover:scale-110 transition-transform">
                                    <svg class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                                </div>
                                <div>
                                    <InputLabel for="max_users" value="User Accounts" class="text-sm font-bold text-base-content/80" />
                                    <p class="text-[10px] text-base-content/50">Maximum staff/dentist members</p>
                                </div>
                            </div>
                            <TextInput id="max_users" type="number" min="1" class="w-24 text-center font-bold bg-base-100 border-base-300" v-model="form.max_users" required />
                        </div>

                        <div class="flex items-center justify-between p-4 bg-base-200/30 rounded-xl border border-base-300 hover:border-primary/30 transition-colors group">
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-lg bg-success/10 flex items-center justify-center mr-4 group-hover:scale-110 transition-transform">
                                    <svg class="w-5 h-5 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                </div>
                                <div>
                                    <InputLabel for="max_patients" value="Patient Records" class="text-sm font-bold text-base-content/80" />
                                    <p class="text-[10px] text-base-content/50">0 for unlimited</p>
                                </div>
                            </div>
                            <TextInput id="max_patients" type="number" min="0" class="w-24 text-center font-bold bg-base-100 border-base-300" v-model="form.max_patients" required />
                        </div>

                        <div class="flex items-center justify-between p-4 bg-base-200/30 rounded-xl border border-base-300 hover:border-primary/30 transition-colors group">
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-lg bg-info/10 flex items-center justify-center mr-4 group-hover:scale-110 transition-transform">
                                    <svg class="w-5 h-5 text-info" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                </div>
                                <div>
                                    <InputLabel for="max_appointments" value="Appointments" class="text-sm font-bold text-base-content/80" />
                                    <p class="text-[10px] text-base-content/50">Monthly volume. 0 for unlimited</p>
                                </div>
                            </div>
                            <TextInput id="max_appointments" type="number" min="0" class="w-24 text-center font-bold bg-base-100 border-base-300" v-model="form.max_appointments" required />
                        </div>

                        <div class="flex items-center justify-between p-4 bg-base-200/30 rounded-xl border border-base-300 hover:border-primary/30 transition-colors group">
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-lg bg-warning/10 flex items-center justify-center mr-4 group-hover:scale-110 transition-transform">
                                    <svg class="w-5 h-5 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                                </div>
                                <div>
                                    <InputLabel for="max_storage_mb" value="File Storage Capacity" class="text-sm font-bold text-base-content/80" />
                                    <p class="text-[10px] text-base-content/50">For patient photos and documents</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <TextInput id="max_storage_mb" type="number" min="1" class="w-24 text-center font-bold bg-base-100 border-base-300" v-model="form.max_storage_mb" required />
                                <span class="text-xs font-bold text-base-content/40">MB</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Features -->
                <div v-show="activeTab === 'features'" class="space-y-4 animate-in fade-in slide-in-from-bottom-2 duration-300">
                    <div class="bg-base-200/30 rounded-xl border border-base-300 divide-y divide-base-300/50">
                        <div v-for="(feature, index) in form.features" :key="feature.id" 
                            class="flex items-center justify-between p-4 hover:bg-base-100 transition-colors cursor-pointer group"
                            @click="toggleFeature(index)"
                        >
                            <div class="flex items-start">
                                <div :class="['w-8 h-8 rounded-lg flex items-center justify-center mr-3 mt-0.5 transition-colors', feature.assigned ? 'bg-success/10 text-success' : 'bg-base-300 text-base-content/20']">
                                    <svg v-if="feature.assigned" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                    <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" /></svg>
                                </div>
                                <div class="max-w-md">
                                    <h4 :class="['text-xs font-bold transition-colors', feature.assigned ? 'text-base-content' : 'text-base-content/40']">{{ feature.name }}</h4>
                                    <p class="text-[10px] text-base-content/40 leading-snug mt-0.5 line-clamp-1 group-hover:line-clamp-none transition-all">{{ feature.description }}</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-3">
                                <span v-if="feature.assigned" class="text-[9px] font-bold text-success uppercase tracking-widest bg-success/10 px-2 py-0.5 rounded border border-success/20">Active</span>
                                <Checkbox 
                                    :checked="feature.assigned" 
                                    @update:checked="feature.assigned = $event" 
                                    class="checkbox-primary"
                                    @click.stop
                                />
                            </div>
                        </div>
                    </div>

                    <div class="p-4 bg-info/5 border border-info/20 rounded-xl mt-6">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-info mr-3 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <div>
                                <h5 class="text-xs font-bold text-info">Dynamic Feature Management</h5>
                                <p class="text-[10px] text-info/70 mt-1">Changes to features are staging-capable. Use the "Push Updates" button on the card after saving to transmit changes to active tenants.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Footer -->
        <footer class="p-6 border-t border-base-300 bg-base-100 flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <button 
                    @click="$emit('delete')" 
                    class="btn btn-ghost btn-sm text-error/60 hover:text-error hover:bg-error/10 border-transparent normal-case group transition-all"
                >
                    <svg class="w-4 h-4 mr-1.5 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                    Delete Plan
                </button>
                <div class="h-4 w-px bg-base-300 mx-1"></div>
                <SecondaryButton @click="$emit('cancel')" class="btn-sm normal-case border-base-300">
                    Discard Changes
                </SecondaryButton>
            </div>
            <div class="flex items-center space-x-3">
                <span v-if="form.isDirty" class="text-[10px] text-warning font-bold animate-pulse">Unsaved Changes</span>
                <PrimaryButton 
                    @click="submit" 
                    class="text-white border-transparent btn-sm normal-case px-8 shadow-sm transition-all hover:brightness-110" 
                    :style="{ backgroundColor: primaryColor }" 
                    :class="{ 'opacity-25': form.processing }" 
                    :disabled="form.processing"
                >
                    <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    Apply Global Updates
                </PrimaryButton>
            </div>
        </footer>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #cbd5e1;
}
</style>
