<script setup>
import { brandingState } from '@/States/brandingState';
import { ref, computed } from 'vue';
import { usePage, Link, router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

const showInvoiceModal = ref(false);
const selectedInvoice = ref(null);

const viewInvoice = (invoice) => {
    selectedInvoice.value = invoice;
    showInvoiceModal.value = true;
};

const props = defineProps({
    invoices: { type: Array, default: () => [] },
});

const primaryColor = computed(() => brandingState.primary_color);

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
                                <button @click="viewInvoice(invoice)" class="btn btn-ghost btn-xs font-bold" :style="{ color: primaryColor }">
                                    View
                                </button>
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

    <!-- View Invoice Modal -->
    <dialog :class="['modal', { 'modal-open': showInvoiceModal }]">
        <div class="modal-box max-w-2xl bg-base-100 rounded-3xl border border-base-300 p-0 overflow-hidden">
            <!-- Header -->
            <div class="p-6 border-b border-base-300 flex justify-between items-center bg-base-200/30">
                <div>
                    <h3 class="text-xl font-black text-base-content">Invoice Details</h3>
                    <p class="text-[10px] font-black text-base-content/30 uppercase tracking-[0.2em] mt-1">
                        #INV-{{ selectedInvoice ? String(selectedInvoice.id).padStart(5, '0') : '' }}
                    </p>
                </div>
                <button @click="showInvoiceModal = false" class="btn btn-ghost btn-circle btn-sm">✕</button>
            </div>

            <div v-if="selectedInvoice" class="p-8 space-y-8">
                <!-- Info Grid -->
                <div class="grid grid-cols-2 gap-8">
                    <div class="space-y-1">
                        <p class="text-[10px] font-black text-base-content/30 uppercase tracking-[0.2em]">Patient</p>
                        <p class="text-sm font-bold text-base-content">{{ selectedInvoice.patient?.first_name }} {{ selectedInvoice.patient?.last_name }}</p>
                    </div>
                    <div class="space-y-1 text-right">
                        <p class="text-[10px] font-black text-base-content/30 uppercase tracking-[0.2em]">Date Created</p>
                        <p class="text-sm font-bold text-base-content">{{ new Date(selectedInvoice.created_at).toLocaleDateString('en-PH', { month: 'long', day: 'numeric', year: 'numeric' }) }}</p>
                    </div>
                </div>

                <!-- Items Table -->
                <div class="rounded-2xl border border-base-300 overflow-hidden">
                    <table class="table w-full">
                        <thead>
                            <tr class="bg-base-200/50">
                                <th class="text-[10px] font-black uppercase tracking-widest text-base-content/30">Item</th>
                                <th class="text-[10px] font-black uppercase tracking-widest text-base-content/30 text-center">Qty</th>
                                <th class="text-[10px] font-black uppercase tracking-widest text-base-content/30 text-right">Price</th>
                                <th class="text-[10px] font-black uppercase tracking-widest text-base-content/30 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in selectedInvoice.items" :key="item.id" class="border-b border-base-200/50">
                                <td class="text-sm font-medium">{{ item.description }}</td>
                                <td class="text-sm text-center text-base-content/60">{{ item.quantity }}</td>
                                <td class="text-sm text-right text-base-content/60">₱{{ Number(item.unit_price).toFixed(2) }}</td>
                                <td class="text-sm text-right font-bold">₱{{ (item.quantity * item.unit_price).toFixed(2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Summary -->
                <div class="flex justify-end">
                    <div class="w-64 space-y-3">
                        <div class="flex justify-between text-sm">
                            <span class="text-base-content/40">Status</span>
                            <span :class="['px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest', statusColor(selectedInvoice.status)]">
                                {{ selectedInvoice.status?.replace('_', ' ') }}
                            </span>
                        </div>
                        <div class="border-t border-base-300 pt-3 flex justify-between">
                            <span class="text-sm font-black text-base-content uppercase tracking-wider">Total Amount</span>
                            <span class="text-2xl font-black" :style="{ color: primaryColor }">₱{{ Number(selectedInvoice.total_amount).toFixed(2) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Treatment History -->
                <div v-if="selectedInvoice.patient?.treatments?.length > 0" class="mt-8 pt-8 border-t border-base-300">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-1.5 h-4 rounded-full" :style="{ backgroundColor: primaryColor }"></div>
                        <h4 class="text-[10px] font-black text-base-content/40 uppercase tracking-[0.2em]">Patient's Clinical Records</h4>
                    </div>
                    <div class="grid grid-cols-1 gap-3">
                        <div v-for="treatment in selectedInvoice.patient.treatments" :key="treatment.id" 
                            class="p-4 bg-base-200/50 rounded-2xl border border-base-300/50 flex justify-between items-center group hover:bg-base-200 transition-colors">
                            <div>
                                <p class="text-sm font-bold text-base-content">{{ treatment.procedure }}</p>
                                <p class="text-[10px] text-base-content/40 uppercase tracking-widest mt-1">
                                    {{ new Date(treatment.created_at).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) }} 
                                    <span v-if="treatment.diagnosis" class="mx-2">•</span>
                                    <span v-if="treatment.diagnosis">{{ treatment.diagnosis }}</span>
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-black" :style="{ color: primaryColor }">₱{{ Number(treatment.cost).toFixed(2) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Action -->
            <div class="p-6 bg-base-200/30 border-t border-base-300 flex justify-end gap-3">
                <button v-if="selectedInvoice?.status !== 'paid'" 
                    @click="markAsPaid(selectedInvoice.id); showInvoiceModal = false"
                    class="btn btn-sm rounded-xl text-white font-black px-6"
                    :style="{ backgroundColor: primaryColor }">
                    Mark as Fully Paid
                </button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button @click="showInvoiceModal = false">close</button>
        </form>
    </dialog>
</template>
