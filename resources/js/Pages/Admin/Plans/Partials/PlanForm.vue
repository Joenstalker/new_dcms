<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { onMounted } from 'vue';

const props = defineProps({
    plan: {
        type: Object,
        default: null,
    },
    processing: Boolean,
});

const emit = defineEmits(['submit', 'cancel']);

const page = usePage();
const primaryColor = page.props.branding?.primary_color || '#0ea5e9';

const form = useForm({
    name: props.plan?.name ?? '',
    price_monthly: props.plan?.price_monthly ?? 0,
    price_yearly: props.plan?.price_yearly ?? 0,
    max_users: props.plan?.max_users ?? 1,
    max_patients: props.plan?.max_patients === null ? 0 : (props.plan?.max_patients ?? 0),
    max_appointments: props.plan?.max_appointments === null ? 0 : (props.plan?.max_appointments ?? 0),
    has_qr_booking: props.plan ? Boolean(props.plan.has_qr_booking) : true,
    has_sms: props.plan ? Boolean(props.plan.has_sms) : false,
    has_branding: props.plan ? Boolean(props.plan.has_branding) : false,
    has_analytics: props.plan ? Boolean(props.plan.has_analytics) : false,
    has_priority_support: props.plan ? Boolean(props.plan.has_priority_support) : false,
    has_multi_branch: props.plan ? Boolean(props.plan.has_multi_branch) : false,
    report_level: props.plan?.report_level ?? 'basic',
});

const submit = () => {
    emit('submit', form);
};
</script>

<template>
    <form @submit.prevent="submit" class="p-6 space-y-6 bg-base-100">
        <header class="border-b border-base-300 pb-4">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold text-base-content">
                    {{ plan ? 'Edit' : 'Create' }} <span :style="{ color: primaryColor }">Subscription Plan</span>
                </h2>
                <div v-if="plan?.stripe_product_id" class="flex items-center space-x-1 px-2 py-0.5 bg-success/10 rounded text-[10px] font-bold text-success border border-success/20">
                    <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                    <span>Synced: {{ plan.stripe_product_id }}</span>
                </div>
                <div v-else-if="plan" class="px-2 py-0.5 bg-warning/10 rounded text-[10px] font-bold text-warning border border-warning/20">
                    Unsynchronized
                </div>
            </div>
            <p class="mt-1 text-xs text-base-content/50">
                Define pricing and features. Stripe Product & Prices will be created automatically.
            </p>
        </header>

        <div class="max-h-[65vh] overflow-y-auto pr-2 custom-scrollbar space-y-8">
            <!-- Basic Info -->
            <section>
                <h3 class="text-[10px] font-bold text-base-content/40 uppercase tracking-[0.2em] mb-4 flex items-center">
                    <span class="w-5 h-5 rounded-full bg-base-200 flex items-center justify-center mr-2 text-[10px] text-base-content/60">1</span> 
                    Basic Details
                </h3>
                <div class="space-y-4">
                    <div>
                        <InputLabel for="name" value="Plan Name" class="text-xs font-semibold text-base-content/70" />
                        <TextInput 
                            id="name" 
                            type="text" 
                            class="mt-1 block w-full text-sm bg-base-100 border-base-300 focus:border-primary focus:ring-primary" 
                            v-model="form.name" 
                            required 
                            placeholder="e.g. Starter, Pro, Ultimate" 
                        />
                        <p v-if="plan" class="text-[9px] text-base-content/40 mt-1 italic">Plan name changes will be pushed to Stripe.</p>
                        <InputError class="mt-1" :message="form.errors.name" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="price_monthly" value="Monthly (₱)" class="text-xs font-semibold text-base-content/70" />
                            <TextInput id="price_monthly" type="number" step="0.01" class="mt-1 block w-full text-sm bg-base-100 border-base-300 focus:border-primary focus:ring-primary" v-model="form.price_monthly" required />
                            <InputError class="mt-1" :message="form.errors.price_monthly" />
                        </div>
                        <div>
                            <InputLabel for="price_yearly" value="Yearly (₱)" class="text-xs font-semibold text-base-content/70" />
                            <TextInput id="price_yearly" type="number" step="0.01" class="mt-1 block w-full text-sm bg-base-100 border-base-300 focus:border-primary focus:ring-primary" v-model="form.price_yearly" required />
                            <InputError class="mt-1" :message="form.errors.price_yearly" />
                        </div>
                    </div>
                </div>
            </section>

            <!-- Usage Limits -->
            <section>
                <h3 class="text-[10px] font-bold text-base-content/40 uppercase tracking-[0.2em] mb-4 flex items-center">
                    <span class="w-5 h-5 rounded-full bg-base-200 flex items-center justify-center mr-2 text-[10px] text-base-content/60">2</span> 
                    Usage Limits
                </h3>
                <div class="grid grid-cols-1 gap-4">
                    <div class="flex items-center justify-between p-3 bg-base-200/30 rounded-lg border border-base-300">
                        <div>
                            <InputLabel for="max_users" value="Maximum Users" class="text-xs font-semibold text-base-content/70" />
                            <p class="text-[10px] text-base-content/50">Number of staff accounts</p>
                        </div>
                        <TextInput id="max_users" type="number" min="1" class="w-24 text-sm bg-base-100 border-base-300 focus:border-primary focus:ring-primary" v-model="form.max_users" required />
                    </div>

                    <div class="flex items-center justify-between p-3 bg-base-200/30 rounded-lg border border-base-300">
                        <div>
                            <InputLabel for="max_patients" value="Maximum Patients" class="text-xs font-semibold text-base-content/70" />
                            <p class="text-[10px] text-base-content/50">0 for unlimited</p>
                        </div>
                        <TextInput id="max_patients" type="number" min="0" class="w-24 text-sm bg-base-100 border-base-300 focus:border-primary focus:ring-primary" v-model="form.max_patients" required />
                    </div>

                    <div class="flex items-center justify-between p-3 bg-base-200/30 rounded-lg border border-base-300">
                        <div>
                            <InputLabel for="max_appointments" value="Max Appointments" class="text-xs font-semibold text-base-content/70" />
                            <p class="text-[10px] text-base-content/50">0 for unlimited</p>
                        </div>
                        <TextInput id="max_appointments" type="number" min="0" class="w-24 text-sm bg-base-100 border-base-300 focus:border-primary focus:ring-primary" v-model="form.max_appointments" required />
                    </div>
                </div>
            </section>

            <!-- Features -->
            <section>
                <h3 class="text-[10px] font-bold text-base-content/40 uppercase tracking-[0.2em] mb-4 flex items-center">
                    <span class="w-5 h-5 rounded-full bg-base-200 flex items-center justify-center mr-2 text-[10px] text-base-content/60">3</span> 
                    Features
                </h3>
                <div class="bg-base-100 rounded-xl border border-base-300 divide-y divide-base-300/50">
                    <label class="flex items-center justify-between p-3 hover:bg-base-200/50 transition-colors cursor-pointer">
                        <span class="text-xs font-medium text-base-content/70">QR Code Booking</span>
                        <Checkbox name="has_qr_booking" v-model:checked="form.has_qr_booking" class="checkbox-primary" />
                    </label>

                    <label class="flex items-center justify-between p-3 hover:bg-base-200/50 transition-colors cursor-pointer">
                        <span class="text-xs font-medium text-base-content/70">SMS Notifications</span>
                        <Checkbox name="has_sms" v-model:checked="form.has_sms" class="checkbox-primary" />
                    </label>

                    <label class="flex items-center justify-between p-3 hover:bg-base-200/50 transition-colors cursor-pointer">
                        <span class="text-xs font-medium text-base-content/70">Custom Branding</span>
                        <Checkbox name="has_branding" v-model:checked="form.has_branding" class="checkbox-primary" />
                    </label>

                    <label class="flex items-center justify-between p-3 hover:bg-base-200/50 transition-colors cursor-pointer">
                        <span class="text-xs font-medium text-base-content/70">Advanced Analytics</span>
                        <Checkbox name="has_analytics" v-model:checked="form.has_analytics" class="checkbox-primary" />
                    </label>

                    <label class="flex items-center justify-between p-3 hover:bg-base-200/50 transition-colors cursor-pointer">
                        <span class="text-xs font-medium text-base-content/70">Priority Support</span>
                        <Checkbox name="has_priority_support" v-model:checked="form.has_priority_support" class="checkbox-primary" />
                    </label>

                    <label class="flex items-center justify-between p-3 hover:bg-base-200/50 transition-colors cursor-pointer">
                        <span class="text-xs font-medium text-base-content/70">Multi-branch Ready</span>
                        <Checkbox name="has_multi_branch" v-model:checked="form.has_multi_branch" class="checkbox-primary" />
                    </label>
                </div>

                <div class="mt-4">
                    <InputLabel for="report_level" value="Analytics Level" class="text-xs font-semibold text-base-content/70" />
                    <select 
                        id="report_level" 
                        v-model="form.report_level"
                        class="mt-1 block w-full bg-base-100 border-base-300 focus:border-primary focus:ring-primary rounded-md shadow-sm text-sm text-base-content"
                    >
                        <option value="basic">Basic Reports</option>
                        <option value="enhanced">Enhanced Reports</option>
                        <option value="advanced">Advanced Analytics</option>
                    </select>
                </div>
            </section>
        </div>

        <div class="flex items-center justify-end space-x-3 pt-6 border-t border-base-300">
            <SecondaryButton @click="$emit('cancel')" :disabled="processing" class="btn btn-ghost btn-sm normal-case">
                Cancel
            </SecondaryButton>
            <PrimaryButton class="text-white border-transparent btn-sm normal-case px-6 shadow-sm transition-all hover:brightness-110" :style="{ backgroundColor: primaryColor }" :class="{ 'opacity-25': processing }" :disabled="processing">
                {{ plan ? 'Update Plan' : 'Save Plan' }}
            </PrimaryButton>
        </div>
    </form>
</template>
