<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    tenant: Object,
    days_remaining: Number,
    current_plan_id: Number,
    payment_method: String,
    stripe_id: String,
    plans: Array,
    payment_history: {
        type: Array,
        default: () => []
    }
});

const page = usePage();

// Read subscription from middleware's Inertia::share (has plan_name, max_users, etc.)
const subscription = computed(() => page.props.subscription || null);

const daysRemaining = computed(() => props.days_remaining);
const isRenewalWindow = computed(() => daysRemaining.value !== null && daysRemaining.value <= 7);
const billingCycle = computed(() => subscription.value?.billing_cycle || 'monthly');

const upgradePlans = computed(() => {
    if (!props.plans || !props.current_plan_id) return props.plans || [];
    return props.plans.filter(p => p.id > props.current_plan_id);
});

const currentPlan = computed(() => {
    if (!props.plans || !props.current_plan_id) return null;
    return props.plans.find(p => p.id === props.current_plan_id);
});

const formatPrice = (price) => {
    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(price);
};

const statusColor = (status) => {
    const map = {
        active: 'bg-emerald-100 text-emerald-700 border-emerald-200',
        trialing: 'bg-amber-100 text-amber-700 border-amber-200',
        past_due: 'bg-red-100 text-red-700 border-red-200',
        unpaid: 'bg-red-100 text-red-700 border-red-200',
        canceled: 'bg-gray-100 text-gray-600 border-gray-200',
    };
    return map[status] || 'bg-gray-100 text-gray-600 border-gray-200';
};

const planGradients = {
    Basic: 'from-slate-500 to-slate-700',
    Pro: 'from-blue-500 to-indigo-600',
    Ultimate: 'from-amber-500 to-orange-600',
};

const planBadge = {
    Pro: '🚀 Most Popular',
    Ultimate: '👑 Best Value',
};

const paymentHistory = computed(() => props.payment_history || []);

const formatDate = (value) => {
    if (!value) return '-';

    return new Intl.DateTimeFormat('en-PH', {
        year: 'numeric',
        month: 'short',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
};

const paymentStatusClass = (status) => {
    const normalized = (status || '').toLowerCase();

    if (normalized === 'success') return 'bg-emerald-100 text-emerald-700 border-emerald-200';
    if (normalized === 'failed') return 'bg-red-100 text-red-700 border-red-200';
    if (normalized === 'refund') return 'bg-amber-100 text-amber-700 border-amber-200';

    return 'bg-gray-100 text-gray-700 border-gray-200';
};

const formatStatus = (status) => {
    const normalized = (status || '').toLowerCase();

    if (normalized === 'success') return 'Success';
    if (normalized === 'failed') return 'Failed';
    if (normalized === 'refund') return 'Refund';

    return status || 'Unknown';
};
</script>

<template>
    <Head title="Billing & Plan" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Billing & Plan
            </h2>
        </template>

        <div class="mt-6 space-y-8">
            <div v-if="$page.props.flash && $page.props.flash.success" class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-xl shadow-sm flex items-center gap-3">
                <span class="text-xl">✅</span>
                {{ $page.props.flash.success }}
            </div>

            <!-- ==================== RENEWAL ALERT ==================== -->
            <div v-if="isRenewalWindow && subscription" class="relative overflow-hidden rounded-2xl border-2 border-amber-300 bg-gradient-to-r from-amber-50 to-orange-50 p-6 shadow-lg">
                <div class="absolute -right-4 -top-4 w-24 h-24 bg-amber-200/30 rounded-full blur-xl"></div>
                <div class="relative z-10 flex items-center justify-between gap-4 flex-wrap">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center shadow-lg">
                            <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-amber-900">Subscription Expiring Soon</h3>
                            <p class="text-sm text-amber-700">
                                Your <strong>{{ subscription.plan_name }}</strong> plan expires in
                                <span class="font-black text-orange-600">{{ daysRemaining }} day{{ daysRemaining !== 1 ? 's' : '' }}</span>.
                                Renew now to keep your features active.
                            </p>
                        </div>
                    </div>
                    <a
                        :href="route('billing.portal')"
                        target="_blank"
                        class="inline-flex items-center gap-2 px-6 py-3 rounded-xl font-bold text-sm text-white bg-gradient-to-r from-amber-500 to-orange-500 shadow-lg hover:shadow-xl hover:scale-105 transition-all text-center cursor-pointer"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Renew Plan
                    </a>
                </div>
            </div>

            <!-- ==================== CURRENT PLAN CARD ==================== -->
            <div v-if="subscription" class="rounded-2xl overflow-hidden shadow-xl">
                <div :class="['bg-gradient-to-r text-white p-6', planGradients[subscription.plan_name] || 'from-gray-600 to-gray-800']">
                    <div class="flex items-center justify-between flex-wrap gap-4">
                        <div>
                            <p class="text-white/70 text-xs font-semibold uppercase tracking-widest">Current Plan</p>
                            <h3 class="text-3xl font-black mt-1">{{ subscription.plan_name }}</h3>
                            <div class="flex items-center gap-3 mt-2">
                                <span :class="['inline-flex items-center px-3 py-1 rounded-full text-xs font-bold border', statusColor(subscription.stripe_status)]">
                                    {{ subscription.stripe_status?.toUpperCase() }}
                                </span>
                                <span v-if="payment_method === 'admin_override'" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold border bg-purple-100 text-purple-700 border-purple-200">
                                    🛡️ ADMIN MANAGED
                                </span>
                                <span class="text-white/60 text-sm">
                                    {{ billingCycle === 'monthly' ? 'Monthly' : 'Yearly' }} billing
                                </span>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-white/60 text-xs">Current Price</p>
                            <p class="text-3xl font-black">
                                {{ payment_method === 'admin_override' ? 'FREE' : formatPrice(currentPlan ? (billingCycle === 'monthly' ? currentPlan.price_monthly : currentPlan.price_yearly) : 0) }}
                            </p>
                            <p class="text-white/50 text-xs" v-if="payment_method !== 'admin_override'">/{{ billingCycle === 'monthly' ? 'month' : 'year' }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white p-5 flex items-center justify-between border-t flex-wrap gap-4">
                    <div class="flex flex-wrap items-center gap-3 text-xs text-gray-500">
                        <span v-if="subscription.max_patients">
                            👥 <strong>{{ subscription.max_patients }}</strong> patients
                        </span>
                        <span v-else>👥 Unlimited patients</span>
                        <span v-if="subscription.max_users">👤 <strong>{{ subscription.max_users }}</strong> staff</span>
                        <span v-if="subscription.has_sms" class="text-emerald-600 font-semibold">📱 SMS ✓</span>
                        <span v-if="subscription.has_analytics" class="text-emerald-600 font-semibold">📊 Analytics ✓</span>
                        <span v-if="subscription.has_branding" class="text-emerald-600 font-semibold">🎨 Branding ✓</span>
                        <span v-if="subscription.has_multi_branch" class="text-emerald-600 font-semibold">🏢 Multi-branch ✓</span>
                    </div>
                    <a
                        v-if="stripe_id || payment_method !== 'admin_override'"
                        :href="route('billing.portal')"
                        target="_blank"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold text-gray-700 bg-gray-100 hover:bg-gray-200 transition-all text-center cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" />
                        </svg>
                        Manage Billing
                    </a>
                    <div v-else class="text-xs text-gray-400 italic">
                        Billing managed by administrator
                    </div>
                </div>
            </div>

            <!-- ==================== DAYS REMAINING ==================== -->
            <div v-if="subscription && daysRemaining !== null" class="flex items-center gap-4 p-4 bg-white rounded-2xl border shadow-sm">
                <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Time Remaining</p>
                    <p class="text-lg font-black" :class="daysRemaining <= 7 ? 'text-orange-600' : 'text-gray-900'">
                        {{ daysRemaining }} day{{ daysRemaining !== 1 ? 's' : '' }}
                    </p>
                </div>
                <div class="flex-1">
                    <div class="w-full bg-gray-100 rounded-full h-3 overflow-hidden">
                        <div
                            class="h-full rounded-full transition-all duration-500"
                            :class="daysRemaining <= 7 ? 'bg-gradient-to-r from-orange-400 to-red-500' : 'bg-gradient-to-r from-blue-400 to-indigo-500'"
                            :style="{ width: Math.min(100, Math.max(5, (daysRemaining / (billingCycle === 'monthly' ? 30 : 365)) * 100)) + '%' }"
                        ></div>
                    </div>
                </div>
            </div>

            <!-- ==================== PAYMENT HISTORY ==================== -->
            <div class="bg-white rounded-2xl border shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b bg-gray-50">
                    <h3 class="text-lg font-bold text-gray-900">Payment History</h3>
                    <p class="text-sm text-gray-500 mt-1">All your transactions with DCMS including registration, upgrades, downgrades, and renewals.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500">
                            <tr>
                                <th class="px-4 py-3 text-left">Date</th>
                                <th class="px-4 py-3 text-left">ID</th>
                                <th class="px-4 py-3 text-left">Plan</th>
                                <th class="px-4 py-3 text-left">Payment Method</th>
                                <th class="px-4 py-3 text-right">Amount</th>
                                <th class="px-4 py-3 text-left">Status</th>
                                <th class="px-4 py-3 text-left">Receipt</th>
                                <th class="px-4 py-3 text-left">Invoice</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-if="paymentHistory.length === 0">
                                <td colspan="8" class="px-4 py-6 text-center text-gray-500">No payment history yet.</td>
                            </tr>

                            <tr v-for="entry in paymentHistory" :key="entry.id" class="hover:bg-gray-50/70">
                                <td class="px-4 py-3 text-gray-700 whitespace-nowrap">{{ formatDate(entry.date) }}</td>
                                <td class="px-4 py-3">
                                    <span class="font-mono text-xs font-semibold text-gray-800">{{ entry.transaction_code }}</span>
                                </td>
                                <td class="px-4 py-3 text-gray-700 whitespace-nowrap">{{ entry.plan_name || subscription?.plan_name || '-' }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ entry.payment_method || 'Card payment' }}</td>
                                <td class="px-4 py-3 text-right font-bold text-gray-900">
                                    {{ formatPrice(entry.amount || 0) }}
                                </td>
                                <td class="px-4 py-3">
                                    <span :class="['inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border', paymentStatusClass(entry.status)]">
                                        {{ formatStatus(entry.status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <a
                                        :href="entry.receipt_download_url"
                                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-gray-200 text-gray-700 hover:bg-emerald-50 hover:text-emerald-600 transition-colors"
                                        title="Download Receipt"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5v-9m0 9l-3-3m3 3l3-3M3 16.125V18a2.25 2.25 0 002.25 2.25h13.5A2.25 2.25 0 0021 18v-1.875" />
                                        </svg>
                                    </a>
                                </td>
                                <td class="px-4 py-3">
                                    <a
                                        v-if="entry.status === 'success' && entry.invoice_download_url"
                                        :href="entry.invoice_download_url"
                                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-gray-200 text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition-colors"
                                        title="Download Invoice"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125V5.625A3.375 3.375 0 0010.125 2.25H8.25m.75 12l3 3m0 0l3-3m-3 3V9M5.625 20.25h12.75A1.875 1.875 0 0020.25 18.375V8.25a1.875 1.875 0 00-.55-1.325L13.325.55A1.875 1.875 0 0012 0H5.625A1.875 1.875 0 003.75 1.875v16.5a1.875 1.875 0 001.875 1.875z" />
                                        </svg>
                                    </a>
                                    <span v-else class="text-xs text-gray-400">-</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ==================== UPGRADE PLANS ==================== -->
            <div v-if="upgradePlans.length > 0">
                <div class="mb-4">
                    <h3 class="text-xl font-bold text-gray-900">Upgrade Your Plan</h3>
                    <p class="text-sm text-gray-500 mt-1">Unlock more features and grow your clinic with a higher plan.</p>
                </div>

                <div class="grid gap-6" :class="upgradePlans.length === 1 ? 'grid-cols-1 max-w-md' : 'grid-cols-1 md:grid-cols-2'">
                    <div
                        v-for="plan in upgradePlans"
                        :key="plan.id"
                        class="relative rounded-2xl border-2 bg-white shadow-lg hover:shadow-xl transition-all overflow-hidden group"
                        :class="plan.name === 'Ultimate' ? 'border-amber-300' : 'border-blue-200'"
                    >
                        <!-- Badge -->
                        <div v-if="planBadge[plan.name]" class="absolute top-0 right-0 px-4 py-1.5 rounded-bl-xl text-xs font-black text-white" :class="plan.name === 'Ultimate' ? 'bg-gradient-to-r from-amber-500 to-orange-500' : 'bg-gradient-to-r from-blue-500 to-indigo-500'">
                            {{ planBadge[plan.name] }}
                        </div>

                        <div class="p-6">
                            <h4 class="text-2xl font-black text-gray-900">{{ plan.name }}</h4>
                            <div class="mt-3">
                                <span class="text-3xl font-black text-gray-900">{{ formatPrice(plan.price_monthly) }}</span>
                                <span class="text-gray-400 text-sm">/month</span>
                            </div>
                            <p class="text-xs text-gray-400 mt-1">
                                or {{ formatPrice(plan.price_yearly) }}/year
                                <span class="text-emerald-600 font-bold">(save {{ Math.round((1 - plan.price_yearly / (plan.price_monthly * 12)) * 100) }}%)</span>
                            </p>

                            <!-- Feature highlights -->
                            <div class="mt-5 space-y-2">
                                <template v-for="(categoryFeatures, category) in plan.features" :key="category">
                                    <div v-for="feature in categoryFeatures" :key="feature.id" class="flex items-center gap-2 text-sm">
                                        <svg class="w-4 h-4 flex-shrink-0" :class="feature.is_enabled ? 'text-emerald-500' : 'text-gray-300'" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                        <span :class="feature.is_enabled ? 'text-gray-700' : 'text-gray-400 line-through'">
                                            {{ feature.name }}
                                            <span v-if="feature.type === 'numeric' && feature.value" class="text-xs text-gray-400">({{ feature.value }})</span>
                                        </span>
                                    </div>
                                </template>
                            </div>

                            <a
                                :href="route('billing.portal')"
                                target="_blank"
                                class="mt-6 w-full inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl font-bold text-sm text-white transition-all hover:scale-[1.02] hover:shadow-lg text-center cursor-pointer"
                                :class="plan.name === 'Ultimate' ? 'bg-gradient-to-r from-amber-500 to-orange-500' : 'bg-gradient-to-r from-blue-500 to-indigo-600'"
                            >
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                                Upgrade to {{ plan.name }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Already on highest plan -->
            <div v-else-if="subscription" class="text-center py-8 bg-gradient-to-r from-amber-50 to-orange-50 rounded-2xl border border-amber-200">
                <div class="w-16 h-16 mx-auto rounded-2xl bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center shadow-lg mb-4">
                    <span class="text-2xl">👑</span>
                </div>
                <h3 class="text-lg font-bold text-gray-900">You're on the highest plan!</h3>
                <p class="text-sm text-gray-500 mt-1">You have access to all premium features.</p>
            </div>
        </div>

    </AuthenticatedLayout>
</template>
