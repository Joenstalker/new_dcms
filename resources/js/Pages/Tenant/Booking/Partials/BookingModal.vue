<script setup>
import { ref, computed, watch, onUnmounted } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    show: Boolean,
    tenant: Object,
    services: Array,
    dentists: Array,
});

const emit = defineEmits(['close']);

const step = ref(1);
const showSuccess = ref(false);
const bookingReference = ref('');

const form = useForm({
    guest_first_name: '',
    guest_last_name: '',
    guest_phone: '',
    guest_email: '',
    guest_address: '',
    guest_medical_history: [],
    current_medications: '',
    appointment_date: '',
    appointment_time: '',
    service: '',
    dentist_id: '',
    notes: '',
    photo: null,
});

const photoPreview = ref(null);
const handlePhotoUpload = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.photo = file;
        photoPreview.value = URL.createObjectURL(file);
    }
};

const brandingColor = computed(() => props.tenant?.branding_color || '#3b82f6');
const logoUrl = computed(() => {
    const logopath = props.tenant?.logo_booking_path || props.tenant?.logo_path;
    return logopath ? `/storage/${logopath}` : null;
});
const fontFamily = computed(() => props.tenant?.font_family || 'font-sans');

// Prevent background scrolling when modal is open
watch(() => props.show, (newVal) => {
    if (newVal) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
    }
}, { immediate: true });

// Cleanup on component unmount
onUnmounted(() => {
    document.body.style.overflow = '';
});

const steps = [
    { id: 1, name: 'Patient Info' },
    { id: 2, name: 'Medical History' },
    { id: 3, name: 'Schedule' },
    { id: 4, name: 'Dentist' },
    { id: 5, name: 'Review' },
];

const nextStep = () => {
    if (step.value < 5) step.value++;
};

const prevStep = () => {
    if (step.value > 1) step.value--;
};

const submit = () => {
    // Combine date and time
    const fullDateTime = `${form.appointment_date} ${form.appointment_time}`;
    
    form.transform((data) => ({
        ...data,
        appointment_date: fullDateTime,
        guest_medical_history: [
            ...data.guest_medical_history,
            ...(data.current_medications ? [`Medications: ${data.current_medications}`] : [])
        ]
    })).post(route('tenant.book.store'), {
        preserveScroll: true,
        onSuccess: (page) => {
            bookingReference.value = page.props.flash.booking_reference;
            showSuccess.value = true;
        },
    });
};

const close = () => {
    emit('close');
    // Reset after animation
    setTimeout(() => {
        step.value = 1;
        showSuccess.value = false;
        form.reset();
    }, 300);
};

const selectedDentist = computed(() => {
    return props.dentists.find(d => d.id === form.dentist_id);
});

const selectedService = computed(() => {
    return props.services.find(s => s.name === form.service);
});

const medicalOptions = [
    'Diabetes',
    'Heart Condition',
    'Asthma',
    'Allergies',
    'Hypertension',
    'Epilepsy',
];

const timeSlots = [
    '09:00 AM', '10:00 AM', '11:00 AM',
    '01:00 PM', '02:00 PM', '03:00 PM', '04:00 PM'
];

</script>

<template>
    <div v-if="show" class="fixed inset-0 z-[100] overflow-y-auto" :class="fontFamily" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="close"></div>

        <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-[2.5rem] bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-2xl animate-in fade-in zoom-in duration-300">
                
                <!-- Success State -->
                <div v-if="showSuccess" class="p-8 lg:p-12 text-center">
                    <div class="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-8 animate-bounce">
                        <span class="text-5xl">✅</span>
                    </div>
                    <h2 class="text-3xl font-black text-gray-900 mb-4">Booking Successful!</h2>
                    <p class="text-gray-600 mb-8 max-w-sm mx-auto">
                        Your appointment has been scheduled. Please save your reference ID for check-in.
                    </p>
                    
                    <div class="bg-gray-50 p-6 rounded-3xl border-2 border-dashed border-gray-200 mb-10 inline-block px-12">
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Reference ID</p>
                        <p class="text-3xl font-black font-mono tracking-tighter" :style="{ color: brandingColor }">
                            {{ bookingReference }}
                        </p>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <button @click="close" class="px-8 py-4 bg-gray-900 text-white font-bold rounded-full hover:bg-black transition-all">
                            Done
                        </button>
                        <button @click="window.print()" class="px-8 py-4 border-2 border-gray-200 font-bold rounded-full hover:bg-gray-50 transition-all flex items-center justify-center gap-2">
                            <span>📄</span> Print Receipt
                        </button>
                    </div>
                </div>

                <!-- Form Steps -->
                <div v-else>
                    <!-- Header -->
                    <div class="px-8 pt-8 pb-4 flex justify-between items-start">
                        <div>
                            <!-- Logo -->
                            <div v-if="logoUrl" class="mb-4">
                                <img :src="logoUrl" :alt="tenant?.name" class="h-12 object-contain mx-auto lg:mx-0">
                            </div>
                            <h3 class="text-2xl font-black text-gray-900" id="modal-title">
                                Book Appointment
                            </h3>
                            <p class="text-sm text-gray-500 font-medium">Step {{ step }} of 5: {{ steps[step-1].name }}</p>
                        </div>
                        <button @click="close" class="p-2 hover:bg-gray-100 rounded-full transition-colors text-gray-400">
                            <span class="text-2xl">✕</span>
                        </button>
                    </div>

                    <!-- Progress Bar -->
                    <div class="px-8 mb-8">
                        <div class="h-2 w-full bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full transition-all duration-500 ease-out" 
                                 :style="{ width: (step / 5 * 100) + '%', backgroundColor: brandingColor }">
                            </div>
                        </div>
                    </div>

                    <!-- Step Content -->
                    <div class="px-8 pb-8 min-h-[400px]">
                        
                        <!-- Step 1: Patient Info -->
                        <div v-if="step === 1" class="space-y-6 animate-in slide-in-from-right-4 duration-300">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">First Name</label>
                                    <input type="text" v-model="form.guest_first_name" required class="w-full bg-gray-50 border-none rounded-2xl focus:ring-2 p-4 transition-all" :style="{ '--tw-ring-color': brandingColor }" placeholder="John">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Last Name</label>
                                    <input type="text" v-model="form.guest_last_name" required class="w-full bg-gray-50 border-none rounded-2xl focus:ring-2 p-4 transition-all" :style="{ '--tw-ring-color': brandingColor }" placeholder="Doe">
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Email Address</label>
                                    <input type="email" v-model="form.guest_email" class="w-full bg-gray-50 border-none rounded-2xl focus:ring-2 p-4 transition-all" :style="{ '--tw-ring-color': brandingColor }" placeholder="john@example.com">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Phone Number</label>
                                    <input type="text" v-model="form.guest_phone" required class="w-full bg-gray-50 border-none rounded-2xl focus:ring-2 p-4 transition-all" :style="{ '--tw-ring-color': brandingColor }" placeholder="09XX XXX XXXX">
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 items-end">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Facial Photo (Optional)</label>
                                    <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-2xl border-2 border-dashed border-gray-200">
                                        <div class="w-12 h-12 bg-white rounded-xl flex items-center justify-center overflow-hidden shadow-sm">
                                            <img v-if="photoPreview" :src="photoPreview" class="w-full h-full object-cover">
                                            <span v-else class="text-xl">📸</span>
                                        </div>
                                        <input type="file" @change="handlePhotoUpload" class="hidden" id="photo-upload" accept="image/*">
                                        <label for="photo-upload" class="px-4 py-2 bg-white border border-gray-200 rounded-xl text-[10px] font-black uppercase tracking-wider cursor-pointer hover:bg-gray-50 transition-all">
                                            Upload
                                        </label>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Home Address (Optional)</label>
                                    <textarea v-model="form.guest_address" rows="1" class="w-full bg-gray-50 border-none rounded-2xl focus:ring-2 p-4 transition-all" :style="{ '--tw-ring-color': brandingColor }" placeholder="Street, City..."></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Medical History -->
                        <div v-if="step === 2" class="space-y-6 animate-in slide-in-from-right-4 duration-300">
                            <p class="text-sm text-gray-500 mb-4">Please check any conditions that apply to you. This helps us provide the safest care.</p>
                            <div class="grid grid-cols-2 gap-4">
                                <label v-for="option in medicalOptions" :key="option" 
                                       class="flex items-center p-4 rounded-2xl border-2 transition-all cursor-pointer group"
                                       :class="form.guest_medical_history.includes(option) ? 'border-blue-500 bg-blue-50' : 'border-gray-50 bg-gray-50 hover:bg-gray-100'">
                                    <input type="checkbox" :value="option" v-model="form.guest_medical_history" class="hidden">
                                    <span class="w-6 h-6 rounded-lg border-2 flex items-center justify-center mr-3 transition-all"
                                          :style="form.guest_medical_history.includes(option) ? { backgroundColor: brandingColor, borderColor: brandingColor } : { borderColor: '#d1d5db' }">
                                        <span v-if="form.guest_medical_history.includes(option)" class="text-white text-xs font-bold">✓</span>
                                    </span>
                                    <span class="font-bold text-sm text-gray-700" :class="form.guest_medical_history.includes(option) ? 'text-blue-900' : ''">{{ option }}</span>
                                </label>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Current Medications (If any)</label>
                                <textarea v-model="form.current_medications" rows="3" class="w-full bg-gray-50 border-none rounded-2xl focus:ring-2 p-4 transition-all" :style="{ '--tw-ring-color': brandingColor }" placeholder="List any medications you are currently taking..."></textarea>
                            </div>
                        </div>

                        <!-- Step 3: Schedule -->
                        <div v-if="step === 3" class="space-y-6 animate-in slide-in-from-right-4 duration-300">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-4">Select Preferred Date</label>
                                <input type="date" v-model="form.appointment_date" class="w-full bg-gray-50 border-none rounded-2xl focus:ring-2 p-6 text-xl font-black text-center" :style="{ '--tw-ring-color': brandingColor }">
                            </div>
                            
                            <div v-if="form.appointment_date">
                                <label class="block text-sm font-bold text-gray-700 mb-4">Available Time Slots</label>
                                <div class="grid grid-cols-3 gap-3">
                                    <button v-for="time in timeSlots" :key="time" 
                                            @click="form.appointment_time = time"
                                            class="p-4 rounded-2xl border-2 font-bold text-sm transition-all shadow-sm"
                                            :class="form.appointment_time === time ? 'border-transparent text-white scale-105' : 'border-gray-50 bg-gray-50 text-gray-700 hover:bg-gray-100'"
                                            :style="form.appointment_time === time ? { backgroundColor: brandingColor } : {}">
                                        {{ time }}
                                    </button>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Service Needed</label>
                                <select v-model="form.service" class="w-full bg-gray-50 border-none rounded-2xl focus:ring-2 p-4 transition-all font-bold" :style="{ '--tw-ring-color': brandingColor }">
                                    <option value="">Select a service...</option>
                                    <option v-for="s in services" :key="s.id" :value="s.name">{{ s.name }} - ₱{{ s.price }}</option>
                                </select>
                            </div>
                        </div>

                        <!-- Step 4: Dentist Selection -->
                        <div v-if="step === 4" class="space-y-6 animate-in slide-in-from-right-4 duration-300">
                            <p class="text-sm text-gray-500 mb-4">Choose your preferred dentist or select "Any Available".</p>
                            <div class="space-y-3">
                                <button @click="form.dentist_id = ''" 
                                        class="w-full p-6 bg-gray-50 rounded-[2rem] border-2 text-left flex items-center justify-between transition-all"
                                        :class="!form.dentist_id ? 'border-blue-500 ring-2' : 'border-transparent hover:bg-gray-100'"
                                        :style="!form.dentist_id ? { '--tw-ring-color': brandingColor } : {}">
                                    <div class="flex items-center gap-4">
                                        <div class="w-14 h-14 bg-gray-200 rounded-2xl flex items-center justify-center text-2xl">👨‍👩‍👧‍👦</div>
                                        <div>
                                            <p class="font-black text-gray-900 leading-tight">Any Available Dentist</p>
                                            <p class="text-xs text-gray-500">Fastest booking option</p>
                                        </div>
                                    </div>
                                    <div v-if="!form.dentist_id" class="w-6 h-6 rounded-full flex items-center justify-center text-white" :style="{ backgroundColor: brandingColor }">✓</div>
                                </button>

                                <button v-for="dentist in dentists" :key="dentist.id" 
                                        @click="form.dentist_id = dentist.id"
                                        class="w-full p-6 bg-gray-50 rounded-[2rem] border-2 text-left flex items-center justify-between transition-all"
                                        :class="form.dentist_id === dentist.id ? 'border-blue-500 ring-2' : 'border-transparent hover:bg-gray-100'"
                                        :style="form.dentist_id === dentist.id ? { '--tw-ring-color': brandingColor } : {}">
                                    <div class="flex items-center gap-4">
                                        <div class="w-14 h-14 bg-gray-200 rounded-2xl flex items-center justify-center text-2xl">🦷</div>
                                        <div>
                                            <p class="font-black text-gray-900 leading-tight">{{ dentist.name }}</p>
                                            <p class="text-xs text-gray-500">Specialist Dentist</p>
                                        </div>
                                    </div>
                                    <div v-if="form.dentist_id === dentist.id" class="w-6 h-6 rounded-full flex items-center justify-center text-white" :style="{ backgroundColor: brandingColor }">✓</div>
                                </button>
                            </div>
                        </div>

                        <!-- Step 5: Review -->
                        <div v-if="step === 5" class="space-y-6 animate-in slide-in-from-right-4 duration-300">
                            <div class="bg-gray-50 p-8 rounded-[2.5rem] space-y-6">
                                <div class="flex justify-between border-b border-gray-200 pb-4">
                                    <div>
                                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none mb-1">Patient</p>
                                        <p class="text-xl font-black text-gray-900 leading-tight">{{ form.guest_first_name }} {{ form.guest_last_name }}</p>
                                        <p class="text-xs text-gray-500">{{ form.guest_phone }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none mb-1">Service</p>
                                        <p class="text-lg font-black" :style="{ color: brandingColor }">{{ form.service || 'General Checkup' }}</p>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-2 gap-8">
                                    <div>
                                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none mb-2 text-center">Date</p>
                                        <div class="bg-white p-4 rounded-3xl text-center shadow-sm">
                                            <p class="text-lg font-black leading-tight">{{ form.appointment_date }}</p>
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none mb-2 text-center">Time</p>
                                        <div class="bg-white p-4 rounded-3xl text-center shadow-sm">
                                            <p class="text-lg font-black leading-tight">{{ form.appointment_time || '--:--' }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 bg-white rounded-2xl flex items-center justify-center text-xl shadow-sm">👨‍⚕️</div>
                                    <div>
                                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none mb-1">Preferred Dentist</p>
                                        <p class="font-black text-gray-900 leading-tight">{{ selectedDentist ? selectedDentist.name : 'Any Available' }}</p>
                                    </div>
                                </div>

                                <div v-if="form.guest_medical_history.length > 0">
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none mb-3">Relevant Conditions</p>
                                    <div class="flex flex-wrap gap-2">
                                        <span v-for="m in form.guest_medical_history" :key="m" class="px-3 py-1 bg-white rounded-full text-[10px] font-bold text-gray-600 shadow-sm border border-gray-100">
                                            {{ m }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <p class="text-xs text-center text-gray-400 leading-relaxed px-4">
                                By clicking confirm, you agree to our terms of service and patient privacy policy.
                            </p>
                        </div>

                    </div>

                    <!-- Footer Actions -->
                    <div class="px-8 py-8 border-t border-gray-50 flex gap-4">
                        <button v-if="step > 1" @click="prevStep" class="px-8 py-4 border-2 border-gray-100 font-bold rounded-full text-gray-500 hover:bg-gray-50 transition-all">
                            Back
                        </button>
                        <button v-if="step < 5" @click="nextStep" 
                                :disabled="step === 1 && (!form.guest_first_name || !form.guest_last_name || !form.guest_phone)"
                                class="flex-1 py-4 text-white font-black text-lg rounded-full shadow-lg hover:shadow-xl transition-all disabled:opacity-50" 
                                :style="{ backgroundColor: brandingColor }">
                            Continue
                        </button>
                        <button v-if="step === 5" @click="submit" :disabled="form.processing"
                                class="flex-1 py-4 text-white font-black text-lg rounded-full shadow-lg hover:shadow-xl transition-all disabled:opacity-50" 
                                :style="{ backgroundColor: brandingColor }">
                            {{ form.processing ? 'Booking...' : 'Confirm Appointment' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.animate-in {
    animation-duration: 0.3s;
}
@keyframes fade-in {
    from { opacity: 0; }
    to { opacity: 1; }
}
@keyframes zoom-in {
    from { transform: scale(0.95); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}
.fade-in { animation-name: fade-in; }
.zoom-in { animation-name: zoom-in; }

@media print {
    .fixed { position: relative !important; }
    .bg-gray-900\/60 { display: none !important; }
    .shadow-2xl { box-shadow: none !important; }
    button { display: none !important; }
}
</style>
