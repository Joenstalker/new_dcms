<script setup>
import { computed } from 'vue';
import { usePage, Link, router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

const props = defineProps({
    invoices: { type: Array, default: () => [] },
});

const branding = computed(() => usePage().props.branding || {});
const primaryColor = computed(() => branding.value.primary_color || '#0ea5e9');

const statusColor = (status) => {
    const colors = {
        'unpaid': 'bg-error/10 text-error',
        'partially_paid': 'bg-warning/10 text-warning',
        'paid': 'bg-success/10 text-success',
    };
    return colors[status] || 'bg-base-200 text-base-content/50';
};

const markAsPaid = (id) => {
    Swal.fire({
        title: 'Mark as Paid?',
        text: 'This will mark the invoice as fully paid.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: primaryColor.value,
        confirmButtonText: 'Yes, Mark Paid',
    }).then((result) => {
        if (result.isConfirmed) {
            router.put(`/billing/${id}`, { status: 'paid' }, { preserveScroll: true });
        }
    });
};
</script>

<template>
    <div class="space-y-6">
        <!-- Stats Row -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-base-100 rounded-2xl border border-base-300 p-5">
                <p class="text-[10px] font-black text-base-content/30 uppercase tracking-[0.2em]">Total Invoices</p>
                <p class="text-2xl font-black text-base-content mt-1">{{ invoices.length }}</p>
            </div>
            <div class="bg-base-100 rounded-2xl border border-base-300 p-5">
                <p class="text-[10px] font-black text-base-content/30 uppercase tracking-[0.2em]">Paid</p>
                <p class="text-2xl font-black text-success mt-1">{{ invoices.filter(i => i.status === 'paid').length }}</p>
            </div>
            <div class="bg-base-100 rounded-2xl border border-base-300 p-5">
                <p class="text-[10px] font-black text-base-content/30 uppercase tracking-[0.2em]">Unpaid</p>
                <p class="text-2xl font-black text-error mt-1">{{ invoices.filter(i => i.status !== 'paid').length }}</p>
            </div>
        </div>

        <!-- Transactions Table -->
        <div class="bg-base-100 rounded-2xl border border-base-300 overflow-hidden">
            <table class="table w-full">
                <thead>
                    <tr class="bg-base-200/50">
                        <th class="text-[10px] font-black uppercase tracking-widest text-base-content/30">Invoice ID</th>
                        <th class="text-[10px] font-black uppercase tracking-widest text-base-content/30">Patient</th>
                        <th class="text-[10px] font-black uppercase tracking-widest text-base-content/30 text-right">Total</th>
                        <th class="text-[10px] font-black uppercase tracking-widest text-base-content/30 text-right">Paid</th>
                        <th class="text-[10px] font-black uppercase tracking-widest text-base-content/30 text-center">Status</th>
                        <th class="text-[10px] font-black uppercase tracking-widest text-base-content/30 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="invoice in invoices" :key="invoice.id" class="hover">
                        <td class="font-mono text-sm font-bold text-base-content/60">
                            #INV-{{ String(invoice.id).padStart(5, '0') }}
                        </td>
                        <td>
                            <Link v-if="invoice.patient" :href="`/patients/${invoice.patient_id}`" 
                                class="text-sm font-bold hover:underline" :style="{ color: primaryColor }">
                                {{ invoice.patient.first_name }} {{ invoice.patient.last_name }}
                            </Link>
                            <span v-else class="text-sm text-base-content/30">—</span>
                        </td>
                        <td class="text-sm font-bold text-base-content text-right">
                            ₱{{ Number(invoice.total_amount).toFixed(2) }}
                        </td>
                        <td class="text-sm text-base-content/50 text-right">
                            ₱{{ Number(invoice.amount_paid || 0).toFixed(2) }}
                        </td>
                        <td class="text-center">
                            <span :class="['px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest', statusColor(invoice.status)]">
                                {{ invoice.status?.replace('_', ' ') || 'Unknown' }}
                            </span>
                        </td>
                        <td class="text-center">
                            <div class="flex items-center justify-center gap-2">
                                <button v-if="invoice.status !== 'paid'" @click="markAsPaid(invoice.id)"
                                    class="btn btn-ghost btn-xs text-success font-bold">
                                    Mark Paid
                                </button>
                                <Link :href="`/billing/${invoice.id}`" class="btn btn-ghost btn-xs font-bold" :style="{ color: primaryColor }">
                                    View
                                </Link>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="invoices.length === 0">
                        <td colspan="6" class="text-center py-16">
                            <div class="w-14 h-14 mx-auto mb-3 rounded-2xl bg-base-200 flex items-center justify-center">
                                <svg class="w-7 h-7 text-base-content/15" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75" />
                                </svg>
                            </div>
                            <p class="text-xs text-base-content/20 font-bold uppercase tracking-widest">No transactions yet</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
