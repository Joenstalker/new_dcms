<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, watch, computed } from 'vue';
import Cashier from './Cashier.vue';
import Transactions from './Transactions.vue';
import Receipts from './Receipts.vue';

const props = defineProps({
    invoices: { type: Array, default: () => [] },
    patients: { type: Array, default: () => [] },
    services: { type: Array, default: () => [] },
});

const page = usePage();
const tenantId = computed(() => page.props.tenant?.id || null);
const liveInvoices = ref([...(props.invoices || [])]);
let billingChannel = null;

watch(() => props.invoices, (nextInvoices) => {
    liveInvoices.value = [...(nextInvoices || [])];
}, { deep: true });

// Active tab from URL query
const activeTab = ref('cashier');

onMounted(() => {
    const params = new URLSearchParams(window.location.search);
    activeTab.value = params.get('tab') || 'cashier';

    if (!window.Echo || !tenantId.value) return;

    billingChannel = window.Echo.private(`tenant.${tenantId.value}.billing`)
        .listen('.TenantInvoiceChanged', (event) => {
            const incoming = event?.invoice;
            const action = event?.action;

            if (!incoming || !incoming.id) return;

            if (action === 'deleted') {
                liveInvoices.value = liveInvoices.value.filter((item) => item.id !== incoming.id);
                return;
            }

            const existingIndex = liveInvoices.value.findIndex((item) => item.id === incoming.id);

            if (existingIndex >= 0) {
                liveInvoices.value[existingIndex] = {
                    ...liveInvoices.value[existingIndex],
                    ...incoming,
                };
                return;
            }

            liveInvoices.value = [incoming, ...liveInvoices.value];
        });
});

onUnmounted(() => {
    if (window.Echo && tenantId.value) {
        window.Echo.leave(`tenant.${tenantId.value}.billing`);
    }

    billingChannel = null;
});
</script>

<template>
    <Head title="Billing & POS" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-black tracking-tight text-base-content">Billing & Payments</h2>
        </template>

        <!-- Cashier / New Invoice Tab -->
        <Cashier v-if="activeTab === 'cashier'" :patients="patients" :invoices="liveInvoices" :services="services" />

        <!-- Transactions Tab -->
        <Transactions v-else-if="activeTab === 'transactions'" :invoices="liveInvoices" />

        <!-- Receipts Tab -->
        <Receipts v-else-if="activeTab === 'receipts'" :invoices="liveInvoices" />
    </AuthenticatedLayout>
</template>
