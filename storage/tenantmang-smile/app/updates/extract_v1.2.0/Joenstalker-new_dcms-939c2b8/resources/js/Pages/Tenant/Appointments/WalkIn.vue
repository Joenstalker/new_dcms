<script setup>
import { brandingState } from '@/States/brandingState';
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    dentists: { type: Array, default: () => [] },
    patients: { type: Array, default: () => [] },
});

const primaryColor = computed(() => brandingState.primary_color);
const patientOptions = computed(() => {
    return (props.patients || []).map((patient) => {
        const fullName = [patient.first_name, patient.last_name]
            .filter(Boolean)
            .join(' ')
            .trim();

        return {
            id: patient.id,
            label: fullName || patient.name || `Patient #${patient.id}`,
        };
    });
});

// Walk-in form state
const walkinForm = ref({
    patient_name: '',
    phone: '',
    service: '',
    dentist_id: '',
    notes: '',
    is_new_patient: true,
    existing_patient_id: '',
});

const isSubmitting = ref(false);

const handleSubmit = () => {
    // Placeholder for walk-in submission
    isSubmitting.value = true;
    setTimeout(() => { isSubmitting.value = false; }, 1000);
};
</script>

<template>
    <div class="max-w-2xl mx-auto space-y-6">
        <!-- Walk-in Header -->
        <div class="text-center">
            <div class="w-16 h-16 mx-auto mb-4 rounded-2xl flex items-center justify-center shadow-lg" :style="{ backgroundColor: primaryColor }">
                <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                </svg>
            </div>
            <h3 class="text-lg font-black text-base-content">Walk-in Registration</h3>
            <p class="text-xs text-base-content/40 mt-1">Register a walk-in patient for immediate appointment</p>
        </div>

        <!-- Walk-in Form -->
        <div class="bg-base-100 rounded-2xl border border-base-300 p-6 space-y-5">
            <!-- Patient Type Toggle -->
            <div class="flex gap-3">
                <button 
                    @click="walkinForm.is_new_patient = true"
                    :class="['flex-1 py-3 rounded-xl text-xs font-black uppercase tracking-widest transition-all duration-300',
                        walkinForm.is_new_patient ? 'text-white shadow-lg' : 'bg-base-200 text-base-content/50']"
                    :style="walkinForm.is_new_patient ? { backgroundColor: primaryColor } : {}"
                >
                    New Patient
                </button>
                <button 
                    @click="walkinForm.is_new_patient = false"
                    :class="['flex-1 py-3 rounded-xl text-xs font-black uppercase tracking-widest transition-all duration-300',
                        !walkinForm.is_new_patient ? 'text-white shadow-lg' : 'bg-base-200 text-base-content/50']"
                    :style="!walkinForm.is_new_patient ? { backgroundColor: primaryColor } : {}"
                >
                    Existing Patient
                </button>
            </div>

            <!-- New Patient Fields -->
            <template v-if="walkinForm.is_new_patient">
                <div class="form-control">
                    <label class="label"><span class="label-text text-[10px] font-black uppercase tracking-widest text-base-content/40">Patient Name</span></label>
                    <input v-model="walkinForm.patient_name" type="text" placeholder="Full name" class="input input-bordered rounded-xl bg-base-200/50 focus:border-primary" />
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text text-[10px] font-black uppercase tracking-widest text-base-content/40">Phone Number</span></label>
                    <input v-model="walkinForm.phone" type="tel" placeholder="09XX XXX XXXX" class="input input-bordered rounded-xl bg-base-200/50 focus:border-primary" />
                </div>
            </template>

            <!-- Existing Patient Selector -->
            <template v-else>
                <div class="form-control">
                    <label class="label"><span class="label-text text-[10px] font-black uppercase tracking-widest text-base-content/40">Select Patient</span></label>
                    <select v-model="walkinForm.existing_patient_id" class="select select-bordered rounded-xl bg-base-200/50">
                        <option value="" disabled>Choose a patient...</option>
                        <option v-for="patient in patientOptions" :key="patient.id" :value="patient.id">
                            {{ patient.label }}
                        </option>
                    </select>
                </div>
            </template>

            <!-- Common Fields -->
            <div class="form-control">
                <label class="label"><span class="label-text text-[10px] font-black uppercase tracking-widest text-base-content/40">Service / Reason</span></label>
                <input v-model="walkinForm.service" type="text" placeholder="e.g., Cleaning, Extraction, Checkup" class="input input-bordered rounded-xl bg-base-200/50 focus:border-primary" />
            </div>

            <div class="form-control">
                <label class="label"><span class="label-text text-[10px] font-black uppercase tracking-widest text-base-content/40">Assign Dentist</span></label>
                <select v-model="walkinForm.dentist_id" class="select select-bordered rounded-xl bg-base-200/50">
                    <option value="" disabled>Select dentist...</option>
                    <option v-for="dentist in dentists" :key="dentist.id" :value="dentist.id">
                        {{ dentist.name }}
                    </option>
                </select>
            </div>

            <div class="form-control">
                <label class="label"><span class="label-text text-[10px] font-black uppercase tracking-widest text-base-content/40">Notes (optional)</span></label>
                <textarea v-model="walkinForm.notes" class="textarea textarea-bordered rounded-xl bg-base-200/50 focus:border-primary" rows="3" placeholder="Any special notes..."></textarea>
            </div>

            <!-- Submit -->
            <button 
                @click="handleSubmit"
                :disabled="isSubmitting"
                class="w-full py-3.5 rounded-xl text-white text-xs font-black uppercase tracking-widest shadow-lg hover:shadow-xl hover:scale-[1.01] transition-all duration-300 disabled:opacity-50"
                :style="{ backgroundColor: primaryColor }"
            >
                {{ isSubmitting ? 'Registering...' : 'Register Walk-in' }}
            </button>
        </div>
    </div>
</template>
