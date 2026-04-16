<script setup>
import { brandingState } from '@/States/brandingState';
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    show: Boolean,
    treatment: Object,
    patients: Array,
    renderInPlace: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close']);

const primaryColor = computed(() => brandingState.primary_color);

const form = useForm({
    patient_id: '',
    diagnosis: '',
    procedure: '',
    cost: '',
    notes: '',
});

watch(() => props.treatment, (newTreatment) => {
    if (newTreatment) {
        form.patient_id = newTreatment.patient_id;
        form.diagnosis = newTreatment.diagnosis;
        form.procedure = newTreatment.procedure;
        form.cost = newTreatment.cost;
        form.notes = newTreatment.notes || '';
    }
}, { immediate: true });

const submit = () => {
    form.put(route('treatments.update', props.treatment.id), {
        preserveScroll: true,
        onSuccess: () => {
            emit('close');
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Treatment record updated successfully',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
            });
        },
    });
};

const close = () => {
    emit('close');
};
</script>

<template>
    <Modal :show="show" @close="close" :teleport-to-body="!renderInPlace">
        <div class="p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-black text-base-content uppercase tracking-tight">Edit Treatment</h2>
                <button @click="close" class="btn btn-sm btn-circle btn-ghost">✕</button>
            </div>

            <form @submit.prevent="submit" class="space-y-4">
                <!-- Patient Selection -->
                <div>
                    <InputLabel for="edit_patient_id" value="Patient" />
                    <select
                        id="edit_patient_id"
                        v-model="form.patient_id"
                        class="mt-1 block w-full rounded-xl border-base-300 bg-base-100 text-sm font-medium focus:border-primary focus:ring-primary shadow-sm h-11"
                        required
                    >
                        <option value="" disabled>Select Patient</option>
                        <option v-for="patient in patients" :key="patient.id" :value="patient.id">
                            {{ patient.first_name }} {{ patient.last_name }} (ID: {{ patient.id }})
                        </option>
                    </select>
                    <InputError :message="form.errors.patient_id" class="mt-2" />
                </div>

                <!-- Diagnosis -->
                <div>
                    <InputLabel for="edit_diagnosis" value="Diagnosis" />
                    <TextInput
                        id="edit_diagnosis"
                        v-model="form.diagnosis"
                        type="text"
                        class="mt-1 block w-full shadow-sm"
                        required
                    />
                    <InputError :message="form.errors.diagnosis" class="mt-2" />
                </div>

                <!-- Procedure -->
                <div>
                    <InputLabel for="edit_procedure" value="Procedure" />
                    <textarea
                        id="edit_procedure"
                        v-model="form.procedure"
                        class="mt-1 block w-full rounded-xl border-base-300 bg-base-100 text-sm font-medium focus:border-primary focus:ring-primary shadow-sm min-h-[100px]"
                        required
                    ></textarea>
                    <InputError :message="form.errors.procedure" class="mt-2" />
                </div>

                <!-- Cost -->
                <div>
                    <InputLabel for="edit_cost" value="Cost" />
                    <div class="relative mt-1">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-base-content/40 font-bold">₱</span>
                        <TextInput
                            id="edit_cost"
                            v-model="form.cost"
                            type="number"
                            step="0.01"
                            class="block w-full pl-8 shadow-sm"
                            required
                        />
                    </div>
                    <InputError :message="form.errors.cost" class="mt-2" />
                </div>

                <!-- Notes -->
                <div>
                    <InputLabel for="edit_notes" value="Notes (Optional)" />
                    <textarea
                        id="edit_notes"
                        v-model="form.notes"
                        class="mt-1 block w-full rounded-xl border-base-300 bg-base-100 text-sm font-medium focus:border-primary focus:ring-primary shadow-sm min-h-[80px]"
                    ></textarea>
                    <InputError :message="form.errors.notes" class="mt-2" />
                </div>

                <div class="flex items-center justify-end space-x-3 mt-8">
                    <SecondaryButton @click="close" type="button" class="rounded-xl px-6 h-11">
                        Cancel
                    </SecondaryButton>
                    <button
                        type="submit"
                        class="btn rounded-xl px-8 h-11 border-0 text-white shadow-lg shadow-primary/20 hover:scale-[1.02] hover:shadow-xl transition-all font-black text-xs uppercase tracking-widest"
                        :style="{ backgroundColor: primaryColor }"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                    >
                        Update Treatment
                    </button>
                </div>
            </form>
        </div>
    </Modal>
</template>
