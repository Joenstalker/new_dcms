<script setup>
import { brandingState } from '@/States/brandingState';
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

const props = defineProps({
    patient: { type: Object, required: true }
});

const primaryColor = computed(() => brandingState.primary_color);

const showAddForm = ref(false);

const form = useForm({
    patient_id: props.patient.id,
    diagnosis: '',
    procedure: '',
    cost: '',
    notes: '',
});

const submitTreatment = () => {
    form.post(route('treatments.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            showAddForm.value = false;
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Treatment recorded successfully',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
            });
            // The patient balance should update automatically via inertia reload if handled correctly, 
            // but since we are in a modal that fetched data via JSON, we might need a manual refresh or 
            // rely on the user closing/reopening. 
            // For now, we'll suggest a page refresh or just let it be.
        },
    });
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric', month: 'short', day: 'numeric'
    });
};

const totalPaid = computed(() => {
    return props.patient.invoices?.reduce((sum, inv) => sum + Number(inv.amount_paid || 0), 0) || 0;
});

const totalCharges = computed(() => {
    return props.patient.treatments?.reduce((sum, t) => sum + Number(t.cost || 0), 0) || 0;
});
</script>

<template>
    <div class="space-y-6 animate-in fade-in duration-500">
        <!-- Balance Overview Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-base-100 p-5 rounded-3xl border border-base-200 shadow-sm">
                <p class="text-[10px] font-black text-base-content/40 uppercase tracking-widest mb-1">Total Charges</p>
                <p class="text-xl font-black text-base-content">₱{{ totalCharges.toLocaleString(undefined, { minimumFractionDigits: 2 }) }}</p>
            </div>
            <div class="bg-base-100 p-5 rounded-3xl border border-base-200 shadow-sm">
                <p class="text-[10px] font-black text-base-content/40 uppercase tracking-widest mb-1">Total Paid</p>
                <p class="text-xl font-black text-success">₱{{ totalPaid.toLocaleString(undefined, { minimumFractionDigits: 2 }) }}</p>
            </div>
            <div class="p-5 rounded-3xl shadow-lg border-0 text-white relative overflow-hidden" :style="{ backgroundColor: primaryColor }">
                <div class="relative z-10">
                    <p class="text-[10px] font-black opacity-60 uppercase tracking-widest mb-1">Remaining Balance</p>
                    <p class="text-2xl font-black">₱{{ Number(patient.balance || 0).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}</p>
                </div>
                <!-- Subtle Gradient Overlay -->
                <div class="absolute inset-0 bg-gradient-to-br from-white/10 to-transparent pointer-events-none"></div>
            </div>
        </div>

        <!-- Section Header -->
        <div class="flex items-center justify-between border-b border-base-200 pb-4">
            <h4 class="text-sm font-black text-base-content/40 uppercase tracking-widest">Transaction History</h4>
            <button 
                @click="showAddForm = !showAddForm"
                class="btn btn-sm btn-ghost gap-2 rounded-xl text-xs font-black uppercase tracking-widest"
                :style="{ color: primaryColor }"
            >
                <svg v-if="!showAddForm" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" /></svg>
                <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                {{ showAddForm ? 'Cancel' : 'Record Service' }}
            </button>
        </div>

        <!-- Add Treatment Form (Quick Entry) -->
        <div v-if="showAddForm" class="bg-base-200/50 p-6 rounded-3xl border border-base-200 border-dashed animate-in slide-in-from-top-4 duration-300">
            <form @submit.prevent="submitTreatment" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="col-span-1">
                    <label class="text-[10px] font-black text-base-content/40 uppercase tracking-widest ml-1">Diagnosis</label>
                    <input v-model="form.diagnosis" type="text" placeholder="e.g. Tooth Decay" class="input input-sm w-full mt-1 bg-base-100 rounded-xl border-base-300 focus:border-primary font-bold" required />
                </div>
                <div class="col-span-1">
                    <label class="text-[10px] font-black text-base-content/40 uppercase tracking-widest ml-1">Cost (₱)</label>
                    <input v-model="form.cost" type="number" step="0.01" placeholder="0.00" class="input input-sm w-full mt-1 bg-base-100 rounded-xl border-base-300 focus:border-primary font-bold text-primary" required />
                </div>
                <div class="col-span-2">
                    <label class="text-[10px] font-black text-base-content/40 uppercase tracking-widest ml-1">Procedure Details</label>
                    <textarea v-model="form.procedure" placeholder="Describe the procedure performed..." class="textarea textarea-sm w-full mt-1 bg-base-100 rounded-xl border-base-300 focus:border-primary font-medium min-h-[80px]" required></textarea>
                </div>
                <div class="col-span-2 flex justify-end">
                    <button type="submit" class="btn btn-sm btn-primary px-8 rounded-xl text-white font-black uppercase tracking-widest shadow-md hover:scale-105 transition-transform h-10" :style="{ backgroundColor: primaryColor }" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Save Record' }}
                    </button>
                </div>
            </form>
        </div>

        <!-- Treatments List -->
        <div class="space-y-3">
            <div v-if="patient.treatments && patient.treatments.length > 0">
                <div v-for="treatment in patient.treatments" :key="treatment.id" class="group p-4 bg-base-100 rounded-2xl border border-base-200 hover:border-primary/30 transition-all shadow-sm">
                    <div class="flex justify-between items-start">
                        <div class="flex gap-4">
                            <div class="w-10 h-10 rounded-xl bg-base-200 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-base-content/30" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                            </div>
                            <div>
                                <p class="text-sm font-black text-base-content uppercase leading-none">{{ treatment.diagnosis }}</p>
                                <p class="text-xs font-medium text-base-content/50 mt-1 leading-tight">{{ treatment.procedure }}</p>
                                <p class="text-[10px] font-bold text-base-content/30 uppercase tracking-widest mt-2">{{ formatDate(treatment.created_at) }}</p>
                            </div>
                        </div>
                        <p class="font-black text-sm" :style="{ color: primaryColor }">₱{{ Number(treatment.cost || 0).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}</p>
                    </div>
                </div>
            </div>
            
            <!-- Invoices List (Brief) -->
            <div v-if="patient.invoices && patient.invoices.length > 0" class="mt-8">
                <h5 class="text-[10px] font-black text-base-content/40 uppercase tracking-widest mb-4 ml-1">Invoice Payments</h5>
                <div v-for="invoice in patient.invoices" :key="invoice.id" class="p-4 bg-base-200/30 rounded-2xl border border-base-200 flex justify-between items-center mb-2">
                    <div class="flex items-center gap-3">
                        <div class="w-2 h-2 rounded-full" :class="invoice.status === 'paid' ? 'bg-success' : 'bg-warning'"></div>
                        <span class="text-xs font-black text-base-content/60 uppercase tracking-widest">INV-{{ invoice.id }}</span>
                        <span class="badge badge-sm uppercase font-black text-[9px] tracking-widest px-2" :class="invoice.status === 'paid' ? 'badge-success text-white' : 'badge-warning'">{{ invoice.status }}</span>
                    </div>
                    <p class="text-xs font-bold text-success">Paid: ₱{{ Number(invoice.amount_paid || 0).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}</p>
                </div>
            </div>

            <div v-if="(!patient.treatments || patient.treatments.length === 0) && (!patient.invoices || patient.invoices.length === 0)" class="text-center py-16">
                <div class="w-16 h-16 bg-base-200 rounded-3xl mx-auto flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-base-content/10" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <p class="text-xs font-bold text-base-content/30 uppercase tracking-widest">No financial activity recorded.</p>
            </div>
        </div>
    </div>
</template>
