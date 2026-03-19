<script setup>
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';

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
    form.post('/appointments', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            patientSearch.value = '';
            emit('close');
        },
    });
};

const closeModal = () => {
    emit('close');
};
</script>

<template>
    <Modal :show="show" @close="closeModal" max-width="lg">
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">New Appointment</h2>
                <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form @submit.prevent="submit" class="space-y-5">
                <!-- Patient Selection -->
                <div class="relative">
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Patient</label>
                    <div class="flex gap-2">
                        <div class="relative flex-1">
                            <input 
                                v-model="patientSearch"
                                type="text" 
                                placeholder="Search Patient Here" 
                                class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500"
                            >
                            <div v-if="filteredPatients.length > 0 && !form.patient_id" class="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-xl overflow-hidden">
                                <button 
                                    v-for="p in filteredPatients" 
                                    :key="p.id"
                                    type="button"
                                    @click="selectPatient(p)"
                                    class="w-full px-4 py-2 text-left text-sm hover:bg-blue-50 border-b border-gray-50 last:border-0"
                                >
                                    {{ p.first_name }} {{ p.last_name }}
                                </button>
                            </div>
                        </div>
                        <button type="button" @click="router.visit('/patients/create')" class="bg-[#009b9b] text-white px-3 py-2 rounded-lg text-sm font-bold flex items-center gap-1 whitespace-nowrap">
                            <span>+</span> New Patient
                        </button>
                    </div>
                    <p v-if="form.errors.patient_id" class="mt-1 text-xs text-red-500">This is required</p>
                </div>

                <!-- Associate / Dentist -->
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Associate</label>
                    <select v-model="form.dentist_id" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Select Associate</option>
                        <option v-for="d in dentists" :key="d.id" :value="d.id">{{ d.name }}</option>
                    </select>
                </div>

                <!-- Date & Time -->
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Appointment Date and Time</label>
                    <input v-model="form.appointment_date" type="datetime-local" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                    <p v-if="form.errors.appointment_date" class="mt-1 text-xs text-red-500">This is required</p>
                </div>

                <!-- Duration -->
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Estimated Duration</label>
                    <select v-model="form.duration" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="30">30 minutes</option>
                        <option value="60">1 hour</option>
                        <option value="90">1.5 hours</option>
                        <option value="120">2 hours</option>
                    </select>
                </div>

                <!-- Reason / Notes -->
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Reason</label>
                    <textarea v-model="form.notes" rows="3" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500"></textarea>
                    <p v-if="form.errors.notes" class="mt-1 text-xs text-red-500">This is required</p>
                </div>

                <div class="flex justify-end gap-3 mt-8">
                    <button type="button" @click="closeModal" class="px-6 py-2 bg-orange-500 text-white rounded-lg font-bold hover:bg-orange-600 transition flex items-center gap-2">
                        <span>✖</span> Cancel
                    </button>
                    <button type="submit" :disabled="form.processing" class="px-6 py-2 bg-teal-600 text-white rounded-lg font-bold hover:bg-teal-700 transition flex items-center gap-2 disabled:opacity-50">
                        <span>✔</span> Save
                    </button>
                </div>
            </form>
        </div>
    </Modal>
</template>
