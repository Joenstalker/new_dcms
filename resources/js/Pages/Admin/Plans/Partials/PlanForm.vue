<script setup>
import { useForm } from '@inertiajs/vue3';
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

const form = useForm({
    name: props.plan?.name ?? '',
    stripe_id: props.plan?.stripe_id ?? '',
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
    <form @submit.prevent="submit" class="p-6 space-y-6">
        <header class="border-b border-gray-100 pb-4">
            <h2 class="text-xl font-bold text-gray-900">
                {{ plan ? 'Edit' : 'Create' }} <span class="text-teal-600">Subscription Plan</span>
            </h2>
            <p class="mt-1 text-xs text-gray-500">
                Define pricing and features for this subscription tier.
            </p>
        </header>

        <div class="max-h-[65vh] overflow-y-auto pr-2 custom-scrollbar space-y-8">
            <!-- Basic Info -->
            <section>
                <h3 class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.2em] mb-4 flex items-center">
                    <span class="w-5 h-5 rounded-full bg-gray-100 flex items-center justify-center mr-2 text-[10px]">1</span> 
                    Basic Details
                </h3>
                <div class="space-y-4">
                    <div>
                        <InputLabel for="name" value="Plan Name" class="text-xs font-semibold text-gray-700" />
                        <TextInput 
                            id="name" 
                            type="text" 
                            class="mt-1 block w-full text-sm" 
                            :class="{'bg-gray-50 border-gray-200': !!plan}"
                            v-model="form.name" 
                            required 
                            :disabled="!!plan"
                            placeholder="e.g. Starter, Pro, Ultimate" 
                        />
                        <p v-if="plan" class="text-[9px] text-gray-400 mt-1 italic">Plan name cannot be changed.</p>
                        <InputError class="mt-1" :message="form.errors.name" />
                    </div>

                    <div>
                        <InputLabel for="stripe_id" value="Stripe Price ID" class="text-xs font-semibold text-gray-700" />
                        <TextInput id="stripe_id" type="text" class="mt-1 block w-full text-sm" v-model="form.stripe_id" placeholder="price_..." />
                        <InputError class="mt-1" :message="form.errors.stripe_id" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="price_monthly" value="Monthly (₱)" class="text-xs font-semibold text-gray-700" />
                            <TextInput id="price_monthly" type="number" step="0.01" class="mt-1 block w-full text-sm" v-model="form.price_monthly" required />
                            <InputError class="mt-1" :message="form.errors.price_monthly" />
                        </div>
                        <div>
                            <InputLabel for="price_yearly" value="Yearly (₱)" class="text-xs font-semibold text-gray-700" />
                            <TextInput id="price_yearly" type="number" step="0.01" class="mt-1 block w-full text-sm" v-model="form.price_yearly" required />
                            <InputError class="mt-1" :message="form.errors.price_yearly" />
                        </div>
                    </div>
                </div>
            </section>

            <!-- Usage Limits -->
            <section>
                <h3 class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.2em] mb-4 flex items-center">
                    <span class="w-5 h-5 rounded-full bg-gray-100 flex items-center justify-center mr-2 text-[10px]">2</span> 
                    Usage Limits
                </h3>
                <div class="grid grid-cols-1 gap-4">
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-100">
                        <div>
                            <InputLabel for="max_users" value="Maximum Users" class="text-xs font-semibold text-gray-700" />
                            <p class="text-[10px] text-gray-500">Number of staff accounts</p>
                        </div>
                        <TextInput id="max_users" type="number" min="1" class="w-24 text-sm" v-model="form.max_users" required />
                    </div>

                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-100">
                        <div>
                            <InputLabel for="max_patients" value="Maximum Patients" class="text-xs font-semibold text-gray-700" />
                            <p class="text-[10px] text-gray-500">0 for unlimited</p>
                        </div>
                        <TextInput id="max_patients" type="number" min="0" class="w-24 text-sm" v-model="form.max_patients" required />
                    </div>

                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-100">
                        <div>
                            <InputLabel for="max_appointments" value="Max Appointments" class="text-xs font-semibold text-gray-700" />
                            <p class="text-[10px] text-gray-500">0 for unlimited</p>
                        </div>
                        <TextInput id="max_appointments" type="number" min="0" class="w-24 text-sm" v-model="form.max_appointments" required />
                    </div>
                </div>
            </section>

            <!-- Features -->
            <section>
                <h3 class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.2em] mb-4 flex items-center">
                    <span class="w-5 h-5 rounded-full bg-gray-100 flex items-center justify-center mr-2 text-[10px]">3</span> 
                    Features
                </h3>
                <div class="bg-white rounded-xl border border-gray-100 divide-y divide-gray-50">
                    <label class="flex items-center justify-between p-3 hover:bg-gray-50 transition-colors cursor-pointer">
                        <span class="text-xs font-medium text-gray-700">QR Code Booking</span>
                        <Checkbox name="has_qr_booking" v-model:checked="form.has_qr_booking" class="text-teal-600 focus:ring-teal-500" />
                    </label>

                    <label class="flex items-center justify-between p-3 hover:bg-gray-50 transition-colors cursor-pointer">
                        <span class="text-xs font-medium text-gray-700">SMS Notifications</span>
                        <Checkbox name="has_sms" v-model:checked="form.has_sms" class="text-teal-600 focus:ring-teal-500" />
                    </label>

                    <label class="flex items-center justify-between p-3 hover:bg-gray-50 transition-colors cursor-pointer">
                        <span class="text-xs font-medium text-gray-700">Custom Branding</span>
                        <Checkbox name="has_branding" v-model:checked="form.has_branding" class="text-teal-600 focus:ring-teal-500" />
                    </label>

                    <label class="flex items-center justify-between p-3 hover:bg-gray-50 transition-colors cursor-pointer">
                        <span class="text-xs font-medium text-gray-700">Advanced Analytics</span>
                        <Checkbox name="has_analytics" v-model:checked="form.has_analytics" class="text-teal-600 focus:ring-teal-500" />
                    </label>

                    <label class="flex items-center justify-between p-3 hover:bg-gray-50 transition-colors cursor-pointer">
                        <span class="text-xs font-medium text-gray-700">Priority Support</span>
                        <Checkbox name="has_priority_support" v-model:checked="form.has_priority_support" class="text-teal-600 focus:ring-teal-500" />
                    </label>

                    <label class="flex items-center justify-between p-3 hover:bg-gray-50 transition-colors cursor-pointer">
                        <span class="text-xs font-medium text-gray-700">Multi-branch Ready</span>
                        <Checkbox name="has_multi_branch" v-model:checked="form.has_multi_branch" class="text-teal-600 focus:ring-teal-500" />
                    </label>
                </div>

                <div class="mt-4">
                    <InputLabel for="report_level" value="Analytics Level" class="text-xs font-semibold text-gray-700" />
                    <select 
                        id="report_level" 
                        v-model="form.report_level"
                        class="mt-1 block w-full border-gray-300 focus:border-teal-500 focus:ring-teal-500 rounded-md shadow-sm text-sm"
                    >
                        <option value="basic">Basic Reports</option>
                        <option value="enhanced">Enhanced Reports</option>
                        <option value="advanced">Advanced Analytics</option>
                    </select>
                </div>
            </section>
        </div>

        <div class="flex items-center justify-end space-x-3 pt-6 border-t border-gray-100">
            <SecondaryButton @click="$emit('cancel')" :disabled="processing" class="text-xs py-2 shadow-none border-gray-200">
                Cancel
            </SecondaryButton>
            <PrimaryButton class="bg-teal-600 hover:bg-teal-700 text-xs py-2 px-6 shadow-sm shadow-teal-900/10" :class="{ 'opacity-25': processing }" :disabled="processing">
                {{ plan ? 'Update Plan' : 'Save Plan' }}
            </PrimaryButton>
        </div>
    </form>
</template>
