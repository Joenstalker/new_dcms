<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    show: Boolean,
    registrationData: {
        type: Object,
        default: null
    },
    plans: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['close', 'paymentSuccess']);

const isLoading = ref(false);
const selectedPlanId = ref(null);
const billingCycle = ref('monthly');
const selectedPaymentMethod = ref('card');

// Dynamic domain from backend config
const page = usePage();
const clinicDomain = computed(() => {
    const appUrl = page.props.config?.app_url || 'http://localhost:8080';
    const url = new URL(appUrl);
    const port = url.port ? `:${url.port}` : '';
    return `${url.hostname}${port}`;
});

// Popup window reference
let paymentPopup = null;
const checkPaymentInterval = ref(null);

// Get the selected plan from props or default to first
const selectedPlan = computed(() => {
    if (selectedPlanId.value) {
        return props.plans.find(p => p.id === selectedPlanId.value);
    }
    return props.plans[0] || props.registrationData?.plan;
});

// Set initial plan when modal opens
watch(() => props.show, (newVal) => {
    if (newVal && props.plans.length > 0) {
        if (props.registrationData?.plan?.id) {
            selectedPlanId.value = props.registrationData.plan.id;
        } else {
            selectedPlanId.value = props.plans[0].id;
        }
    }
});

// Watch for plan changes to update pricing
watch(selectedPlanId, () => {
    // Reset billing cycle when plan changes
    billingCycle.value = 'monthly';
});

const currentPrice = computed(() => {
    if (!selectedPlan.value) return 0;
    return billingCycle.value === 'yearly' 
        ? selectedPlan.value.price_yearly 
        : selectedPlan.value.price_monthly;
});

const savings = computed(() => {
    if (!selectedPlan.value || billingCycle.value !== 'yearly') return 0;
    const monthlyCost = selectedPlan.value.price_monthly * 12;
    return monthlyCost - selectedPlan.value.price_yearly;
});

const paymentMethods = [
    { 
        id: 'card', 
        name: 'Credit/Debit Card', 
        icon: '💳', 
        description: 'Pay securely with Visa, Mastercard, or Amex',
        stripe: true,
        coming: false
    },
    { 
        id: 'gcash', 
        name: 'GCash', 
        icon: '📱', 
        description: 'Pay using your GCash wallet',
        stripe: false,
        coming: true
    },
    { 
        id: 'paypal', 
        name: 'PayPal', 
        icon: '🅿️', 
        description: 'Pay with your PayPal account',
        stripe: false,
        coming: true
    },
    { 
        id: 'bank', 
        name: 'Bank Transfer', 
        icon: '🏦', 
        description: 'Direct bank transfer (BPI, Metrobank, etc.)',
        stripe: false,
        coming: true
    },
];

const proceedToPayment = async () => {
    if (!selectedPlan.value) return;
    
    isLoading.value = true;
    
    try {
        console.log('Creating checkout for plan:', selectedPlan.value);
        
        const response = await fetch('/registration/checkout', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                clinic_name: props.registrationData.clinic_name,
                first_name: props.registrationData.first_name,
                last_name: props.registrationData.last_name,
                admin_name: props.registrationData.admin_name,
                email: props.registrationData.email,
                phone: props.registrationData.phone,
                street: props.registrationData.street,
                region: props.registrationData.region,
                barangay: props.registrationData.barangay,
                city: props.registrationData.city,
                province: props.registrationData.province,
                password: props.registrationData.password,
                subdomain: props.registrationData.subdomain,
                plan_id: selectedPlan.value.id,
                billing_cycle: billingCycle.value,
            })
        });
        
        const data = await response.json();
        console.log('Checkout response:', data);
        
        if (data.success && data.url) {
            // Instead of a popup, we redirect the main window directly to Stripe Hosted Checkout
            window.location.href = data.url;
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Payment Error',
                text: data.message || 'Failed to create payment session. Please try again.',
                confirmButtonColor: '#0d9488',
            });
        }
    } catch (error) {
        console.error('Payment error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'An error occurred. Please try again.',
            confirmButtonColor: '#0d9488',
        });
    } finally {
        isLoading.value = false;
    }
};

const closeModal = () => {
    selectedPlanId.value = null;
    billingCycle.value = 'monthly';
    selectedPaymentMethod.value = 'card';
    emit('close');
};
</script>

<template>
    <Modal :show="show" @close="closeModal" maxWidth="xl">
        <div class="p-6">
            <!-- Header -->
            <div class="flex justify-between items-start mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Complete Your Subscription</h2>
                    <p class="text-sm text-gray-500 mt-1">Review and customize your plan</p>
                </div>
                <button @click="closeModal" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Registration Summary -->
            <div v-if="registrationData" class="bg-gray-50 rounded-lg p-4 mb-6">
                <h3 class="text-sm font-medium text-gray-500 mb-2">Registration Details</h3>
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <div>
                        <span class="text-gray-500">Clinic:</span>
                        <span class="ml-2 font-medium">{{ registrationData.clinic_name }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500">URL:</span>
                        <span class="ml-2 font-medium text-teal-600">{{ registrationData.subdomain }}.{{ clinicDomain }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500">Admin:</span>
                        <span class="ml-2 font-medium">{{ registrationData.admin_name }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500">Email:</span>
                        <span class="ml-2 font-medium">{{ registrationData.email }}</span>
                    </div>
                </div>
            </div>

            <!-- Plan Selection -->
            <div class="mb-6">
                <InputLabel for="plan" value="Select Plan" class="mb-2" />
                <select
                    id="plan"
                    v-model="selectedPlanId"
                    class="w-full border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500"
                >
                    <option v-for="plan in plans" :key="plan.id" :value="plan.id">
                        {{ plan.name }} - ₱{{ Number(plan.price_monthly).toLocaleString() }}/mo
                    </option>
                </select>
            </div>

            <!-- Plan Details -->
            <div v-if="selectedPlan" class="bg-white border border-gray-200 rounded-lg p-4 mb-6">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">{{ selectedPlan.name }} Plan</h3>
                        <p class="text-sm text-gray-500">
                            {{ selectedPlan.max_users }} users • 
                            {{ selectedPlan.max_patients ? selectedPlan.max_patients.toLocaleString() : 'Unlimited' }} patients
                        </p>
                    </div>
                    <div class="text-right">
                        <div class="text-2xl font-bold text-teal-600">
                            ₱{{ Number(currentPrice).toLocaleString() }}
                        </div>
                        <div class="text-xs text-gray-500">
                            /{{ billingCycle === 'yearly' ? 'year' : 'month' }}
                        </div>
                    </div>
                </div>
                
                <!-- Features -->
                <div class="flex flex-wrap gap-2 mb-4">
                    <span v-if="selectedPlan.features?.qr_booking" class="px-2 py-1 text-xs bg-teal-100 text-teal-700 rounded-full">
                        QR Booking
                    </span>
                    <span v-if="selectedPlan.features?.sms" class="px-2 py-1 text-xs bg-teal-100 text-teal-700 rounded-full">
                        SMS Notifications
                    </span>
                    <span v-if="selectedPlan.features?.branding" class="px-2 py-1 text-xs bg-teal-100 text-teal-700 rounded-full">
                        Custom Branding
                    </span>
                    <span v-if="selectedPlan.features?.priority_support" class="px-2 py-1 text-xs bg-teal-100 text-teal-700 rounded-full">
                        Priority Support
                    </span>
                    <span v-if="selectedPlan.features?.multi_branch" class="px-2 py-1 text-xs bg-teal-100 text-teal-700 rounded-full">
                        Multi-branch
                    </span>
                </div>

                <!-- Billing Cycle Toggle -->
                <div class="flex items-center justify-center space-x-4 p-3 bg-gray-50 rounded-lg">
                    <label class="flex items-center cursor-pointer">
                        <input 
                            type="radio" 
                            v-model="billingCycle" 
                            value="monthly"
                            class="text-teal-600 focus:ring-teal-500"
                        />
                        <span class="ml-2 text-sm font-medium" :class="billingCycle === 'monthly' ? 'text-gray-900' : 'text-gray-500'">
                            Monthly
                        </span>
                    </label>
                    <label class="flex items-center cursor-pointer">
                        <input 
                            type="radio" 
                            v-model="billingCycle" 
                            value="yearly"
                            class="text-teal-600 focus:ring-teal-500"
                        />
                        <span class="ml-2 text-sm font-medium" :class="billingCycle === 'yearly' ? 'text-gray-900' : 'text-gray-500'">
                            Yearly
                        </span>
                    </label>
                    <span v-if="billingCycle === 'yearly' && savings > 0" class="text-xs text-green-600 font-medium">
                        Save ₱{{ Number(savings).toLocaleString() }}!
                    </span>
                </div>
            </div>

            <!-- Payment Method Selection -->
            <div class="mb-6">
                <h3 class="text-sm font-medium text-gray-900 mb-3">Payment Method</h3>
                <div class="space-y-3">
                    <label
                        v-for="method in paymentMethods"
                        :key="method.id"
                        :class="[
                            'relative flex items-start p-4 border rounded-lg cursor-pointer transition-all',
                            selectedPaymentMethod === method.id 
                                ? 'border-teal-500 bg-teal-50' 
                                : method.coming 
                                    ? 'border-gray-200 opacity-50 cursor-not-allowed'
                                    : 'border-gray-200 hover:border-gray-300'
                        ]"
                    >
                        <input
                            type="radio"
                            :value="method.id"
                            v-model="selectedPaymentMethod"
                            :disabled="method.coming"
                            class="mt-1 text-teal-600 focus:ring-teal-500"
                        />
                        <div class="ml-3 flex-1">
                            <div class="flex items-center">
                                <span class="text-xl mr-2">{{ method.icon }}</span>
                                <span class="font-medium text-gray-900">{{ method.name }}</span>
                                <span v-if="method.coming" class="ml-2 px-2 py-0.5 text-xs bg-amber-100 text-amber-700 rounded">
                                    Coming Soon
                                </span>
                            </div>
                            <p class="text-sm text-gray-500 mt-1">{{ method.description }}</p>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Change Plan Notice -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-6">
                <p class="text-xs text-blue-700">
                    💡 You can change your plan at any time from your dashboard. Upgrades take effect immediately, 
                    and you'll only be charged the difference.
                </p>
            </div>

            <!-- Total & Submit -->
            <div class="border-t border-gray-200 pt-4">
                <div class="flex justify-between items-center mb-4">
                    <span class="text-lg font-medium text-gray-900">Total Due Today</span>
                    <span class="text-2xl font-bold text-teal-600">₱{{ Number(currentPrice).toLocaleString() }}</span>
                </div>
                
                <PrimaryButton
                    @click="proceedToPayment"
                    :disabled="isLoading || selectedPaymentMethod !== 'card'"
                    class="w-full justify-center py-3 bg-[#FF6B53] hover:bg-[#ff563b] focus:bg-[#e04f38]"
                    :class="{ 'opacity-50 cursor-not-allowed': isLoading || selectedPaymentMethod !== 'card' }"
                >
                    <span v-if="isLoading">Processing...</span>
                    <span v-else-if="selectedPaymentMethod !== 'card'">
                        Coming Soon - Select Card Payment
                    </span>
                    <span v-else>
                        Pay with Card & Subscribe
                    </span>
                </PrimaryButton>

                <p class="text-xs text-center text-gray-500 mt-3">
                    🔒 Payments are processed securely via Stripe. 
                    Your subscription will start immediately after payment.
                </p>
            </div>
        </div>
    </Modal>
</template>
