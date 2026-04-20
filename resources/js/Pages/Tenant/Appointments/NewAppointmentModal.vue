<script setup>
import { ref, computed } from 'vue';
import { useForm, router, usePage } from '@inertiajs/vue3';
import { brandingState } from '@/States/brandingState';
import Modal from '@/Components/Modal.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    show: Boolean,
    patients: {
        type: Array,
        default: () => []
    },
    dentists: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['close']);
const page = usePage();
const tenantLimits = computed(() => page.props.tenant_plan?.limits || {});
const tenantUsage = computed(() => page.props.tenant_plan?.current_usage || {});
const primaryColor = computed(() => brandingState.primary_color || '#009b9b');
const focusStyle = computed(() => ({ '--tw-ring-color': primaryColor.value }));

const form = useForm({
    patient_id: '',
    dentist_id: '',
    appointment_date: '',
    duration: '30',
    notes: '',
    status: 'scheduled',
});

const patientSearch = ref('');
const filteredPatients = computed(() => {
    if (!patientSearch.value) return [];
    return props.patients.filter(p => 
        (p.first_name + ' ' + p.last_name).toLowerCase().includes(patientSearch.value.toLowerCase())
    ).slice(0, 5);
});

const selectPatient = (patient) => {
    form.patient_id = patient.id;
    patientSearch.value = `${patient.first_name} ${patient.last_name}`;
};

const submit = () => {
    const createAppointment = () => form.post('/appointments', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            patientSearch.value = '';
            emit('close');
        },
    });

    const maxAppointments = tenantLimits.value.max_appointments;
    const currentAppointments = tenantUsage.value.appointments || 0;

    if (maxAppointments !== null && maxAppointments !== undefined && maxAppointments !== -1 && currentAppointments >= maxAppointments) {
        Swal.fire({
            icon: 'warning',
            title: 'Appointment Limit Reached',
            html: `You reached your appointment limit (${maxAppointments}).<br><br>Continue anyway and add overage to next renewal bill?`,
            showCancelButton: true,
            confirmButtonText: 'Continue and Bill Next Renewal',
            cancelButtonText: 'Cancel',
        }).then((result) => {
            if (!result.isConfirmed) {
                return;
            }

            router.post(route('overage.consent.grant'), {
                metric: 'appointments',
            }, {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => createAppointment(),
            });
        });

        return;
    }

    createAppointment();
};

const closeModal = () => {
    emit('close');
};
</script>

<template>
    <Modal :show="show" @close="closeModal" max-width="md">
        <div class="p-5 sm:p-6">
            <div class="flex justify-between items-center pb-3 mb-4 border-b border-base-300">
                <h2 class="text-xl font-black text-base-content">New Appointment</h2>
                <button @click="closeModal" class="text-base-content/40 hover:text-base-content transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form @submit.prevent="submit" class="space-y-4">
                <!-- Patient Selection -->
                <div class="relative">
                    <label class="block text-[10px] font-black text-base-content/40 uppercase tracking-[0.2em] mb-1">Patient</label>
                    <div class="flex gap-2 items-stretch">
                        <div class="relative flex-1">
                            <input 
                                v-model="patientSearch"
                                type="text" 
                                placeholder="Search Patient Here" 
                                class="w-full px-3.5 py-2.5 bg-base-100 border border-base-300 rounded-xl text-sm text-base-content focus:ring-2 focus:border-transparent transition"
                                :style="focusStyle"
                            >
                            <div v-if="filteredPatients.length > 0 && !form.patient_id" class="absolute z-10 w-full mt-1 bg-base-100 border border-base-300 rounded-xl shadow-xl overflow-hidden">
                                <button 
                                    v-for="p in filteredPatients" 
                                    :key="p.id"
                                    type="button"
                                    @click="selectPatient(p)"
                                    class="w-full px-4 py-2.5 text-left text-sm text-base-content hover:bg-base-200/70 border-b border-base-300/60 last:border-0 transition"
                                >
                                    {{ p.first_name }} {{ p.last_name }}
                                </button>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="router.visit('/patients/create')"
                            class="text-white px-3 py-2.5 rounded-xl text-[11px] font-black uppercase tracking-wider flex items-center gap-1 whitespace-nowrap shadow-sm"
                            :style="{ backgroundColor: primaryColor }"
                        >
                            <span>+</span> New Patient
                        </button>
                    </div>
                    <p v-if="form.errors.patient_id" class="mt-1 text-xs text-red-500">This is required</p>
                </div>

                <!-- Associate / Dentist -->
                <div>
                    <label class="block text-[10px] font-black text-base-content/40 uppercase tracking-[0.2em] mb-1">Associate</label>
                    <select v-model="form.dentist_id" class="w-full px-3.5 py-2.5 bg-base-100 border border-base-300 rounded-xl text-sm text-base-content focus:ring-2 focus:border-transparent transition" :style="focusStyle">
                        <option value="">Select Associate</option>
                        <option v-for="d in dentists" :key="d.id" :value="d.id">{{ d.name }}</option>
                    </select>
                </div>

                <!-- Date & Time -->
                <div>
                    <label class="block text-[10px] font-black text-base-content/40 uppercase tracking-[0.2em] mb-1">Appointment Date and Time</label>
                    <input v-model="form.appointment_date" type="datetime-local" class="w-full px-3.5 py-2.5 bg-base-100 border border-base-300 rounded-xl text-sm text-base-content focus:ring-2 focus:border-transparent transition" :style="focusStyle">
                    <p v-if="form.errors.appointment_date" class="mt-1 text-xs text-red-500">This is required</p>
                </div>

                <!-- Duration -->
                <div>
                    <label class="block text-[10px] font-black text-base-content/40 uppercase tracking-[0.2em] mb-1">Estimated Duration</label>
                    <select v-model="form.duration" class="w-full px-3.5 py-2.5 bg-base-100 border border-base-300 rounded-xl text-sm text-base-content focus:ring-2 focus:border-transparent transition" :style="focusStyle">
                        <option value="30">30 minutes</option>
                        <option value="60">1 hour</option>
                        <option value="90">1.5 hours</option>
                        <option value="120">2 hours</option>
                    </select>
                </div>

                <!-- Reason / Notes -->
                <div>
                    <label class="block text-[10px] font-black text-base-content/40 uppercase tracking-[0.2em] mb-1">Reason</label>
                    <textarea v-model="form.notes" rows="2" class="w-full px-3.5 py-2.5 bg-base-100 border border-base-300 rounded-xl text-sm text-base-content focus:ring-2 focus:border-transparent transition" :style="focusStyle"></textarea>
                    <p v-if="form.errors.notes" class="mt-1 text-xs text-red-500">This is required</p>
                </div>

                <div class="flex justify-end gap-2.5 mt-5 pt-2 border-t border-base-300">
                    <button type="button" @click="closeModal" class="min-w-28 px-5 py-2.5 bg-red-100 text-red-600 rounded-xl text-sm font-black uppercase tracking-wider hover:bg-red-200 transition flex items-center justify-center">
                        Cancel
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="min-w-24 px-5 py-2.5 text-white rounded-xl text-sm font-black uppercase tracking-wider transition flex items-center justify-center disabled:opacity-50"
                        :style="{ backgroundColor: primaryColor }"
                    >
                        Save
                    </button>
                </div>
            </form>
        </div>
    </Modal>
</template>
