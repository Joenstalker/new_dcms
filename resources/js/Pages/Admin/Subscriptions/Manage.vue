<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    subscriptions: Object,
    pendingRegistrations: Object,
});

const activeTab = ref('subscriptions');
const selectedSubscription = ref(null);
const showModal = ref(false);

// Calculate stats
const stats = computed(() => {
    const subs = props.subscriptions || [];
    return {
        total: subs.length,
        active: subs.filter(s => s.stripe_status === 'active').length,
        pastDue: subs.filter(s => ['past_due', 'unpaid'].includes(s.stripe_status)).length,
        cancelled: subs.filter(s => s.stripe_status === 'canceled').length,
    };
});

const pendingStats = computed(() => {
    const pending = props.pendingRegistrations || [];
    return {
        total: pending.length,
        expiring: pending.filter(p => p.seconds_remaining > 0 && p.seconds_remaining < 86400).length, // < 24 hours
    };
});

// Format currency
const formatCurrency = (amount) => {
    return '₱' + Number(amount).toLocaleString('en-PH', { minimumFractionDigits: 2 });
};

// Format date
const formatDate = (date) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('en-PH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

// Get status badge class
const getStatusBadge = (status) => {
    const badges = {
        active: 'bg-green-100 text-green-800',
        past_due: 'bg-red-100 text-red-800',
        unpaid: 'bg-red-100 text-red-800',
        canceled: 'bg-gray-100 text-gray-800',
        pending: 'bg-yellow-100 text-yellow-800',
    };
    return badges[status] || 'bg-gray-100 text-gray-800';
};

// Open subscription details modal
const openSubscriptionModal = (subscription) => {
    selectedSubscription.value = subscription;
    showModal.value = true;
};

// Close modal
const closeModal = () => {
    showModal.value = false;
    selectedSubscription.value = null;
};

// Toggle payment method
const togglePaymentMethod = (subscriptionId, method) => {
    router.post(`/admin/subscriptions/${subscriptionId}/toggle-payment-method`, {
        method,
    }, {
        onSuccess: () => {
            router.reload();
        },
    });
};

// Extend billing
const extendBilling = (subscriptionId) => {
    const days = prompt('Enter number of days to extend:');
    if (days && days > 0) {
        router.post(`/admin/subscriptions/${subscriptionId}/extend-billing`, {
            days: parseInt(days),
        }, {
            onSuccess: () => {
                router.reload();
            },
        });
    }
};

// Suspend subscription
const suspendSubscription = (subscriptionId) => {
    if (confirm('Are you sure you want to suspend this subscription?')) {
        router.post(`/admin/subscriptions/${subscriptionId}/suspend`, {}, {
            onSuccess: () => {
                router.reload();
            },
        });
    }
};

// Activate subscription
const activateSubscription = (subscriptionId) => {
    if (confirm('Are you sure you want to activate this subscription?')) {
        router.post(`/admin/subscriptions/${subscriptionId}/activate`, {}, {
            onSuccess: () => {
                router.reload();
            },
        });
    }
};

// Format time remaining for pending registrations
const formatTimeRemaining = (seconds) => {
    if (seconds <= 0) return 'Expired';
    
    const days = Math.floor(seconds / 86400);
    const hours = Math.floor((seconds % 86400) / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    
    if (days > 0) return `${days}d ${hours}h`;
    if (hours > 0) return `${hours}h ${minutes}m`;
    return `${minutes}m`;
};
</script>

<template>
    <Head title="Manage Subscriptions" />
    <AdminLayout>
        <template #header>
            <h1 class="text-xl font-bold text-gray-900">Subscription Management</h1>
        </template>

        <div class="max-w-7xl mx-auto space-y-6">
            <!-- Tabs -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-1">
                <div class="flex gap-1">
                    <button
                        @click="activeTab = 'subscriptions'"
                        :class="[
                            'flex-1 py-2 px-4 rounded-lg text-sm font-medium transition-colors',
                            activeTab === 'subscriptions' 
                                ? 'bg-teal-500 text-white' 
                                : 'text-gray-600 hover:bg-gray-50'
                        ]"
                    >
                        Active Subscriptions
                        <span class="ml-1 text-xs opacity-75">({{ stats.total }})</span>
                    </button>
                    <button
                        @click="activeTab = 'pending'"
                        :class="[
                            'flex-1 py-2 px-4 rounded-lg text-sm font-medium transition-colors',
                            activeTab === 'pending' 
                                ? 'bg-teal-500 text-white' 
                                : 'text-gray-600 hover:bg-gray-50'
                        ]"
                    >
                        Pending Registrations
                        <span class="ml-1 text-xs opacity-75">({{ pendingStats.total }})</span>
                    </button>
                </div>
            </div>

            <!-- Subscriptions Tab -->
            <div v-if="activeTab === 'subscriptions'" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">
                                    Clinic
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">
                                    Plan
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">
                                    Status
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">
                                    Days Left
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">
                                    Payment Methods
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr 
                                v-for="sub in subscriptions.data" 
                                :key="sub.id" 
                                class="hover:bg-gray-50 transition-colors"
                            >
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ sub.tenant?.name || sub.tenant_id }}</div>
                                    <div class="text-xs text-gray-500" v-if="sub.tenant?.domains?.length">
                                        {{ sub.tenant.domains[0].domain }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ sub.plan?.name || 'Unknown' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium', getStatusBadge(sub.stripe_status)]">
                                        {{ sub.stripe_status || 'N/A' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ sub.days_remaining ?? 0 }} days
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex gap-2">
                                        <span 
                                            v-if="sub.stripe_enabled" 
                                            class="inline-flex items-center px-2 py-1 text-xs font-medium bg-purple-100 text-purple-800 rounded"
                                        >
                                            Stripe
                                        </span>
                                        <span 
                                            v-if="sub.gcash_enabled" 
                                            class="inline-flex items-center px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded"
                                        >
                                            GCash
                                        </span>
                                        <span 
                                            v-if="sub.paymaya_enabled" 
                                            class="inline-flex items-center px-2 py-1 text-xs font-medium bg-cyan-100 text-cyan-800 rounded"
                                        >
                                            PayMaya
                                        </span>
                                        <span 
                                            v-if="sub.bank_transfer_enabled" 
                                            class="inline-flex items-center px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded"
                                        >
                                            Bank
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <button
                                        @click="openSubscriptionModal(sub)"
                                        class="text-teal-600 hover:text-teal-900 text-sm font-medium"
                                    >
                                        Manage
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="subscriptions.data.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    No subscriptions found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pending Registrations Tab -->
            <div v-if="activeTab === 'pending'" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">
                                    Clinic
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">
                                    Plan
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">
                                    Amount
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">
                                    Time Remaining
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">
                                    Auto-Approve
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr 
                                v-for="reg in pendingRegistrations" 
                                :key="reg.id" 
                                class="hover:bg-gray-50 transition-colors"
                            >
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ reg.clinic_name }}</div>
                                    <div class="text-xs text-gray-500">{{ reg.subdomain }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ reg.plan?.name || 'Unknown' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ formatCurrency(reg.amount_paid) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span 
                                        :class="[
                                            'text-sm font-medium',
                                            reg.seconds_remaining < 86400 ? 'text-red-600' : 'text-gray-900'
                                        ]"
                                    >
                                        {{ formatTimeRemaining(reg.seconds_remaining) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span 
                                        :class="[
                                            'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                            reg.is_auto_approve_enabled ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                                        ]"
                                    >
                                        {{ reg.is_auto_approve_enabled ? 'Enabled' : 'Disabled' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <Link
                                        :href="`/admin/pending-registrations/${reg.id}`"
                                        class="text-teal-600 hover:text-teal-900 text-sm font-medium"
                                    >
                                        View Details
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="pendingRegistrations.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    No pending registrations.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Subscription Management Modal -->
        <div v-if="showModal && selectedSubscription" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="closeModal"></div>
                
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">
                                    Manage Subscription
                                </h3>
                                <div class="mt-4 space-y-4">
                                    <!-- Clinic Info -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Clinic</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ selectedSubscription.tenant?.name }}</p>
                                    </div>
                                    
                                    <!-- Plan -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Plan</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ selectedSubscription.plan?.name }}</p>
                                    </div>
                                    
                                    <!-- Status -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Status</label>
                                        <span :class="['mt-1 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium', getStatusBadge(selectedSubscription.stripe_status)]">
                                            {{ selectedSubscription.stripe_status }}
                                        </span>
                                    </div>
                                    
                                    <!-- Days Remaining -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Days Remaining</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ selectedSubscription.days_remaining }} days</p>
                                    </div>
                                    
                                    <!-- Payment Methods -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Payment Methods</label>
                                        <div class="flex flex-wrap gap-2">
                                            <button
                                                @click="togglePaymentMethod(selectedSubscription.id, 'stripe')"
                                                :class="[
                                                    'px-3 py-2 text-sm font-medium rounded-md transition-colors',
                                                    selectedSubscription.stripe_enabled 
                                                        ? 'bg-purple-100 text-purple-800 border border-purple-300' 
                                                        : 'bg-gray-100 text-gray-500 border border-gray-300'
                                                ]"
                                            >
                                                Stripe {{ selectedSubscription.stripe_enabled ? '✓' : '✗' }}
                                            </button>
                                            <button
                                                @click="togglePaymentMethod(selectedSubscription.id, 'gcash')"
                                                :class="[
                                                    'px-3 py-2 text-sm font-medium rounded-md transition-colors',
                                                    selectedSubscription.gcash_enabled 
                                                        ? 'bg-blue-100 text-blue-800 border border-blue-300' 
                                                        : 'bg-gray-100 text-gray-500 border border-gray-300'
                                                ]"
                                            >
                                                GCash {{ selectedSubscription.gcash_enabled ? '✓' : '✗' }}
                                            </button>
                                            <button
                                                @click="togglePaymentMethod(selectedSubscription.id, 'paymaya')"
                                                :class="[
                                                    'px-3 py-2 text-sm font-medium rounded-md transition-colors',
                                                    selectedSubscription.paymaya_enabled 
                                                        ? 'bg-cyan-100 text-cyan-800 border border-cyan-300' 
                                                        : 'bg-gray-100 text-gray-500 border border-gray-300'
                                                ]"
                                            >
                                                PayMaya {{ selectedSubscription.paymaya_enabled ? '✓' : '✗' }}
                                            </button>
                                            <button
                                                @click="togglePaymentMethod(selectedSubscription.id, 'bank_transfer')"
                                                :class="[
                                                    'px-3 py-2 text-sm font-medium rounded-md transition-colors',
                                                    selectedSubscription.bank_transfer_enabled 
                                                        ? 'bg-green-100 text-green-800 border border-green-300' 
                                                        : 'bg-gray-100 text-gray-500 border border-gray-300'
                                                ]"
                                            >
                                                Bank {{ selectedSubscription.bank_transfer_enabled ? '✓' : '✗' }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                        <button
                            v-if="selectedSubscription.stripe_status === 'active'"
                            @click="suspendSubscription(selectedSubscription.id)"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Suspend
                        </button>
                        <button
                            v-if="selectedSubscription.stripe_status === 'canceled'"
                            @click="activateSubscription(selectedSubscription.id)"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Activate
                        </button>
                        <button
                            @click="extendBilling(selectedSubscription.id)"
                            class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Extend Billing
                        </button>
                        <button
                            @click="closeModal"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
