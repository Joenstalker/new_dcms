<script setup>
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    dentists: {
        type: Array,
        default: () => []
    }
});

const form = useForm({
    guest_first_name: '',
    guest_last_name: '',
    guest_phone: '',
    guest_email: '',
    appointment_date: '',
    service: '',
    notes: '',
    dentist_id: '',
    photo: null,
});

const selectedDentist = computed(() => {
    return props.dentists.find(d => d.id == form.dentist_id);
});

const handlePhotoUpload = (e) => {
    form.photo = e.target.files[0];
};

const submit = () => {
    form.post('/book', {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <div class="min-h-screen bg-gray-100 flex flex-col items-center justify-center p-4">
        <div class="w-full max-w-md bg-white rounded-lg shadow-md p-8">
            <h2 class="text-2xl font-bold text-center text-blue-600 mb-6 font-sans">Book an Appointment</h2>
            
            <div v-if="$page.props.flash && $page.props.flash.success" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4" role="alert">
                <span class="block sm:inline">{{ $page.props.flash.success }}</span>
            </div>

            <form @submit.prevent="submit" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="guest_first_name" class="block text-sm font-medium text-gray-700">First Name</label>
                        <input type="text" id="guest_first_name" v-model="form.guest_first_name" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <p v-if="form.errors.guest_first_name" class="mt-2 text-sm text-red-600">{{ form.errors.guest_first_name }}</p>
                    </div>

                    <div>
                        <label for="guest_last_name" class="block text-sm font-medium text-gray-700">Last Name</label>
                        <input type="text" id="guest_last_name" v-model="form.guest_last_name" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <p v-if="form.errors.guest_last_name" class="mt-2 text-sm text-red-600">{{ form.errors.guest_last_name }}</p>
                    </div>
                </div>

                <div>
                    <label for="guest_phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
                    <input type="text" id="guest_phone" v-model="form.guest_phone" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <p v-if="form.errors.guest_phone" class="mt-2 text-sm text-red-600">{{ form.errors.guest_phone }}</p>
                </div>

                <div>
                    <label for="guest_email" class="block text-sm font-medium text-gray-700">Email (Optional)</label>
                    <input type="email" id="guest_email" v-model="form.guest_email" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>

                <div>
                    <label for="photo" class="block text-sm font-medium text-gray-700">Your Photo (Optional)</label>
                    <input type="file" id="photo" @change="handlePhotoUpload" accept="image/*" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    <p class="mt-1 text-xs text-gray-500">This helps us recognize you when you arrive.</p>
                </div>
                
                <hr class="my-4">

                <div>
                    <label for="dentist" class="block text-sm font-medium text-gray-700">Choose Dentist</label>
                    <select id="dentist" v-model="form.dentist_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="">Any Available Dentist</option>
                        <option v-for="dentist in dentists" :key="dentist.id" :value="dentist.id">{{ dentist.name }}</option>
                    </select>
                </div>

                <div v-if="selectedDentist" class="p-3 bg-blue-50 rounded-md border border-blue-100 flex items-center space-x-3 transition-all">
                    <div class="w-12 h-12 bg-blue-200 rounded-full flex items-center justify-center text-blue-600 font-bold text-lg">
                        {{ selectedDentist.name[0] }}
                    </div>
                    <div>
                        <p class="text-sm font-bold text-blue-800">{{ selectedDentist.name }}</p>
                        <p class="text-xs text-blue-600">Professional Dentist</p>
                    </div>
                </div>

                <div>
                    <label for="service" class="block text-sm font-medium text-gray-700">Service Needed</label>
                    <select id="service" v-model="form.service" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="">-- Select Service --</option>
                        <option value="Checkup">General Checkup</option>
                        <option value="Cleaning">Teeth Cleaning</option>
                        <option value="Extraction">Extraction</option>
                        <option value="Whitening">Whitening</option>
                        <option value="Other">Other / Not Sure</option>
                    </select>
                </div>

                <div>
                    <label for="appointment_date" class="block text-sm font-medium text-gray-700">Preferred Date & Time</label>
                    <input type="datetime-local" id="appointment_date" v-model="form.appointment_date" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>

                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700">Additional Notes (Optional)</label>
                    <textarea id="notes" v-model="form.notes" rows="2" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"></textarea>
                </div>

                <div class="pt-2">
                    <button type="submit" :disabled="form.processing" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-md text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all disabled:opacity-50">
                        {{ form.processing ? 'Booking...' : 'Confirm Appointment' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
