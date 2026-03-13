<script setup>
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    show: Boolean,
    selectedPlan: {
        type: Object,
        default: null
    },
    plans: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['close', 'openPayment']);

const currentStep = ref(1);
const isLoading = ref(false);
const subdomainSuggestions = ref([]);
const subdomainChecking = ref(false);
const subdomainAvailable = ref(null);

// Form data
const form = useForm({
    clinic_name: '',
    admin_name: '',
    email: '',
    password: '',
    password_confirmation: '',
    subdomain: '',
});

// Computed
const isStep1Valid = computed(() => {
    return form.clinic_name.length >= 3 &&
           form.admin_name.length >= 2 &&
           form.email.includes('@') &&
           form.password.length >= 8 &&
           form.password === form.password_confirmation;
});

const isStep2Valid = computed(() => {
    return form.subdomain.length >= 3 && subdomainAvailable.value === true;
});

const isStep3Valid = computed(() => {
    return isStep1Valid.value && isStep2Valid.value;
});

// Watch for clinic name changes to auto-suggest subdomain
watch(() => form.clinic_name, (newVal) => {
    if (newVal.length >= 3 && currentStep.value === 2) {
        fetchSubdomainSuggestions();
    }
});

// Methods
const fetchSubdomainSuggestions = async () => {
    try {
        const response = await fetch('/registration/suggest-subdomain', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ clinic_name: form.clinic_name })
        });
        const data = await response.json();
        if (data.success) {
            subdomainSuggestions.value = data.suggestions;
            if (data.suggestions.length > 0 && !form.subdomain) {
                form.subdomain = data.suggestions[0];
                checkSubdomainAvailability();
            }
        }
    } catch (error) {
        console.error('Failed to fetch suggestions:', error);
    }
};

const checkSubdomainAvailability = async () => {
    if (form.subdomain.length < 3) return;
    
    subdomainChecking.value = true;
    subdomainAvailable.value = null;
    
    try {
        const response = await fetch('/registration/check-subdomain', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ subdomain: form.subdomain })
        });
        const data = await response.json();
        subdomainAvailable.value = data.available;
    } catch (error) {
        console.error('Failed to check subdomain:', error);
    } finally {
        subdomainChecking.value = false;
    }
};

const selectSuggestion = (suggestion) => {
    form.subdomain = suggestion;
    checkSubdomainAvailability();
};

const nextStep = async () => {
    if (currentStep.value === 1) {
        // Validate step 1
        isLoading.value = true;
        try {
            const response = await fetch('/registration/validate-account', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    clinic_name: form.clinic_name,
                    admin_name: form.admin_name,
                    email: form.email,
                    password: form.password,
                    password_confirmation: form.password_confirmation
                })
            });
            const data = await response.json();
            if (data.success) {
                currentStep.value = 2;
            }
        } catch (error) {
            console.error('Validation failed:', error);
        } finally {
            isLoading.value = false;
        }
    } else if (currentStep.value === 2) {
        // Validate step 2
        await checkSubdomainAvailability();
        if (subdomainAvailable.value) {
            currentStep.value = 3;
        }
    }
};

const prevStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--;
    }
};

const proceedToPayment = () => {
    emit('openPayment', {
        clinic_name: form.clinic_name,
        admin_name: form.admin_name,
        email: form.email,
        password: form.password,
        subdomain: form.subdomain,
        plan: props.selectedPlan
    });
};

const closeModal = () => {
    currentStep.value = 1;
    form.reset();
    subdomainAvailable.value = null;
    subdomainSuggestions.value = [];
    emit('close');
};

// Payment method icons
const paymentMethods = [
    { id: 'card', name: 'Credit/Debit Card', icon: '💳', available: true, stripe: true },
    { id: 'gcash', name: 'GCash', icon: '📱', available: false, coming: true },
    { id: 'paypal', name: 'PayPal', icon: '🅿️', available: false, coming: true },
    { id: 'bank', name: 'Bank Transfer', icon: '🏦', available: false, coming: true },
];
</script>

<template>
    <Modal :show="show" @close="closeModal" maxWidth="lg">
        <div class="p-6">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Register Your Clinic</h2>
                    <p class="text-sm text-gray-500 mt-1">Step {{ currentStep }} of 3</p>
                </div>
                <button @click="closeModal" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Progress Bar -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div v-for="step in 3" :key="step" class="flex items-center">
                        <div :class="[
                            'w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold transition-colors',
                            currentStep >= step ? 'bg-teal-500 text-white' : 'bg-gray-200 text-gray-500'
                        ]">
                            {{ step }}
                        </div>
                        <div v-if="step < 3" :class="[
                            'w-16 sm:w-24 h-1 mx-2 transition-colors',
                            currentStep > step ? 'bg-teal-500' : 'bg-gray-200'
                        ]"></div>
                    </div>
                </div>
                <div class="flex justify-between mt-2 text-xs text-gray-500">
                    <span>Account</span>
                    <span>Subdomain</span>
                    <span>Confirm</span>
                </div>
            </div>

            <!-- Step 1: Account Setup -->
            <div v-if="currentStep === 1" class="space-y-5">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Account Setup</h3>
                
                <div>
                    <InputLabel for="clinic_name" value="Clinic Name *" />
                    <TextInput
                        id="clinic_name"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.clinic_name"
                        placeholder="e.g., Smile Dental Clinic"
                        required
                        autofocus
                    />
                    <InputError class="mt-1" :message="form.errors.clinic_name" />
                </div>

                <div>
                    <InputLabel for="admin_name" value="Admin Name *" />
                    <TextInput
                        id="admin_name"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.admin_name"
                        placeholder="Your full name"
                        required
                    />
                    <InputError class="mt-1" :message="form.errors.admin_name" />
                </div>

                <div>
                    <InputLabel for="email" value="Email Address *" />
                    <TextInput
                        id="email"
                        type="email"
                        class="mt-1 block w-full"
                        v-model="form.email"
                        placeholder="you@clinic.com"
                        required
                    />
                    <InputError class="mt-1" :message="form.errors.email" />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="password" value="Password *" />
                        <TextInput
                            id="password"
                            type="password"
                            class="mt-1 block w-full"
                            v-model="form.password"
                            placeholder="Min. 8 characters"
                            required
                        />
                        <InputError class="mt-1" :message="form.errors.password" />
                    </div>
                    <div>
                        <InputLabel for="password_confirmation" value="Confirm Password *" />
                        <TextInput
                            id="password_confirmation"
                            type="password"
                            class="mt-1 block w-full"
                            v-model="form.password_confirmation"
                            placeholder="Confirm password"
                            required
                        />
                        <InputError class="mt-1" :message="form.errors.password_confirmation" />
                    </div>
                </div>
            </div>

            <!-- Step 2: Subdomain Selection -->
            <div v-if="currentStep === 2" class="space-y-5">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Choose Your Subdomain</h3>
                
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                    <p class="text-sm text-blue-800">
                        <strong>🌐 Your clinic URL:</strong> 
                        <span class="font-mono">{{ form.subdomain || 'yourclinic' }}.dcms.test:8080</span>
                    </p>
                </div>

                <div>
                    <InputLabel for="subdomain" value="Subdomain *" />
                    <div class="mt-1 flex rounded-md shadow-sm">
                        <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                            dcms.test/
                        </span>
                        <input
                            id="subdomain"
                            type="text"
                            class="flex-1 block w-full px-3 py-2 rounded-none rounded-r-md border border-gray-300 focus:ring-teal-500 focus:border-teal-500 sm:text-sm"
                            v-model="form.subdomain"
                            @blur="checkSubdomainAvailability"
                            @input="subdomainAvailable = null"
                            placeholder="yourclinic"
                        />
                    </div>
                    
                    <!-- Subdomain Status -->
                    <div class="mt-2 flex items-center">
                        <span v-if="subdomainChecking" class="text-sm text-gray-500">
                            Checking availability...
                        </span>
                        <span v-else-if="subdomainAvailable === true" class="text-sm text-green-600 flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Subdomain is available!
                        </span>
                        <span v-else-if="subdomainAvailable === false" class="text-sm text-red-600 flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            Subdomain is already taken
                        </span>
                    </div>

                    <!-- Suggestions -->
                    <div v-if="subdomainSuggestions.length > 0 && !form.subdomain" class="mt-3">
                        <p class="text-xs text-gray-500 mb-2">Suggestions:</p>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="suggestion in subdomainSuggestions"
                                :key="suggestion"
                                @click="selectSuggestion(suggestion)"
                                class="px-3 py-1 text-sm bg-gray-100 hover:bg-teal-50 hover:text-teal-700 rounded-full border border-gray-200 transition-colors"
                            >
                                {{ suggestion }}
                            </button>
                        </div>
                    </div>
                </div>

                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                    <p class="text-xs text-yellow-700">
                        💡 <strong>Tip:</strong> Choose a short, memorable subdomain that matches your clinic name.
                    </p>
                </div>
            </div>

            <!-- Step 3: Confirmation -->
            <div v-if="currentStep === 3" class="space-y-5">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Review & Confirm</h3>

                <div class="bg-gray-50 rounded-lg p-4 space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Clinic Name:</span>
                        <span class="font-medium text-gray-900">{{ form.clinic_name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Admin Name:</span>
                        <span class="font-medium text-gray-900">{{ form.admin_name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Email:</span>
                        <span class="font-medium text-gray-900">{{ form.email }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Your URL:</span>
                        <span class="font-medium text-teal-600">{{ form.subdomain }}.dcms.test:8080</span>
                    </div>
                    <div v-if="selectedPlan" class="flex justify-between pt-3 border-t border-gray-200">
                        <span class="text-gray-500">Selected Plan:</span>
                        <span class="font-medium text-gray-900">{{ selectedPlan.name }}</span>
                    </div>
                    <div v-if="selectedPlan" class="flex justify-between">
                        <span class="text-gray-500">Monthly Price:</span>
                        <span class="font-bold text-teal-600">₱{{ Number(selectedPlan.price_monthly).toLocaleString() }}</span>
                    </div>
                </div>

                <!-- Payment Method Selection -->
                <div class="mt-6">
                    <h4 class="text-sm font-medium text-gray-900 mb-3">Select Payment Method</h4>
                    <div class="grid grid-cols-2 gap-3">
                        <div
                            v-for="method in paymentMethods"
                            :key="method.id"
                            :class="[
                                'relative rounded-lg border p-4 cursor-pointer transition-all',
                                method.available 
                                    ? 'border-teal-500 bg-teal-50 hover:border-teal-600' 
                                    : 'border-gray-200 bg-gray-50 opacity-60'
                            ]"
                        >
                            <div class="flex items-center justify-between">
                                <span class="text-2xl">{{ method.icon }}</span>
                                <span class="font-medium text-gray-900">{{ method.name }}</span>
                            </div>
                            <div v-if="method.coming" class="absolute -top-2 -right-2">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">
                                    Coming Soon
                                </span>
                            </div>
                            <div v-if="method.stripe" class="absolute -top-2 -right-2">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-teal-100 text-teal-800">
                                    Active
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mt-4">
                    <p class="text-xs text-blue-700">
                        🔒 By proceeding, you agree to our Terms of Service and Privacy Policy.
                        You'll be redirected to Stripe for secure payment.
                    </p>
                </div>
            </div>

            <!-- Navigation Buttons -->
            <div class="mt-8 flex justify-between">
                <SecondaryButton 
                    v-if="currentStep > 1" 
                    @click="prevStep"
                    :disabled="isLoading"
                >
                    Back
                </SecondaryButton>
                <div v-else></div>

                <PrimaryButton
                    v-if="currentStep < 3"
                    @click="nextStep"
                    :disabled="isLoading || (currentStep === 1 && !isStep1Valid) || (currentStep === 2 && !isStep2Valid)"
                    :class="{ 'opacity-50 cursor-not-allowed': isLoading || (currentStep === 1 && !isStep1Valid) || (currentStep === 2 && !isStep2Valid) }"
                >
                    <span v-if="isLoading">Processing...</span>
                    <span v-else>Continue</span>
                </PrimaryButton>

                <PrimaryButton
                    v-else
                    @click="proceedToPayment"
                    class="bg-[#FF6B53] hover:bg-[#ff563b]"
                >
                    Proceed to Payment
                </PrimaryButton>
            </div>
        </div>
    </Modal>
</template>
