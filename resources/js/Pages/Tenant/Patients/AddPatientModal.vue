<script setup>
import { brandingState } from '@/States/brandingState';
import { useForm, usePage } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { VueDatePicker } from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';

const props = defineProps({
    show: Boolean,
    patient: {
        type: Object,
        default: null
    }
});

const emit = defineEmits(['close']);

const primaryColor = computed(() => brandingState.primary_color);

const form = useForm({
    first_name: '',
    last_name: '',
    phone: '',
    email: '',
    date_of_birth: '',
    gender: '',
    address: '',
    medical_history: '',
    operation_history: '',
    initial_balance: 0,
    last_visit_time: '',
    photo: null,
});

// Reset form when modal opens
watch(() => props.show, (newVal) => {
    if (newVal) {
        if (props.patient) {
            form.first_name = props.patient.first_name || '';
            form.last_name = props.patient.last_name || '';
            form.phone = props.patient.phone || '';
            form.email = props.patient.email || '';
            form.date_of_birth = props.patient.date_of_birth 
                ? new Date(props.patient.date_of_birth).toISOString().split('T')[0] 
                : '';
            form.gender = props.patient.gender || '';
            form.address = props.patient.address || '';
            form.medical_history = props.patient.medical_history || '';
            form.operation_history = props.patient.operation_history || '';
            form.initial_balance = props.patient.initial_balance || 0;
            form.last_visit_time = props.patient.last_visit_time || '';
            form.photo = null;
            
            photoPreview.value = props.patient.photo_url || null;
        } else {
            form.reset();
            photoPreview.value = null;
        }
        form.clearErrors();
    }
});

const close = () => {
    emit('close');
};

const photoPreview = ref(null);

const handlePhotoUpload = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.photo = file;
        photoPreview.value = URL.createObjectURL(file);
    }
};

const submit = () => {
    if (props.patient) {
        // Handle multipart data under Laravel PUT by simulating POST with _method
        form.transform((data) => ({
            ...data,
            _method: 'put',
        })).post(`/patients/${props.patient.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                close();
            }
        });
    } else {
        form.transform((data) => data).post('/patients', {
            preserveScroll: true,
            onSuccess: () => {
                close();
            }
        });
    }
};
</script>

<template>
    <dialog class="modal" :class="{ 'modal-open': show }">
        <div class="modal-box w-11/12 max-w-4xl p-0 overflow-hidden bg-base-200 flex flex-col max-h-[90vh]">
            <!-- Header -->
            <div class="flex items-center justify-between p-6 bg-base-100 border-b border-base-300 shrink-0">
                <h3 class="text-xl font-black text-base-content tracking-tight">{{ patient ? 'Update Patient Record' : 'Add New Patient' }}</h3>
                <button @click="close" class="btn btn-sm btn-circle btn-ghost">✕</button>
            </div>

            <form @submit.prevent="submit" class="flex flex-col flex-1 min-h-0 overflow-hidden">
                <!-- Scrollable Content Area -->
                <div class="p-8 overflow-y-auto custom-scrollbar space-y-8 flex-1 min-h-0">
                    
                    <!-- Profile Block -->
                    <div class="flex flex-col md:flex-row gap-8">
                        <!-- Photo Upload -->
                        <div class="shrink-0 flex flex-col items-center">
                            <div class="w-32 h-32 rounded-full overflow-hidden bg-base-200 ring-4 ring-base-100 ring-offset-2 ring-offset-base-300 mb-4 shadow-sm relative group cursor-pointer">
                                <img v-if="photoPreview" :src="photoPreview" class="w-full h-full object-cover" />
                                <div v-else class="w-full h-full flex flex-col items-center justify-center text-base-content/40 bg-base-200">
                                    <svg class="w-8 h-8 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                    <span class="text-[10px] uppercase font-black tracking-widest">Upload</span>
                                </div>
                                
                                <!-- Hover overlay -->
                                <div class="absolute inset-0 bg-base-content/50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                    <svg class="w-8 h-8 text-base-100" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                </div>

                                <input type="file" @change="handlePhotoUpload" accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer w-full h-full z-10" />
                            </div>
                            <p v-if="form.errors.photo" class="text-error text-[10px] font-black uppercase tracking-widest mt-1">{{ form.errors.photo }}</p>
                            <p v-else class="text-[10px] font-bold text-base-content/40 uppercase tracking-widest text-center mt-1">Profile Photo<br><span class="text-error">* Required</span></p>
                        </div>

                        <!-- Core Info -->
                        <div class="flex-1 space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="form-control">
                                    <label class="label"><span class="label-text text-xs font-black uppercase tracking-widest text-base-content/50">First Name <span class="text-error">*</span></span></label>
                                    <input v-model="form.first_name" type="text" placeholder="e.g. John" class="input input-bordered rounded-xl bg-base-100" />
                                    <span v-if="form.errors.first_name" class="text-error text-[10px] font-black uppercase mt-1 px-1">{{ form.errors.first_name }}</span>
                                </div>
                                <div class="form-control">
                                    <label class="label"><span class="label-text text-xs font-black uppercase tracking-widest text-base-content/50">Last Name <span class="text-error">*</span></span></label>
                                    <input v-model="form.last_name" type="text" placeholder="e.g. Doe" class="input input-bordered rounded-xl bg-base-100" />
                                    <span v-if="form.errors.last_name" class="text-error text-[10px] font-black uppercase mt-1 px-1">{{ form.errors.last_name }}</span>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="form-control">
                                    <label class="label"><span class="label-text text-xs font-black uppercase tracking-widest text-base-content/50">Phone Number</span></label>
                                    <input v-model="form.phone" type="tel" placeholder="e.g. 09123456789" class="input input-bordered rounded-xl bg-base-100" />
                                    <span v-if="form.errors.phone" class="text-error text-[10px] font-black uppercase mt-1 px-1">{{ form.errors.phone }}</span>
                                </div>
                                <div class="form-control">
                                    <label class="label"><span class="label-text text-xs font-black uppercase tracking-widest text-base-content/50">Email Address</span></label>
                                    <input v-model="form.email" type="email" placeholder="john@example.com" class="input input-bordered rounded-xl bg-base-100" />
                                    <span v-if="form.errors.email" class="text-error text-[10px] font-black uppercase mt-1 px-1">{{ form.errors.email }}</span>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 border-t border-base-300 pt-4 mt-4">
                                <div class="form-control" style="--dp-input-padding: 0px 1rem; --dp-border-radius: 0.75rem; --dp-border-color: transparent;">
                                    <label class="label"><span class="label-text text-xs font-black uppercase tracking-widest text-base-content/50">Date of Birth</span></label>
                                    <VueDatePicker 
                                        v-model="form.date_of_birth" 
                                        :enable-time-picker="false" 
                                        auto-apply
                                        format="yyyy-MM-dd"
                                        value-format="yyyy-MM-dd"
                                        placeholder="Select Date" 
                                        input-class-name="input input-bordered w-full rounded-xl bg-base-100 placeholder:text-base-content/50"
                                    />
                                </div>
                                <div class="form-control">
                                    <label class="label"><span class="label-text text-xs font-black uppercase tracking-widest text-base-content/50">Gender</span></label>
                                    <select v-model="form.gender" class="select select-bordered rounded-xl bg-base-100">
                                        <option value="" disabled>Select</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                <div class="form-control sm:col-span-2 lg:col-span-1">
                                    <label class="label"><span class="label-text text-xs font-black uppercase tracking-widest text-base-content/50">Initial Balance</span></label>
                                    <div class="relative">
                                        <span class="absolute left-4 top-1/2 -translate-y-1/2 font-bold text-base-content/40">₱</span>
                                        <input v-model.number="form.initial_balance" type="number" step="0.01" min="0" class="input input-bordered w-full pl-8 rounded-xl bg-base-100" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Expanded Info -->
                    <div class="space-y-4 border-t border-base-300 pt-6">
                        <div class="form-control">
                            <label class="label"><span class="label-text text-xs font-black uppercase tracking-widest text-base-content/50">Home Address</span></label>
                            <input v-model="form.address" type="text" placeholder="Full residential address" class="input input-bordered rounded-xl bg-base-100" />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-control">
                                <label class="label"><span class="label-text text-xs font-black uppercase tracking-widest text-base-content/50">Medical History</span></label>
                                <textarea v-model="form.medical_history" class="textarea textarea-bordered h-24 rounded-xl bg-base-100 resize-none text-sm" placeholder="List existing conditions, allergies, or regular medications..."></textarea>
                            </div>
                            <div class="form-control">
                                <label class="label"><span class="label-text text-xs font-black uppercase tracking-widest text-base-content/50">Operation History</span></label>
                                <textarea v-model="form.operation_history" class="textarea textarea-bordered h-24 rounded-xl bg-base-100 resize-none text-sm" placeholder="List past dental or related operations..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Fixed Footer Actions -->
                <div class="p-6 bg-base-100 border-t border-base-300 flex justify-end gap-3 shrink-0">
                    <button type="button" @click="close" class="btn btn-ghost rounded-xl text-xs font-black uppercase tracking-widest">Cancel</button>
                    <button 
                        type="submit" 
                        :disabled="form.processing"
                        class="btn border-0 text-white rounded-xl shadow-lg hover:shadow-xl hover:scale-[1.02] transition-all text-xs font-black uppercase tracking-widest disabled:opacity-50"
                        :style="{ backgroundColor: primaryColor }"
                    >
                        <span v-if="form.processing" class="loading loading-spinner loading-xs"></span>
                        {{ patient ? 'Save Changes' : 'Save Patient Record' }}
                    </button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button @click="close">close</button>
        </form>
    </dialog>
</template>
