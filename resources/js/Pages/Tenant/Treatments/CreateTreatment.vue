<script setup>
import { brandingState } from '@/States/brandingState';
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    show: Boolean,
    patients: Array,
    services: {
        type: Array,
        default: () => [],
    },
    dentists: {
        type: Array,
        default: () => [],
    },
    initialPatientId: {
        type: Number,
        default: 0,
    },
    renderInPlace: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close', 'saved']);

const primaryColor = computed(() => brandingState.primary_color);
const step = ref(1);
const maxStep = 4;
const modalHeightClass = computed(() => props.renderInPlace
    ? 'h-[70vh] max-h-[70vh]'
    : 'h-[82vh] max-h-[82vh]');

const paymentAccounts = [
    { value: 'cash', label: 'Cash' },
    { value: 'credit_card', label: 'Credit Card' },
];

const stepLabels = [
    'Clinical Details',
    'Payment Details',
    'Dentist Commission',
    'Next Visit',
];

const form = useForm({
    patient_id: '',
    service_id: '',
    dentist_id: '',
    procedure: '',
    notes: '',
    diagnosis: '',
    is_last_visit: false,
    linked_treatment_id: '',
    payment_account: 'cash',
    cost: '',
    discount: '',
    total_amount_due: 0,
    amount_paid: '',
    commission_deductions: '',
    commission_percentage: '',
    commission_net: '',
    commission_use_percentage: true,
    schedule_next_visit: false,
    next_visit_at: '',
    next_visit_procedure: '',
    next_visit_dentist_id: '',
    next_visit_remarks: '',
});

const totalAmountDue = computed(() => {
    const cost = Number(form.cost || 0);
    const discount = Number(form.discount || 0);
    return Math.max(cost - discount, 0);
});

const amountPaidValue = computed(() => Math.max(Number(form.amount_paid || 0), 0));
const remainingBalance = computed(() => Math.max(totalAmountDue.value - amountPaidValue.value, 0));
const hasOverpayment = computed(() => amountPaidValue.value > totalAmountDue.value);

const computedCommissionNet = computed(() => {
    const base = Math.max(totalAmountDue.value - Number(form.commission_deductions || 0), 0);
    if (form.commission_use_percentage) {
        return Math.max((base * Number(form.commission_percentage || 0)) / 100, 0);
    }

    return Math.max(Number(form.commission_net || 0), 0);
});

const canMoveNext = computed(() => {
    if (step.value === 1) {
        return Boolean(form.patient_id && form.service_id && form.dentist_id && String(form.procedure).trim() !== '');
    }

    if (step.value === 2) {
        return Number(form.cost || 0) >= 0
            && Number(form.discount || 0) >= 0
            && amountPaidValue.value >= 0
            && amountPaidValue.value <= totalAmountDue.value;
    }

    if (step.value === 3) {
        return Number(form.commission_deductions || 0) >= 0 && (form.commission_use_percentage
            ? Number(form.commission_percentage || 0) >= 0
            : Number(form.commission_net || 0) >= 0);
    }

    if (step.value === 4 && form.schedule_next_visit) {
        return Boolean(form.next_visit_at && String(form.next_visit_procedure).trim() !== '' && form.next_visit_dentist_id);
    }

    return true;
});

watch(totalAmountDue, (value) => {
    form.total_amount_due = value;
});

watch(() => form.service_id, (serviceId) => {
    if (!serviceId) {
        return;
    }

    const selected = (props.services || []).find((service) => String(service.id) === String(serviceId));
    if (!selected || selected.price === undefined || selected.price === null) {
        return;
    }

    // Auto-fill from seeded service price; user can still edit manually.
    form.cost = Number(selected.price).toFixed(2);
});

const submit = () => {
    form.total_amount_due = totalAmountDue.value;
    form.commission_net = computedCommissionNet.value;
    form.diagnosis = String(form.procedure || '').trim().slice(0, 255) || 'Progress Note';

    form.post(route('treatments.store'), {
        preserveScroll: true,
        onSuccess: () => {
            emit('saved');
            form.reset();
            step.value = 1;
            emit('close');
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Progress note saved successfully',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
            });
        },
    });
};

const close = () => {
    form.reset();
    step.value = 1;
    emit('close');
};

const goNext = () => {
    if (step.value < maxStep && canMoveNext.value) {
        step.value += 1;
    }
};

const goBack = () => {
    if (step.value > 1) {
        step.value -= 1;
    }
};

watch(() => props.show, (value) => {
    if (value && props.initialPatientId) {
        form.patient_id = String(props.initialPatientId);
    }

    if (!value) {
        form.reset();
        step.value = 1;
    }
});
</script>

<template>
    <Modal :show="show" @close="close" maxWidth="4xl" :teleport-to-body="!renderInPlace">
        <div class="flex flex-col" :class="modalHeightClass">
            <div class="p-6 pb-4 border-b border-base-300 bg-base-100">
                <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-xl font-black text-base-content uppercase tracking-tight">New Progress Note</h2>
                    <p class="text-xs text-base-content/50 mt-1">Step {{ step }} of 4 · {{ stepLabels[step - 1] }}</p>
                </div>
                <button @click="close" class="btn btn-sm btn-circle btn-ghost">✕</button>
            </div>

                <div class="grid grid-cols-1 sm:grid-cols-4 gap-2">
                    <div
                        v-for="n in 4"
                        :key="`wizard-step-${n}`"
                        class="h-1.5 rounded-full"
                        :class="n <= step ? 'bg-primary' : 'bg-base-300'"
                        :style="n <= step ? { backgroundColor: primaryColor } : {}"
                    ></div>
                </div>
            </div>

            <form @submit.prevent="submit" class="flex-1 min-h-0 flex flex-col">
                <div class="flex-1 overflow-y-auto p-6 space-y-6">
                <section v-if="step === 1" class="space-y-4">
                    <h3 class="text-sm font-black uppercase tracking-widest text-base-content/70">Clinical Details</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="patient_id" value="Patient" />
                            <select id="patient_id" v-model="form.patient_id" class="mt-1 block w-full rounded-xl border-base-300 bg-base-100 text-sm h-11" required>
                                <option value="" disabled>Select Patient</option>
                                <option v-for="patient in patients" :key="patient.id" :value="String(patient.id)">
                                    {{ patient.first_name }} {{ patient.last_name }} (ID: {{ patient.id }})
                                </option>
                            </select>
                            <InputError :message="form.errors.patient_id" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="service_id" value="Category (Service)" />
                            <select id="service_id" v-model="form.service_id" class="mt-1 block w-full rounded-xl border-base-300 bg-base-100 text-sm h-11" required>
                                <option value="" disabled>Select Service</option>
                                <option v-for="service in services" :key="service.id" :value="String(service.id)">
                                    {{ service.name }}
                                </option>
                            </select>
                            <InputError :message="form.errors.service_id" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="dentist_id" value="Assigned Dentist" />
                            <select id="dentist_id" v-model="form.dentist_id" class="mt-1 block w-full rounded-xl border-base-300 bg-base-100 text-sm h-11" required>
                                <option value="" disabled>Select Dentist</option>
                                <option v-for="dentist in dentists" :key="dentist.id" :value="String(dentist.id)">
                                    {{ dentist.name }}
                                </option>
                            </select>
                            <InputError :message="form.errors.dentist_id" class="mt-2" />
                        </div>
                        <div class="flex items-end">
                            <label class="flex items-center gap-2 text-xs font-bold text-base-content/70">
                                <input type="checkbox" v-model="form.is_last_visit" class="checkbox checkbox-sm" />
                                Mark as Last Visit
                            </label>
                        </div>
                    </div>

                    <div>
                        <InputLabel for="procedure" value="Procedure" />
                        <textarea id="procedure" v-model="form.procedure" class="mt-1 block w-full rounded-xl border-base-300 bg-base-100 text-sm min-h-[110px]" placeholder="Procedure" required></textarea>
                        <InputError :message="form.errors.procedure" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="notes" value="Remarks" />
                        <textarea id="notes" v-model="form.notes" class="mt-1 block w-full rounded-xl border-base-300 bg-base-100 text-sm min-h-[90px]" placeholder="Remarks"></textarea>
                        <InputError :message="form.errors.notes" class="mt-2" />
                    </div>
                </section>

                <section v-if="step === 2" class="space-y-4">
                    <h3 class="text-sm font-black uppercase tracking-widest text-base-content/70">Payment Details</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <InputLabel for="payment_account" value="Account" />
                            <select id="payment_account" v-model="form.payment_account" class="mt-1 block w-full rounded-xl border-base-300 bg-base-100 text-sm h-11">
                                <option v-for="account in paymentAccounts" :key="account.value" :value="account.value">
                                    {{ account.label }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <InputLabel for="cost" value="Cost" />
                            <TextInput id="cost" v-model="form.cost" type="number" step="0.01" class="mt-1 block w-full" placeholder="0.00" />
                            <InputError :message="form.errors.cost" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="discount" value="Discount" />
                            <TextInput id="discount" v-model="form.discount" type="number" step="0.01" class="mt-1 block w-full" placeholder="0.00" />
                            <InputError :message="form.errors.discount" class="mt-2" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="total_amount_due" value="Total Amount Due" />
                            <TextInput id="total_amount_due" :model-value="totalAmountDue.toFixed(2)" type="text" class="mt-1 block w-full bg-base-200/70" readonly />
                        </div>
                        <div>
                            <InputLabel for="amount_paid" value="Amount Paid" />
                            <TextInput id="amount_paid" v-model="form.amount_paid" type="number" step="0.01" class="mt-1 block w-full" placeholder="0.00" />
                            <InputError :message="form.errors.amount_paid" class="mt-2" />
                        </div>
                    </div>

                    <div class="rounded-xl border border-base-300 bg-base-100 p-3 flex flex-wrap items-center justify-between gap-2">
                        <p class="text-[10px] font-black uppercase tracking-widest text-base-content/50">Remaining Balance</p>
                        <p class="text-sm font-black" :class="remainingBalance > 0 ? 'text-warning' : 'text-success'">
                            ₱{{ remainingBalance.toFixed(2) }}
                        </p>
                    </div>

                    <p v-if="hasOverpayment" class="text-xs font-bold text-error">
                        Amount Paid cannot be greater than Total Amount Due.
                    </p>
                </section>

                <section v-if="step === 3" class="space-y-4">
                    <h3 class="text-sm font-black uppercase tracking-widest text-base-content/70">Dentist Commission</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <InputLabel for="commission_deductions" value="Deductions Lab Fee, etc" />
                            <TextInput id="commission_deductions" v-model="form.commission_deductions" type="number" step="0.01" class="mt-1 block w-full" placeholder="0.00" />
                        </div>
                        <div>
                            <InputLabel for="commission_percentage" value="Dentist Percentage" />
                            <TextInput id="commission_percentage" v-model="form.commission_percentage" type="number" step="0.01" class="mt-1 block w-full" placeholder="0" :disabled="!form.commission_use_percentage" />
                        </div>
                        <div>
                            <InputLabel for="commission_net" value="Net Commission" />
                            <TextInput
                                v-if="form.commission_use_percentage"
                                id="commission_net"
                                :model-value="computedCommissionNet.toFixed(2)"
                                type="text"
                                class="mt-1 block w-full bg-base-200/70"
                                readonly
                            />
                            <TextInput
                                v-else
                                id="commission_net"
                                v-model="form.commission_net"
                                type="number"
                                step="0.01"
                                class="mt-1 block w-full"
                                placeholder="0.00"
                            />
                        </div>
                    </div>

                    <label class="flex items-center gap-2 text-xs font-bold text-base-content/70">
                        <input type="checkbox" v-model="form.commission_use_percentage" class="checkbox checkbox-sm" />
                        Use Percentage (uncheck to set Net Commission manually)
                    </label>
                </section>

                <section v-if="step === 4" class="space-y-4">
                    <h3 class="text-sm font-black uppercase tracking-widest text-base-content/70">Next Visit</h3>
                    <label class="flex items-center gap-2 text-xs font-bold text-base-content/70">
                        <input type="checkbox" v-model="form.schedule_next_visit" class="checkbox checkbox-sm" />
                        Schedule Patient&apos;s Next Visit
                    </label>

                    <div v-if="form.schedule_next_visit" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="next_visit_at" value="Date and Time" />
                            <TextInput id="next_visit_at" v-model="form.next_visit_at" type="datetime-local" class="mt-1 block w-full" />
                            <InputError :message="form.errors.next_visit_at" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="next_visit_dentist_id" value="Assigned Dentist" />
                            <select id="next_visit_dentist_id" v-model="form.next_visit_dentist_id" class="mt-1 block w-full rounded-xl border-base-300 bg-base-100 text-sm h-11">
                                <option value="" disabled>Select Dentist</option>
                                <option v-for="dentist in dentists" :key="`next-${dentist.id}`" :value="String(dentist.id)">
                                    {{ dentist.name }}
                                </option>
                            </select>
                            <InputError :message="form.errors.next_visit_dentist_id" class="mt-2" />
                        </div>
                        <div class="md:col-span-2">
                            <InputLabel for="next_visit_procedure" value="Procedure" />
                            <textarea id="next_visit_procedure" v-model="form.next_visit_procedure" class="mt-1 block w-full rounded-xl border-base-300 bg-base-100 text-sm min-h-[80px]" placeholder="Procedure for next visit"></textarea>
                            <InputError :message="form.errors.next_visit_procedure" class="mt-2" />
                        </div>
                        <div class="md:col-span-2">
                            <InputLabel for="next_visit_remarks" value="Remarks" />
                            <textarea id="next_visit_remarks" v-model="form.next_visit_remarks" class="mt-1 block w-full rounded-xl border-base-300 bg-base-100 text-sm min-h-[80px]" placeholder="Remarks"></textarea>
                        </div>
                    </div>
                </section>
                </div>

                <div class="shrink-0 sticky bottom-0 z-10 flex items-center justify-between gap-3 p-4 sm:p-5 border-t border-base-300 bg-base-100">
                    <div class="text-xs font-bold text-base-content/45 uppercase tracking-widest">
                        {{ stepLabels[step - 1] }}
                    </div>

                    <div class="flex items-center gap-2">
                        <SecondaryButton @click="close" type="button" class="rounded-xl px-5 h-10">
                            Cancel
                        </SecondaryButton>
                        <SecondaryButton v-if="step > 1" @click="goBack" type="button" class="rounded-xl px-5 h-10">
                            Back
                        </SecondaryButton>
                        <button
                            v-if="step < 4"
                            type="button"
                            @click="goNext"
                            class="btn rounded-xl px-6 h-10 border-0 text-white font-black text-xs uppercase tracking-widest"
                            :style="{ backgroundColor: primaryColor }"
                            :disabled="!canMoveNext"
                            :class="!canMoveNext ? 'opacity-50 cursor-not-allowed' : ''"
                        >
                            Next
                        </button>
                        <button
                            v-else
                            type="submit"
                            class="btn rounded-xl px-6 h-10 border-0 text-white font-black text-xs uppercase tracking-widest"
                            :style="{ backgroundColor: primaryColor }"
                            :class="{ 'opacity-25': form.processing }"
                            :disabled="form.processing || !canMoveNext"
                        >
                            Save
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </Modal>
</template>
