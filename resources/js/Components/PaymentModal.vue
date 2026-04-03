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
const monthsToSubscribe = ref(1);

// Success data
const paymentResult = ref(null);
const countdown = ref({ days: 0, hours: 0, minutes: 0, seconds: 0 });
let countdownTimer = null;

// Stripe
let stripeCheckout = null;
const currentSessionId = ref(null); // stored as ref so it survives async closures
let completionInProgress = false; // guard against duplicate handlePaymentComplete calls

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
    
    if (billingCycle.value === 'yearly') {
        return selectedPlan.value.price_yearly;
    }
    
    // Custom months calculation
    return selectedPlan.value.price_monthly * monthsToSubscribe.value;
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
            monthsToSubscribe.value = 1;
            billingCycle.value = 'monthly';
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
                months:        billingCycle.value === 'monthly' ? monthsToSubscribe.value : 12,
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

    if (completionInProgress) {
        console.warn('[PaymentModal] handlePaymentComplete already in progress, skipping duplicate call.');
        return;
    }
    completionInProgress = true;

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
            body: JSON.stringify({ 
                session_id: sessionId,
                months: billingCycle.value === 'monthly' ? monthsToSubscribe.value : 12
            }),
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
            startCountdown(new Date(data.registration.expires_at), data.server_time);
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
        completionInProgress = false;
    }
};

// ─── Countdown timer ─────────────────────────────────────────────────────────
const startCountdown = (expiresAt, serverTimeStr) => {
    clearInterval(countdownTimer);

    // Calculate the offset between server time and the browser's local time
    // If serverTimeStr is missing, default to 0 offset
    const serverTimeMs = serverTimeStr 
        ? (typeof serverTimeStr === 'number' ? serverTimeStr : new Date(serverTimeStr).getTime())
        : new Date().getTime();
    const expiresAtMs = typeof expiresAt === 'number' ? expiresAt : new Date(expiresAt).getTime();
    const timeOffset = serverTimeMs - new Date().getTime();

    const update = () => {
        // Evaluate the "current" time adjusted by our measured offset
        const nowMs = new Date().getTime() + timeOffset;
        const diff = expiresAtMs - nowMs;

        if (diff <= 0) {
            countdown.value = { days: 0, hours: 0, minutes: 0, seconds: 0 };
            clearInterval(countdownTimer);
            return;
        }

        const days    = Math.floor(diff / (1000 * 60 * 60 * 24));
        const hours   = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((diff % (1000 * 60)) / 1000);
        
        countdown.value = { days, hours, minutes, seconds };
    };

    update();
    countdownTimer = setInterval(update, 1000);
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
    
    // If we were on the success screen, redirect to homepage to clean URL
    if (screen.value === 'success') {
        window.location.href = '/';
        return;
    }

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
    <!-- maxWidth 3xl to facilitate side-by-side layout -->
    <Modal :show="show" @close="closeModal" maxWidth="3xl">
        <div class="relative min-h-[400px]">

            <!-- ── REVIEW SCREEN ─────────────────────────────────── -->
            <div v-if="screen === 'review'" class="p-5">
                <!-- Header -->
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Finalize Subscription</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Please review your registration and select your preferred billing period.</p>
                    </div>
                    <button @click="closeModal" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-12 gap-5">
                    <!-- Left: Registration Summary (4 cols) -->
                    <div class="md:col-span-5 space-y-4">
                        <div v-if="registrationData" class="bg-gray-50 rounded-xl p-4 border border-gray-100 h-full">
                            <h3 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-4">Registration Summary</h3>
                            <div class="space-y-3">
                                <div>
                                    <span class="block text-[10px] text-gray-400 uppercase">Clinic Name</span>
                                    <span class="font-semibold text-gray-900">{{ registrationData.clinic_name }}</span>
                                </div>
                                <div>
                                    <span class="block text-[10px] text-gray-400 uppercase">Clinic Web Address</span>
                                    <span class="font-semibold text-[#2B7CB3]">{{ registrationData.subdomain }}.{{ clinicDomain }}</span>
                                </div>
                                <div>
                                    <span class="block text-[10px] text-gray-400 uppercase">Administrator</span>
                                    <span class="font-semibold text-gray-900">{{ registrationData.admin_name }}</span>
                                </div>
                                <div>
                                    <span class="block text-[10px] text-gray-400 uppercase">Billing Email</span>
                                    <span class="font-semibold text-gray-900 break-all">{{ registrationData.email }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Plan & Billing (7 cols) -->
                    <div class="md:col-span-7 space-y-4">
                        <!-- Plan Selector -->
                        <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
                            <div class="flex justify-between items-center mb-4">
                                <label class="text-sm font-bold text-gray-900">Choose Plan</label>
                                <select
                                    v-model="selectedPlanId"
                                    class="text-xs border-gray-200 rounded-lg focus:ring-[#2B7CB3] focus:border-[#2B7CB3] py-1.5"
                                >
                                    <option v-for="plan in plans" :key="plan.id" :value="plan.id">{{ plan.name }}</option>
                                </select>
                            </div>

                            <!-- Billing Cycle Options -->
                            <div class="grid grid-cols-2 gap-3 mb-4">
                                <button 
                                    @click="billingCycle = 'monthly'"
                                    :class="[
                                        'px-4 py-2 text-xs font-bold rounded-lg border transition-all',
                                        billingCycle === 'monthly' ? 'bg-blue-50 border-[#2B7CB3] text-[#2B7CB3]' : 'bg-white border-gray-200 text-gray-500 hover:border-gray-300'
                                    ]"
                                >
                                    Flexible Monthly
                                </button>
                                <button 
                                    @click="billingCycle = 'yearly'"
                                    :class="[
                                        'px-4 py-2 text-xs font-bold rounded-lg border transition-all',
                                        billingCycle === 'yearly' ? 'bg-blue-50 border-[#2B7CB3] text-[#2B7CB3]' : 'bg-white border-gray-200 text-gray-500 hover:border-gray-300'
                                    ]"
                                >
                                    Annual (Best Value)
                                </button>
                            </div>

                            <!-- Multi-Month Selector (only for monthly) -->
                            <div v-if="billingCycle === 'monthly'" class="mb-4">
                                <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Duration (Months)</label>
                                <div class="grid grid-cols-6 gap-1.5">
                                    <button 
                                        v-for="m in 12" 
                                        :key="m"
                                        @click="monthsToSubscribe = m"
                                        :class="[
                                            'h-8 text-xs font-bold rounded flex items-center justify-center border transition-all',
                                            monthsToSubscribe === m ? 'bg-[#2B7CB3] border-[#2B7CB3] text-white shadow-sm' : 'bg-white border-gray-200 text-gray-600 hover:border-gray-300'
                                        ]"
                                    >
                                        {{ m }}
                                    </button>
                                </div>
                            </div>

                            <!-- Total Preview -->
                            <div class="bg-gray-900 rounded-xl p-4 text-white">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <p class="text-[10px] text-gray-400 uppercase font-bold">Total Due Today</p>
                                        <p class="text-2xl font-black">{{ formatCurrency(currentPrice) }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-[10px] text-gray-400 uppercase font-bold">Subscription Period</p>
                                        <p class="text-xs font-medium">
                                            {{ billingCycle === 'yearly' ? '12 Months (Yearly Saver)' : `${monthsToSubscribe} Month${monthsToSubscribe > 1 ? 's' : ''}` }}
                                        </p>
                                    </div>
                                </div>
                                <div v-if="billingCycle === 'yearly' && savings > 0" class="mt-2 pt-2 border-t border-gray-800 text-[10px] text-green-400 flex items-center">
                                    <span class="mr-1">🎉</span> You are saving {{ formatCurrency(savings) }} with the Annual Plan!
                                </div>
                            </div>
                        </div>

                        <button
                            @click="proceedToCheckout"
                            :disabled="isLoading || !selectedPlan"
                            class="w-full py-4 rounded-xl font-black text-white bg-[#FF6B53] hover:bg-[#E05A44] transition-all shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                        >
                            <svg v-if="isLoading" class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            <span>{{ isLoading ? 'Initializing Secure Payment...' : 'Secure Checkout & Payment' }}</span>
                        </button>
                    </div>
                </div>

                <div class="mt-4 flex items-center justify-center gap-4 text-[10px] text-gray-400">
                    <span class="flex items-center gap-1"><svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path></svg> SSL Secured</span>
                    <span class="flex items-center gap-1"><svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"></path><path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"></path></svg> PCI Compliant</span>
                    <span class="flex items-center gap-1">💳 Powered by Stripe</span>
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
                        <div class="text-amber-400 self-center text-2xl font-bold">:</div>
                        <div class="text-center">
                            <span class="block text-3xl font-black text-amber-600">{{ countdown.seconds }}</span>
                            <span class="text-xs text-amber-500">Seconds</span>
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
                    Done
                </button>
            </div>

        </div>
    </Modal>
</template>
