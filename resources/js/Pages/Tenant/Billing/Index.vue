<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import Cashier from './Cashier.vue';
import Transactions from './Transactions.vue';
import Receipts from './Receipts.vue';

defineProps({
    invoices: { type: Array, default: () => [] },
    patients: { type: Array, default: () => [] },
});

// Active tab from URL query
const activeTab = ref('cashier');

onMounted(() => {
    const params = new URLSearchParams(window.location.search);
    activeTab.value = params.get('tab') || 'cashier';
});
</script>

<template>
    <Head title="Billing & POS" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-black tracking-tight text-base-content">Billing & Payments</h2>
        </template>

        <!-- Cashier / New Invoice Tab -->
        <Cashier v-if="activeTab === 'cashier'" :patients="patients" :invoices="invoices" />

        <!-- Transactions Tab -->
        <Transactions v-else-if="activeTab === 'transactions'" :invoices="invoices" />

        <!-- Receipts Tab -->
        <Receipts v-else-if="activeTab === 'receipts'" :invoices="invoices" />
    </AuthenticatedLayout>
</template>
