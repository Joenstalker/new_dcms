<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { regions, provinces, cities, barangays } from 'phil-address';
import { onMounted } from 'vue';
import { debounce } from 'lodash';

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

const page = usePage();
const currentStep = ref(1);
const isLoading = ref(false);
const subdomainSuggestions = ref([]);
const subdomainChecking = ref(false);
const subdomainAvailable = ref(null);

// Dynamic domain from backend config
const clinicDomain = computed(() => {
    const appUrl = page.props.config?.app_url || 'http://localhost:8080';
    const url = new URL(appUrl);
    const port = url.port ? `:${url.port}` : '';
    return `${url.hostname}${port}`;
});

// Form data
const form = useForm({
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    street: '',
    barangay: '',
    city: '',
    province: '',
    region: '', // Added region
    clinic_name: '',
    admin_name: '',
    password: '',
    subdomain: '',
});

// Address Data State
const regionList = ref([]);
const provinceList = ref([]);
const cityList = ref([]);
const barangayList = ref([]);

const selectedRegionCode = ref('');
const selectedProvinceCode = ref('');
const selectedCityCode = ref('');
const selectedBarangayCode = ref('');

const isAddressLoading = ref({
    regions: false,
    provinces: false,
    cities: false,
    barangays: false
});

// Load regions on mount
onMounted(async () => {
    isAddressLoading.value.regions = true;
    try {
        regionList.value = await regions();
    } catch (error) {
        console.error('Failed to load regions:', error);
    } finally {
        isAddressLoading.value.regions = false;
    }
});

// Cascaded selection handlers
const handleRegionChange = async () => {
    // Reset dependant fields
    selectedProvinceCode.value = '';
    selectedCityCode.value = '';
    selectedBarangayCode.value = '';
    provinceList.value = [];
    cityList.value = [];
    barangayList.value = [];
    
    form.province = '';
    form.city = '';
    form.barangay = '';

    const region = regionList.value.find(r => r.psgcCode === selectedRegionCode.value);
    form.region = region ? region.name : '';

    if (selectedRegionCode.value) {
        isAddressLoading.value.provinces = true;
        try {
            provinceList.value = await provinces(selectedRegionCode.value);
        } catch (error) {
            console.error('Failed to load provinces:', error);
        } finally {
            isAddressLoading.value.provinces = false;
        }
    }
};

const handleProvinceChange = async () => {
    selectedCityCode.value = '';
    selectedBarangayCode.value = '';
    cityList.value = [];
    barangayList.value = [];
    
    form.city = '';
    form.barangay = '';

    const province = provinceList.value.find(p => p.id === selectedProvinceCode.value);
    form.province = province ? province.name : '';

    if (selectedProvinceCode.value) {
        isAddressLoading.value.cities = true;
        try {
            cityList.value = await cities(selectedProvinceCode.value);
        } catch (error) {
            console.error('Failed to load cities:', error);
        } finally {
            isAddressLoading.value.cities = false;
        }
    }
};

const handleCityChange = async () => {
    selectedBarangayCode.value = '';
    barangayList.value = [];
    
    form.barangay = '';

    const city = cityList.value.find(c => c.id === selectedCityCode.value);
    form.city = city ? city.name : '';

    if (selectedCityCode.value) {
        isAddressLoading.value.barangays = true;
        try {
            barangayList.value = await barangays(selectedCityCode.value);
        } catch (error) {
            console.error('Failed to load barangays:', error);
        } finally {
            isAddressLoading.value.barangays = false;
        }
    }
};

const handleBarangayChange = () => {
    const brgy = barangayList.value.find(b => b.id === selectedBarangayCode.value);
    form.barangay = brgy ? brgy.name : '';
};

// Computed - Combine first and last name for backend
const fullAdminName = computed(() => {
    return `${form.first_name} ${form.last_name}`.trim();
});

// Generate random password
const generatePassword = () => {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789!@#$%';
    let password = '';
    for (let i = 0; i < 12; i++) {
        password += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return password;
};

// Step 1 Validation - Account Information
const isStep1Valid = computed(() => {
    return form.first_name.length >= 2 &&
           form.last_name.length >= 2 &&
           form.email.includes('@') &&
           form.phone.length >= 10 &&
           form.street.length >= 3 &&
           form.barangay.length >= 2 &&
           form.city.length >= 2 &&
           form.province.length >= 2 &&
           form.region.length >= 2;
});

const isStep2Valid = computed(() => {
    return form.subdomain.length >= 3 && subdomainAvailable.value === true;
});

const isStep3Valid = computed(() => {
    return isStep1Valid.value && isStep2Valid.value;
});

// Watch for clinic name changes to auto-suggest subdomain
watch(() => form.clinic_name, (newVal) => {
    if (newVal.length >= 3 && currentStep.value === 2 && !form.subdomain) {
        fetchSubdomainSuggestions();
    }
});

// Debounced check for subdomain availability
const debouncedCheck = debounce(() => {
    checkSubdomainAvailability();
}, 500);

// Sanitize and check subdomain on change
watch(() => form.subdomain, (newVal) => {
    if (!newVal) return;
    
    // Sanitize: Allow only lowercase letters, numbers, and hyphens
    const sanitized = newVal.toLowerCase()
        .replace(/[^a-z0-9-]/g, '') // Remove invalid chars
        .replace(/-{2,}/g, '-');    // Remove double hyphens
        
    if (sanitized !== newVal) {
        form.subdomain = sanitized;
    }

    if (sanitized.length >= 3) {
        subdomainAvailable.value = null;
        debouncedCheck();
    } else {
        subdomainAvailable.value = null;
    }
});

// Methods
const fetchSubdomainSuggestions = async () => {
    try {
        const response = await fetch('/registration/suggest-subdomain', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
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
                'Accept': 'application/json',
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
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    clinic_name: form.clinic_name,
                    first_name: form.first_name,
                    last_name: form.last_name,
                    admin_name: fullAdminName.value,
                    email: form.email,
                    phone: form.phone,
                    street: form.street,
                    region: form.region,
                    barangay: form.barangay,
                    city: form.city,
                    province: form.province,
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
    // Generate password automatically
    const generatedPassword = generatePassword();
    
    emit('openPayment', {
        clinic_name: form.clinic_name,
        first_name: form.first_name,
        last_name: form.last_name,
        email: form.email,
        phone: form.phone,
        street: form.street,
        region: form.region,
        barangay: form.barangay,
        city: form.city,
        province: form.province,
        admin_name: fullAdminName.value,
        password: generatedPassword,
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
    <Modal :show="show" @close="closeModal" maxWidth="3xl">
        <div class="p-5">
            <!-- Header -->
            <div class="flex justify-between items-center mb-4">
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
            <div class="mb-5">
                <div class="flex items-center justify-between px-2">
                    <div v-for="step in 3" :key="step" class="flex items-center">
                        <div :class="[
                            'w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold transition-colors',
                            currentStep >= step ? 'bg-[#2B7CB3] text-white' : 'bg-gray-200 text-gray-500'
                        ]">
                            {{ step }}
                        </div>
                        <div v-if="step < 3" :class="[
                            'w-16 sm:w-24 h-1 mx-2 transition-colors',
                            currentStep > step ? 'bg-[#2B7CB3]' : 'bg-gray-200'
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
            <div v-if="currentStep === 1" class="space-y-4">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Account Information</h3>
                
                <!-- Name and Phone Fields -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <InputLabel for="first_name" value="First Name *" />
                        <TextInput
                            id="first_name"
                            type="text"
                            class="mt-1 block w-full"
                            v-model="form.first_name"
                            placeholder="Juan"
                            required
                            autofocus
                        />
                        <InputError class="mt-1" :message="form.errors.first_name" />
                    </div>
                    <div>
                        <InputLabel for="last_name" value="Last Name *" />
                        <TextInput
                            id="last_name"
                            type="text"
                            class="mt-1 block w-full"
                            v-model="form.last_name"
                            placeholder="Dela Cruz"
                            required
                        />
                        <InputError class="mt-1" :message="form.errors.last_name" />
                    </div>
                    <div>
                        <InputLabel for="phone" value="Phone Number *" />
                        <TextInput
                            id="phone"
                            type="tel"
                            class="mt-1 block w-full"
                            v-model="form.phone"
                            placeholder="09xxxxxxxxx"
                            required
                        />
                        <InputError class="mt-1" :message="form.errors.phone" />
                    </div>
                </div>

                <!-- Email and Clinic Name -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="email" value="Email Address *" />
                        <TextInput
                            id="email"
                            type="email"
                            class="mt-1 block w-full"
                            v-model="form.email"
                            placeholder="juan@clinic.com"
                            required
                        />
                        <InputError class="mt-1" :message="form.errors.email" />
                    </div>
                    <div>
                        <InputLabel for="clinic_name" value="Clinic Name *" />
                        <TextInput
                            id="clinic_name"
                            type="text"
                            class="mt-1 block w-full"
                            v-model="form.clinic_name"
                            placeholder="e.g., Smile Dental Clinic"
                            required
                        />
                        <InputError class="mt-1" :message="form.errors.clinic_name" />
                    </div>
                </div>

                <!-- Address Fields -->
                <div class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <InputLabel for="region" value="Region *" />
                            <select
                                id="region"
                                v-model="selectedRegionCode"
                                @change="handleRegionChange"
                                class="mt-1 block w-full border-gray-300 focus:border-[#2B7CB3] focus:ring-[#2B7CB3] rounded-md shadow-sm text-sm"
                                required
                            >
                                <option value="" disabled>Select Region</option>
                                <option v-for="reg in regionList" :key="reg.psgcCode" :value="reg.psgcCode">
                                    {{ reg.name }}
                                </option>
                            </select>
                            <InputError class="mt-1" :message="form.errors.region" />
                        </div>
                        <div>
                            <InputLabel for="province" value="Province *" />
                            <select
                                id="province"
                                v-model="selectedProvinceCode"
                                @change="handleProvinceChange"
                                :disabled="!selectedRegionCode || isAddressLoading.provinces"
                                class="mt-1 block w-full border-gray-300 focus:border-[#2B7CB3] focus:ring-[#2B7CB3] rounded-md shadow-sm text-sm disabled:bg-gray-50 opacity-100"
                                required
                            >
                                <option value="" disabled>{{ isAddressLoading.provinces ? 'Loading...' : 'Select Province' }}</option>
                                <option v-for="prov in provinceList" :key="prov.id" :value="prov.id">
                                    {{ prov.name }}
                                </option>
                            </select>
                            <InputError class="mt-1" :message="form.errors.province" />
                        </div>
                        <div>
                            <InputLabel for="city" value="City / Municipality *" />
                            <select
                                id="city"
                                v-model="selectedCityCode"
                                @change="handleCityChange"
                                :disabled="!selectedProvinceCode || isAddressLoading.cities"
                                class="mt-1 block w-full border-gray-300 focus:border-[#2B7CB3] focus:ring-[#2B7CB3] rounded-md shadow-sm text-sm disabled:bg-gray-50"
                                required
                            >
                                <option value="" disabled>{{ isAddressLoading.cities ? 'Loading...' : 'Select City' }}</option>
                                <option v-for="city in cityList" :key="city.id" :value="city.id">
                                    {{ city.name }}
                                </option>
                            </select>
                            <InputError class="mt-1" :message="form.errors.city" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <InputLabel for="barangay" value="Barangay *" />
                            <select
                                id="barangay"
                                v-model="selectedBarangayCode"
                                @change="handleBarangayChange"
                                :disabled="!selectedCityCode || isAddressLoading.barangays"
                                class="mt-1 block w-full border-gray-300 focus:border-[#2B7CB3] focus:ring-[#2B7CB3] rounded-md shadow-sm text-sm disabled:bg-gray-50"
                                required
                            >
                                <option value="" disabled>{{ isAddressLoading.barangays ? 'Loading...' : 'Select Barangay' }}</option>
                                <option v-for="brgy in barangayList" :key="brgy.id" :value="brgy.id">
                                    {{ brgy.name }}
                                </option>
                            </select>
                            <InputError class="mt-1" :message="form.errors.barangay" />
                        </div>
                        <div class="sm:col-span-2">
                            <InputLabel for="street" value="Street Address *" />
                            <TextInput
                                id="street"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.street"
                                placeholder="123 Main Street, Unit/Floor"
                                required
                            />
                            <InputError class="mt-1" :message="form.errors.street" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 2: Web Identity -->
            <div v-if="currentStep === 2" class="space-y-6">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Create Your Clinic's Web Identity</h3>
                    <p class="text-xs text-gray-500 mt-1">This is the unique address patients will use to book appointments online.</p>
                </div>
                
                <div class="bg-gray-900 rounded-xl p-4 shadow-inner border border-gray-800">
                    <div class="flex items-center space-x-2 mb-2">
                        <div class="flex space-x-1">
                            <div class="w-2 h-2 rounded-full bg-red-400"></div>
                            <div class="w-2 h-2 rounded-full bg-yellow-400"></div>
                            <div class="w-2 h-2 rounded-full bg-green-400"></div>
                        </div>
                        <div class="flex-1 bg-gray-800 rounded px-2 py-0.5 text-[10px] text-gray-400 flex items-center">
                            <svg class="w-3 h-3 mr-1 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            https://{{ form.subdomain || 'your-clinic' }}.{{ clinicDomain }}
                        </div>
                    </div>
                    <p class="text-center text-sm font-medium text-white py-2">
                        {{ form.subdomain || 'your-clinic' }}<span class="text-gray-500">.{{ clinicDomain }}</span>
                    </p>
                </div>

                <div>
                    <InputLabel for="subdomain" value="Your Personalized Web Name *" />
                    <div class="mt-2">
                        <input
                            id="subdomain"
                            type="text"
                            class="block w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-[#2B7CB3] focus:border-[#2B7CB3] sm:text-sm transition-all shadow-sm"
                            v-model="form.subdomain"
                            placeholder="e.g., smile-dental"
                        />
                    </div>
                    
                    <!-- Status Indicators -->
                    <div class="mt-2 h-5">
                        <span v-if="subdomainChecking" class="text-xs text-gray-500 flex items-center">
                            <svg class="animate-spin h-3 w-3 mr-1" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Checking availability...
                        </span>
                        <span v-else-if="subdomainAvailable === true" class="text-xs text-green-600 font-medium flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Great choice! This name is available.
                        </span>
                        <span v-else-if="subdomainAvailable === false" class="text-xs text-red-600 font-medium flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            This name is already taken. Try another?
                        </span>
                    </div>

                    <!-- Suggestions -->
                    <div v-if="subdomainSuggestions.length > 0 && !form.subdomain" class="mt-4">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Recommended for you:</p>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="suggestion in subdomainSuggestions"
                                :key="suggestion"
                                @click="selectSuggestion(suggestion)"
                                class="px-3 py-1.5 text-xs font-medium bg-white hover:bg-blue-50 text-gray-700 hover:text-[#2B7CB3] rounded-lg border border-gray-200 hover:border-[#2B7CB3] transition-all shadow-sm"
                            >
                                {{ suggestion }}
                            </button>
                        </div>
                    </div>
                </div>

                <div class="bg-amber-50 border border-amber-100 rounded-lg p-3 flex items-start">
                    <span class="text-amber-500 mr-2 text-lg">✨</span>
                    <p class="text-xs text-amber-800 leading-relaxed">
                        <strong>Pro Tip:</strong> Use a name that's easy to say and remember. Short names look best on business cards and social media!
                    </p>
                </div>
            </div>

            <!-- Step 3: Confirmation -->
            <div v-if="currentStep === 3" class="space-y-4">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Review & Confirm</h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Clinic Info -->
                    <div class="bg-gray-50 rounded-xl p-3 border border-gray-100">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2">Clinic Details</p>
                        <div class="space-y-1.5">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Name:</span>
                                <span class="font-medium text-gray-900">{{ form.clinic_name }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Admin:</span>
                                <span class="font-medium text-gray-900 text-right">{{ fullAdminName }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">URL:</span>
                                <span class="font-medium text-[#2B7CB3] text-right">{{ form.subdomain }}.{{ clinicDomain }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Subscription Details -->
                    <div class="bg-blue-50/50 rounded-xl p-3 border border-blue-100">
                        <p class="text-[10px] font-bold text-[#2B7CB3] uppercase tracking-wider mb-2">Subscription</p>
                        <div class="space-y-1.5">
                            <div class="flex justify-between text-sm" v-if="selectedPlan">
                                <span class="text-gray-500">Plan:</span>
                                <span class="font-bold text-gray-900">{{ selectedPlan.name }}</span>
                            </div>
                            <div class="flex justify-between text-sm" v-if="selectedPlan">
                                <span class="text-gray-500">Monthly:</span>
                                <span class="font-black text-[#2B7CB3]">₱{{ Number(selectedPlan.price_monthly).toLocaleString() }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Cycle:</span>
                                <span class="font-medium text-gray-900">Billed monthly</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Method Selection -->
                <div>
                    <h4 class="text-sm font-semibold text-gray-900 mb-3">Select Payment Method</h4>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        <div
                            v-for="method in paymentMethods"
                            :key="method.id"
                            :class="[
                                'relative rounded-xl border p-3 cursor-pointer transition-all flex flex-col items-center justify-center text-center',
                                method.available 
                                    ? 'border-[#2B7CB3] bg-white shadow-sm ring-1 ring-[#2B7CB3]/10 hover:shadow-md' 
                                    : 'border-gray-200 bg-gray-50 opacity-40 grayscale pointer-events-none'
                            ]"
                        >
                            <span class="text-2xl mb-1">{{ method.icon }}</span>
                            <span class="text-[10px] font-bold text-gray-900 leading-tight">{{ method.name }}</span>
                            
                            <div v-if="method.stripe" class="absolute -top-1.5 -right-1.5">
                                <span class="flex h-3 w-3 relative">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 border border-gray-200 rounded-xl p-3 flex items-start">
                    <span class="mr-2 mt-0.5 text-lg">🛡️</span>
                    <p class="text-[10px] text-gray-500 leading-relaxed">
                        By clicking "Proceed to Payment", you agree to our <a href="#" class="text-[#2B7CB3] hover:underline">Terms</a> and <a href="#" class="text-[#2B7CB3] hover:underline">Privacy Policy</a>. Transactions are strictly secured via Stripe.
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
