<script setup>
import { brandingState } from '@/States/brandingState';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    invoices: { type: Array, default: () => [] },
});

const primaryColor = computed(() => brandingState.primary_color);

// Only show paid invoices as receipts
const receipts = computed(() => props.invoices.filter(i => i.status === 'paid'));

const formatDate = (dateStr) => {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
};

const handlePrint = (invoiceId) => {
    // Placeholder for print functionality
    window.print();
};
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-black text-base-content">Receipts</h3>
                <p class="text-xs text-base-content/40 mt-1">{{ receipts.length }} paid invoice(s)</p>
            </div>
        </div>

        <!-- Receipt Cards -->
        <div v-if="receipts.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div 
                v-for="receipt in receipts" 
                :key="receipt.id"
                class="bg-base-100 rounded-2xl border border-base-300 p-6 hover:shadow-lg transition-all duration-300 group"
            >
                <!-- Receipt Header -->
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <p class="font-mono text-xs font-bold text-base-content/40">#INV-{{ String(receipt.id).padStart(5, '0') }}</p>
                        <p class="text-sm font-black text-base-content mt-1" v-if="receipt.patient">
                            {{ receipt.patient.first_name }} {{ receipt.patient.last_name }}
                        </p>
                    </div>
                    <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-success/10 text-success">
                        Paid
                    </span>
                </div>

                <!-- Receipt Details -->
                <div class="space-y-2 mb-4">
                    <div class="flex justify-between text-xs">
                        <span class="text-base-content/40">Amount</span>
                        <span class="font-bold text-base-content">₱{{ Number(receipt.total_amount).toFixed(2) }}</span>
                    </div>
                    <div class="flex justify-between text-xs">
                        <span class="text-base-content/40">Paid</span>
                        <span class="font-bold text-success">₱{{ Number(receipt.amount_paid || receipt.total_amount).toFixed(2) }}</span>
                    </div>
                    <div class="flex justify-between text-xs">
                        <span class="text-base-content/40">Date</span>
                        <span class="font-medium text-base-content/60">{{ formatDate(receipt.updated_at) }}</span>
                    </div>
                </div>

                <!-- Receipt Actions -->
                <div class="border-t border-base-300 pt-3 flex gap-2">
                    <button 
                        @click="handlePrint(receipt.id)"
                        class="flex-1 btn btn-sm rounded-xl bg-base-200 border-0 text-[10px] font-black uppercase tracking-widest text-base-content/50 hover:text-base-content"
                    >
                        <svg class="w-3.5 h-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18.75 12h.008v.008h-.008V12Zm-1.5 0h.008v.008h-.008V12Z" />
                        </svg>
                        Print
                    </button>
                    <button 
                        class="flex-1 btn btn-sm rounded-xl border-0 text-[10px] font-black uppercase tracking-widest text-white"
                        :style="{ backgroundColor: primaryColor }"
                    >
                        <svg class="w-3.5 h-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                        </svg>
                        Download
                    </button>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else class="bg-base-100 rounded-2xl border border-base-300 p-16 text-center">
            <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-base-200 flex items-center justify-center">
                <svg class="w-8 h-8 text-base-content/15" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                </svg>
            </div>
            <h3 class="text-sm font-black text-base-content/40 uppercase tracking-widest">No receipts yet</h3>
            <p class="text-xs text-base-content/20 mt-2">Receipts will appear here once invoices are marked as paid.</p>
        </div>
    </div>
</template>
