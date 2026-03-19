<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({
    invoices: Array,
    patients: Array,
});

const markAsPaid = (id) => {
    if(confirm('Are you sure you want to mark this invoice as fully paid?')) {
        router.put(`/billing/${id}`, { status: 'paid' }, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Billing & POS" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Billing & Payments
            </h2>
        </template>

        <div class="p-6 bg-white rounded-lg shadow-md mt-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-medium text-gray-800">Invoices</h3>
                <button class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition">
                    Create Invoice
                </button>
            </div>

            <div v-if="$page.props.flash && $page.props.flash.success" class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                {{ $page.props.flash.success }}
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount Paid</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="invoice in invoices" :key="invoice.id">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-mono">
                                #INV-{{ String(invoice.id).padStart(5, '0') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <Link v-if="invoice.patient" :href="`/patients/${invoice.patient_id}`" class="text-sm font-medium text-blue-600 hover:text-blue-900">
                                    {{ invoice.patient.first_name }} {{ invoice.patient.last_name }}
                                </Link>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                ${{ Number(invoice.total_amount).toFixed(2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                ${{ Number(invoice.amount_paid).toFixed(2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="{
                                    'px-2 inline-flex text-xs leading-5 font-semibold rounded-full': true,
                                    'bg-red-100 text-red-800': invoice.status === 'unpaid',
                                    'bg-yellow-100 text-yellow-800': invoice.status === 'partially_paid',
                                    'bg-green-100 text-green-800': invoice.status === 'paid',
                                }">
                                    {{ invoice.status.replace('_', ' ').toUpperCase() }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button @click="markAsPaid(invoice.id)" v-if="invoice.status !== 'paid'" class="text-indigo-600 hover:text-indigo-900 mr-3">Mark Paid</button>
                                <Link :href="`/billing/${invoice.id}`" class="text-blue-600 hover:text-blue-900">View</Link>
                            </td>
                        </tr>
                        <tr v-if="invoices.length === 0">
                            <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">No invoices generated.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
