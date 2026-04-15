<script setup>
import { brandingState } from '@/States/brandingState';
import { useForm, usePage, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { VueDatePicker } from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
import Swal from 'sweetalert2';

const props = defineProps({
    show: Boolean,
    patient: {
        type: Object,
        default: null
    }
});

const emit = defineEmits(['close', 'saved']);

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
    patient_type: '',
    tags: [],
    first_visit_at: '',
    last_recall_at: '',
    initial_balance: 0,
    last_visit_time: '',
    photo: null,
});

const tagsText = ref('');

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
            form.patient_type = props.patient.patient_type || '';
            form.first_visit_at = props.patient.first_visit_at || '';
            form.last_recall_at = props.patient.last_recall_at || '';
            form.initial_balance = props.patient.initial_balance || props.patient.balance || 0;
            form.last_visit_time = props.patient.last_visit_time || '';
            form.tags = Array.isArray(props.patient.tags) ? [...props.patient.tags] : [];
            form.photo = null;
            tagsText.value = form.tags.join(', ');
            
            photoPreview.value = props.patient.photo_url || null;
        } else {
            form.reset();
            photoPreview.value = null;
            tagsText.value = '';
        }
        form.clearErrors();
    }
});

const close = () => {
    emit('close');
};

const photoPreview = ref(null);

const sanitizePhoneInput = (value) => String(value ?? '').replace(/\D/g, '').slice(0, 11);

const handlePatientPhoneInput = (event) => {
    form.phone = sanitizePhoneInput(event?.target?.value);
};

const handlePhotoUpload = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.photo = file;
        photoPreview.value = URL.createObjectURL(file);
    }
};

const submit = () => {
    form.phone = sanitizePhoneInput(form.phone);

    form.tags = tagsText.value
        .split(',')
        .map((item) => item.trim())
        .filter((item) => item.length > 0);

    if (props.patient) {
        // Use PUT method for updating existing patient
        form.put(`/patients/${props.patient.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Patient record updated successfully.',
                    timer: 1500,
                    showConfirmButton: false,
                    timerProgressBar: true,
                });
                emit('saved');
                close();
                // Don't reload - let Reverb handle the real-time update
            },
            onError: (errors) => {
                console.error('Validation errors:', errors);
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Failed to update patient record. Please check the form for errors.',
                    timer: 3000,
                    showConfirmButton: true,
                });
            }
        });
    } else {
        // Use POST method for creating new patient
        form.post('/patients', {
            preserveScroll: true,
            onSuccess: () => {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Patient record created successfully.',
                    timer: 1500,
                    showConfirmButton: false,
                    timerProgressBar: true,
                });
                emit('saved');
                close();
                // Don't reload - let Reverb handle the real-time update
            },
            onError: (errors) => {
                console.error('Validation errors:', errors);
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Failed to create patient record. Please check the form for errors.',
                    timer: 3000,
                    showConfirmButton: true,
                });
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
                            <p v-else class="text-[10px] font-bold text-base-content/40 uppercase tracking-widest text-center mt-1">Profile Photo (Optional)</p>
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
                                    <input
                                        v-model="form.phone"
                                        @input="handlePatientPhoneInput"
                                        type="tel"
                                        inputmode="numeric"
                                        maxlength="11"
                                        pattern="\d{11}"
                                        placeholder="e.g. 09123456789"
                                        class="input input-bordered rounded-xl bg-base-100"
                                    />
                                    <span v-if="form.errors.phone" class="text-error text-[10px] font-black uppercase mt-1 px-1">{{ form.errors.phone }}</span>
                                    <span v-else-if="form.phone && form.phone.length !== 11" class="text-error text-[10px] font-black uppercase mt-1 px-1">Phone must be 11 digits</span>
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
                                        teleport="body"
                                        position="bottom"
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
                                <div class="form-control">
                                    <label class="label"><span class="label-text text-xs font-black uppercase tracking-widest text-base-content/50">Patient Type</span></label>
                                    <select v-model="form.patient_type" class="select select-bordered rounded-xl bg-base-100">
                                        <option value="">Auto / Unset</option>
                                        <option value="pedia">Pedia</option>
                                        <option value="adult">Adult</option>
                                    </select>
                                </div>
                                <div class="form-control sm:col-span-2 lg:col-span-1">
                                    <label class="label"><span class="label-text text-xs font-black uppercase tracking-widest text-base-content/50">Initial Balance</span></label>
                                    <div class="relative">
                                        <span
                                            class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-lg font-black leading-none text-base-content"
                                            style="font-family: 'Segoe UI Symbol', 'Noto Sans', 'Arial Unicode MS', 'DejaVu Sans', sans-serif;"
                                        >&#8369;</span>
                                        <input v-model.number="form.initial_balance" type="number" step="0.01" min="0" class="input input-bordered w-full pl-9 rounded-xl bg-base-100" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Expanded Info -->
                    <div class="space-y-4 border-t border-base-300 pt-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-control" style="--dp-input-padding: 0px 1rem; --dp-border-radius: 0.75rem; --dp-border-color: transparent;">
                                <label class="label"><span class="label-text text-xs font-black uppercase tracking-widest text-base-content/50">First Visit</span></label>
                                <VueDatePicker
                                    v-model="form.first_visit_at"
                                    :enable-time-picker="false"
                                    auto-apply
                                    format="yyyy-MM-dd"
                                    value-format="yyyy-MM-dd"
                                    placeholder="Select First Visit"
                                    teleport="body"
                                    position="bottom"
                                    input-class-name="input input-bordered w-full rounded-xl bg-base-100 placeholder:text-base-content/50"
                                />
                            </div>
                            <div class="form-control" style="--dp-input-padding: 0px 1rem; --dp-border-radius: 0.75rem; --dp-border-color: transparent;">
                                <label class="label"><span class="label-text text-xs font-black uppercase tracking-widest text-base-content/50">Last Recall</span></label>
                                <VueDatePicker
                                    v-model="form.last_recall_at"
                                    :enable-time-picker="false"
                                    auto-apply
                                    format="yyyy-MM-dd"
                                    value-format="yyyy-MM-dd"
                                    placeholder="Select Last Recall"
                                    teleport="body"
                                    position="bottom"
                                    input-class-name="input input-bordered w-full rounded-xl bg-base-100 placeholder:text-base-content/50"
                                />
                            </div>
                        </div>

                        <div class="form-control">
                            <label class="label"><span class="label-text text-xs font-black uppercase tracking-widest text-base-content/50">Tags</span></label>
                            <input
                                v-model="tagsText"
                                type="text"
                                placeholder="e.g. ortho, senior, high-priority"
                                class="input input-bordered rounded-xl bg-base-100"
                            />
                            <p class="text-[10px] text-base-content/40 font-bold uppercase tracking-widest mt-1 px-1">Separate tags with comma</p>
                        </div>

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
