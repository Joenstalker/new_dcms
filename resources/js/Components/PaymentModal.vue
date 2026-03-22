<script setup>
import { ref, computed, watch, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { loadStripe } from '@stripe/stripe-js';
import Modal from '@/Components/Modal.vue';
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
    },
    sessionId: {
        type: String,
        default: null
    }
});

const emit = defineEmits(['close', 'paymentSuccess']);

// ─── Screen state: 'review' | 'checkout' | 'success' ────────────────────────
const screen = ref('review');
const isLoading = ref(false);
const selectedPlanId = ref(null);
const billingCycle = ref('monthly');

// Success data
const paymentResult = ref(null);
const countdown = ref(null);
let countdownTimer = null;

// Stripe
let stripeCheckout = null;
const currentSessionId = ref(null); // stored as ref so it survives async closures

// Dynamic domain
const page = usePage();
const clinicDomain = computed(() => {
    const appUrl = page.props.config?.app_url || 'http://localhost:8080';
    const url = new URL(appUrl);
    const port = url.port ? `:${url.port}` : '';
    return `${url.hostname}${port}`;
});

const selectedPlan = computed(() => {
    if (selectedPlanId.value) {
        return props.plans.find(p => p.id === selectedPlanId.value);
    }
    return props.plans[0] || props.registrationData?.plan;
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

// Set plan when modal opens
watch(() => props.show, (newVal) => {
    if (newVal) {
        if (props.sessionId) {
            console.log('[PaymentModal] Opened with sessionId prop:', props.sessionId);
            currentSessionId.value = props.sessionId;
            screen.value = 'checkout'; // show checkout screen (loading)
            handlePaymentComplete();
        } else if (props.plans.length > 0) {
            selectedPlanId.value = props.registrationData?.plan?.id ?? props.plans[0].id;
            screen.value = 'review';
        }
    }
    if (!newVal) {
        cleanupStripe();
    }
});

// ─── Proceed from review → launch Stripe embedded checkout ──────────────────
const proceedToCheckout = async () => {
    if (!selectedPlan.value) return;
    isLoading.value = true;

    try {
        const response = await fetch('/registration/checkout', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                clinic_name:   props.registrationData.clinic_name,
                first_name:    props.registrationData.first_name,
                last_name:     props.registrationData.last_name,
                admin_name:    props.registrationData.admin_name,
                email:         props.registrationData.email,
                phone:         props.registrationData.phone,
                street:        props.registrationData.street,
                region:        props.registrationData.region,
                barangay:      props.registrationData.barangay,
                city:          props.registrationData.city,
                province:      props.registrationData.province,
                password:      props.registrationData.password,
                subdomain:     props.registrationData.subdomain,
                plan_id:       selectedPlan.value.id,
                billing_cycle: billingCycle.value,
            }),
        });

        const data = await response.json();

        if (!data.success || !data.clientSecret) {
            throw new Error(data.message || 'Failed to create payment session.');
        }

        // Store session ID in reactive ref so it's accessible even if the closure context changes
        currentSessionId.value = data.sessionId;
        console.log('[PaymentModal] clientSecret received, sessionId:', data.sessionId);

        // Switch to checkout screen first, then mount
        screen.value = 'checkout';

        // Wait for Vue to render the #stripe-embedded-checkout div
        await new Promise(resolve => setTimeout(resolve, 200));

        const stripe = await loadStripe(import.meta.env.VITE_STRIPE_KEY);
        stripeCheckout = await stripe.initEmbeddedCheckout({
            clientSecret: data.clientSecret,
            onComplete: () => {
                console.log('[PaymentModal] Stripe onComplete fired! sessionId:', currentSessionId.value);
                handlePaymentComplete();
            },
        });
        stripeCheckout.mount('#stripe-embedded-checkout');
        console.log('[PaymentModal] Stripe embed mounted');

    } catch (error) {
        console.error('Checkout error:', error);
        screen.value = 'review';
        Swal.fire({
            icon: 'error',
            title: 'Payment Error',
            text: error.message || 'An error occurred. Please try again.',
            confirmButtonColor: '#2B7CB3',
        });
    } finally {
        isLoading.value = false;
    }
};

// ─── Stripe onComplete callback ─────────────────────────────────────────────
// NOTE: Do NOT call cleanupStripe() here — we need Stripe's confirmation UI to
// remain visible until we've confirmed the API returned success and switched
// screen to 'success'. This prevents a blank flash.
const handlePaymentComplete = async () => {
    const sessionId = currentSessionId.value;
    console.log('[PaymentModal] handlePaymentComplete called, sessionId:', sessionId);

    if (!sessionId) {
        console.error('[PaymentModal] No sessionId! Cannot complete registration.');
        Swal.fire({ icon: 'error', title: 'Error', text: 'Session ID missing. Please refresh and try again.', confirmButtonColor: '#2B7CB3' });
        return;
    }

    isLoading.value = true;

    try {
        console.log('[PaymentModal] POSTing to /registration/complete...');
        const response = await fetch('/registration/complete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ session_id: sessionId }),
        });

        const data = await response.json();
        console.log('[PaymentModal] /registration/complete response:', data);

        if (!data.success) {
            throw new Error(data.message || 'Failed to finalize registration.');
        }

        paymentResult.value = data.registration;

        // Destroy Stripe embed BEFORE switching screen to avoid layout shift
        cleanupStripe();
        screen.value = 'success';
        emit('paymentSuccess', data.registration);
        console.log('[PaymentModal] Switched to success screen ✅');

        // Start countdown if expires_at provided
        if (data.registration?.expires_at) {
            startCountdown(new Date(data.registration.expires_at));
        }

    } catch (error) {
        console.error('Complete error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message || 'Payment was received but we could not finalize your registration. Please contact support.',
            confirmButtonColor: '#2B7CB3',
        });
    } finally {
        isLoading.value = false;
    }
};

// ─── Countdown timer ─────────────────────────────────────────────────────────
const startCountdown = (expiresAt) => {
    clearInterval(countdownTimer);

    const update = () => {
        const now = new Date();
        const diff = expiresAt - now;

        if (diff <= 0) {
            countdown.value = { days: 0, hours: 0, minutes: 0 };
            clearInterval(countdownTimer);
            return;
        }

        const days    = Math.floor(diff / (1000 * 60 * 60 * 24));
        const hours   = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        countdown.value = { days, hours, minutes };
    };

    update();
    countdownTimer = setInterval(update, 60000);
};

// ─── Cleanup ─────────────────────────────────────────────────────────────────
const cleanupStripe = () => {
    if (stripeCheckout) {
        try { stripeCheckout.destroy(); } catch {}
        stripeCheckout = null;
    }
};

onUnmounted(() => {
    cleanupStripe();
    clearInterval(countdownTimer);
});

const closeModal = () => {
    cleanupStripe();
    clearInterval(countdownTimer);
    screen.value = 'review';
    selectedPlanId.value = null;
    billingCycle.value = 'monthly';
    paymentResult.value = null;
    countdown.value = null;
    emit('close');
};

const formatCurrency = (val) =>
    '₱' + Number(val ?? 0).toLocaleString('en-PH', { minimumFractionDigits: 2 });

const tenantUrl = computed(() => {
    if (!paymentResult.value?.subdomain) return '#';
    return `/tenant/${paymentResult.value.subdomain}`;
});
</script>

<template>
    <!-- maxWidth 2xl to give Stripe embed room -->
    <Modal :show="show" @close="closeModal" maxWidth="2xl">
        <div class="relative min-h-[400px]">

            <!-- ── REVIEW SCREEN ─────────────────────────────────── -->
            <div v-if="screen === 'review'" class="p-6">
                <!-- Header -->
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">Complete Your Subscription</h2>
                        <p class="text-sm text-gray-500 mt-1">Review and confirm your plan before payment</p>
                    </div>
                    <button @click="closeModal" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Registration summary -->
                <div v-if="registrationData" class="bg-gray-50 rounded-lg p-4 mb-6">
                    <h3 class="text-sm font-semibold text-gray-500 mb-2 uppercase tracking-wide">Registration Details</h3>
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <div>
                            <span class="text-gray-500">Clinic:</span>
                            <span class="ml-2 font-medium">{{ registrationData.clinic_name }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">URL:</span>
                            <span class="ml-2 font-medium text-[#2B7CB3]">{{ registrationData.subdomain }}.{{ clinicDomain }}</span>
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
                <div class="mb-5">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Select Plan</label>
                    <select
                        v-model="selectedPlanId"
                        class="w-full border-gray-300 rounded-md shadow-sm focus:ring-[#2B7CB3] focus:border-[#2B7CB3]"
                    >
                        <option v-for="plan in plans" :key="plan.id" :value="plan.id">
                            {{ plan.name }} — ₱{{ Number(plan.price_monthly).toLocaleString() }}/mo
                        </option>
                    </select>
                </div>

                <!-- Plan Details -->
                <div v-if="selectedPlan" class="border border-gray-200 rounded-lg p-4 mb-5">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">{{ selectedPlan.name }} Plan</h3>
                            <p class="text-sm text-gray-500">
                                {{ selectedPlan.max_users }} users ·
                                {{ selectedPlan.max_patients ? selectedPlan.max_patients.toLocaleString() : 'Unlimited' }} patients
                            </p>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-bold text-[#2B7CB3]">{{ formatCurrency(currentPrice) }}</div>
                            <div class="text-xs text-gray-500">/{{ billingCycle === 'yearly' ? 'year' : 'month' }}</div>
                        </div>
                    </div>

                    <!-- Billing Cycle Toggle -->
                    <div class="flex items-center justify-center gap-6 mt-4 p-3 bg-gray-50 rounded-lg">
                        <label class="flex items-center cursor-pointer gap-2">
                            <input type="radio" v-model="billingCycle" value="monthly" class="text-[#2B7CB3] focus:ring-[#2B7CB3]" />
                            <span class="text-sm font-medium" :class="billingCycle === 'monthly' ? 'text-gray-900' : 'text-gray-500'">Monthly</span>
                        </label>
                        <label class="flex items-center cursor-pointer gap-2">
                            <input type="radio" v-model="billingCycle" value="yearly" class="text-[#2B7CB3] focus:ring-[#2B7CB3]" />
                            <span class="text-sm font-medium" :class="billingCycle === 'yearly' ? 'text-gray-900' : 'text-gray-500'">Yearly</span>
                        </label>
                        <span v-if="billingCycle === 'yearly' && savings > 0" class="text-xs text-emerald-600 font-semibold">
                            Save {{ formatCurrency(savings) }}!
                        </span>
                    </div>
                </div>

                <!-- Info notice -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-5">
                    <p class="text-xs text-blue-700">
                        💡 A secure Stripe payment form will open right here — you won't leave this page.
                    </p>
                </div>

                <!-- Total & CTA -->
                <div class="border-t border-gray-200 pt-4">
                    <div class="flex justify-between items-center mb-4">
                        <span class="text-lg font-medium text-gray-900">Total Due Today</span>
                        <span class="text-2xl font-bold text-[#2B7CB3]">{{ formatCurrency(currentPrice) }}</span>
                    </div>

                    <button
                        @click="proceedToCheckout"
                        :disabled="isLoading || !selectedPlan"
                        class="w-full py-3 rounded-md font-bold text-white bg-[#FF6B53] hover:bg-[#E05A44] transition-all shadow-md disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                    >
                        <svg v-if="isLoading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        <span>{{ isLoading ? 'Loading Secure Payment...' : 'Pay with Card & Subscribe' }}</span>
                    </button>

                    <p class="text-xs text-center text-gray-500 mt-3">
                        🔒 Payments processed securely via Stripe. No card details stored on our servers.
                    </p>
                </div>
            </div>

            <!-- ── STRIPE CHECKOUT SCREEN ────────────────────────── -->
            <div v-if="screen === 'checkout'" class="p-4">
                <!-- Back button + title -->
                <div class="flex items-center gap-3 mb-4">
                    <button @click="screen = 'review'; cleanupStripe()" class="text-gray-400 hover:text-gray-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                        </svg>
                    </button>
                    <h2 class="text-lg font-bold text-gray-900">Secure Payment</h2>
                    <span class="ml-auto text-xs text-gray-400 flex items-center gap-1">
                        🔒 Powered by Stripe
                    </span>
                </div>

                <!-- Stripe mounts here -->
                <div id="stripe-embedded-checkout" class="min-h-[400px]"></div>

                <!-- Loading spinner while Stripe iframe initialises -->
                <div v-if="isLoading" class="absolute inset-0 flex items-center justify-center bg-white/80 rounded-lg">
                    <div class="flex flex-col items-center gap-3">
                        <svg class="animate-spin h-8 w-8 text-[#2B7CB3]" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        <p class="text-sm text-gray-500">Loading secure payment form…</p>
                    </div>
                </div>
            </div>

            <!-- ── SUCCESS SCREEN ─────────────────────────────────── -->
            <div v-if="screen === 'success'" class="p-6 text-center">
                <!-- Checkmark -->
                <div class="w-20 h-20 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-5">
                    <svg class="w-10 h-10 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                </div>

                <h2 class="text-2xl font-black text-gray-900 mb-1">Payment Received! 🎉</h2>
                <p class="text-gray-500 text-sm mb-6">
                    Dear <strong>{{ paymentResult?.first_name }}</strong>, thank you for registering
                    <strong>{{ paymentResult?.clinic_name }}</strong> with DCMS.
                </p>

                <!-- Countdown (if expires_at present) -->
                <div v-if="countdown" class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-5 text-center">
                    <p class="text-xs font-bold text-amber-700 uppercase tracking-widest mb-2">Registration Pending Approval</p>
                    <div class="flex justify-center gap-4">
                        <div class="text-center">
                            <span class="block text-3xl font-black text-amber-600">{{ countdown.days }}</span>
                            <span class="text-xs text-amber-500">Days</span>
                        </div>
                        <div class="text-amber-400 self-center text-2xl font-bold">:</div>
                        <div class="text-center">
                            <span class="block text-3xl font-black text-amber-600">{{ countdown.hours }}</span>
                            <span class="text-xs text-amber-500">Hours</span>
                        </div>
                        <div class="text-amber-400 self-center text-2xl font-bold">:</div>
                        <div class="text-center">
                            <span class="block text-3xl font-black text-amber-600">{{ countdown.minutes }}</span>
                            <span class="text-xs text-amber-500">Minutes</span>
                        </div>
                    </div>
                    <p class="text-xs text-amber-600 mt-2">Time remaining for admin review</p>
                </div>

                <!-- Registration details card -->
                <div class="bg-gray-50 rounded-xl p-4 mb-6 text-left space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Clinic Name</span>
                        <span class="font-semibold">{{ paymentResult?.clinic_name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Clinic URL</span>
                        <a :href="tenantUrl" class="font-semibold text-[#2B7CB3] hover:underline">
                            {{ paymentResult?.subdomain }}.{{ clinicDomain }}
                        </a>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Amount Paid</span>
                        <span class="font-semibold text-emerald-600">{{ formatCurrency(paymentResult?.amount_paid) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Status</span>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-amber-100 text-amber-700 rounded-full text-xs font-bold">
                            ⏳ Pending Admin Approval
                        </span>
                    </div>
                </div>

                <!-- What's next -->
                <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 mb-6 text-left">
                    <p class="text-xs font-bold text-blue-700 uppercase tracking-widest mb-2">What happens next?</p>
                    <ul class="text-xs text-blue-700 space-y-1 list-disc list-inside">
                        <li>You'll receive a confirmation email shortly</li>
                        <li>Our team will review your registration within the time limit</li>
                        <li>Once approved, you'll get your clinic login credentials via email</li>
                    </ul>
                </div>

                <button
                    @click="closeModal"
                    class="w-full py-3 rounded-md font-bold text-white bg-[#2B7CB3] hover:bg-[#24699A] transition-all shadow-md"
                >
                    Close & Return to Homepage
                </button>
            </div>

        </div>
    </Modal>
</template>
