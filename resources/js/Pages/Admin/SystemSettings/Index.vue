<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ref, computed } from 'vue';

const props = defineProps({
    settings: Object,
});

// Tab state
const activeTab = ref('session');

// Group labels
const groupLabels = {
    session: 'Session Settings',
    branding: 'Branding Settings',
    security: 'Security Settings',
    maintenance: 'Maintenance Settings',
};

// Group icons
const groupIcons = {
    session: 'clock',
    branding: 'paintbrush',
    shield: 'shield-check',
    maintenance: 'wrench',
};

const tabs = [
    { id: 'session', name: 'Session', icon: 'clock' },
    { id: 'branding', name: 'Branding', icon: 'paintbrush' },
    { id: 'security', name: 'Security', icon: 'shield-check' },
    { id: 'maintenance', name: 'Maintenance', icon: 'wrench' },
];

// Check if setting is Coming Soon
const isComingSoon = (description) => {
    return description && description.includes('Coming Soon');
};

// Format setting key for display
const formatKey = (key) => {
    return key
        .replace(/_/g, ' ')
        .replace(/\b\w/g, l => l.toUpperCase());
};

// Get input type based on setting type
const getInputType = (setting) => {
    if (setting.type === 'boolean') return 'toggle';
    if (setting.type === 'integer') return 'number';
    if (setting.type === 'json') return 'json';
    return 'text';
};

// Build form data from settings
const form = ref({});

const initializeForm = () => {
    const formData = {};
    Object.keys(props.settings).forEach(group => {
        props.settings[group].forEach(setting => {
            formData[setting.key] = setting.value;
        });
    });
    form.value = formData;
};

initializeForm();

// Logo upload form
const logoForm = useForm({
    logo: null,
});

const logoPreview = ref(null);
const logoInputRef = ref(null);

const handleLogoChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        logoForm.logo = file;
        // Create preview URL
        const reader = new FileReader();
        reader.onload = (e) => {
            logoPreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const uploadLogo = () => {
    if (!logoForm.logo) return;
    
    const formData = new FormData();
    formData.append('logo', logoForm.logo);
    
    logoForm.post(route('admin.system-settings.logo.upload'), {
        forceFormData: true,
        onSuccess: () => {
            logoPreview.value = null;
            logoForm.logo = null;
        },
    });
};

const deleteLogo = () => {
    if (confirm('Are you sure you want to delete the logo?')) {
        useForm({}).delete(route('admin.system-settings.logo.delete'), {
            onSuccess: () => {
                logoPreview.value = null;
            },
        });
    }
};

// Submit form
const submitForm = () => {
    const submitData = {};
    Object.keys(form.value).forEach(key => {
        submitData[key] = form.value[key];
    });

    useForm({ settings: submitData }).post(route('admin.system-settings.update'), {
        onSuccess: () => {
            // Success handled by AdminLayout
        },
    });
};

// Save a specific group
const saveGroup = (group) => {
    const groupSettings = {};
    props.settings[group]?.forEach(setting => {
        groupSettings[setting.key] = form.value[setting.key];
    });

    useForm({ settings: groupSettings }).post(route('admin.system-settings.update-group', group), {
        onSuccess: () => {
            // Success handled by AdminLayout
        },
    });
};

// Session-specific descriptions
const sessionDescriptions = {
    session_lifetime: 'Minutes before a session expires due to inactivity',
    session_expire_on_close: 'End session when user closes the browser',
    session_encrypt: 'Encrypt session data for additional security',
    remember_me_duration: 'Duration in minutes for "Remember Me" functionality (default: 14 days)',
    max_login_attempts: 'Maximum failed login attempts before account lockout',
    lockout_duration: 'Minutes to lock out user after exceeding max attempts',
    password_reset_expiry: 'Minutes before password reset token expires',
};

// Branding-specific descriptions
const brandingDescriptions = {
    platform_name: 'Name displayed on login pages and email notifications',
    platform_logo: 'Upload your logo to display on the login page (PNG, JPG, SVG, WebP)',
    primary_color: 'Primary accent color for the admin portal (hex code)',
    footer_text: 'Copyright text displayed in the footer',
    sidebar_position: 'Position of the sidebar navigation (left or right)',
};

// Security-specific descriptions
const securityDescriptions = {
    two_factor_auth_enabled: 'Require two-factor authentication for admin login (Coming Soon)',
    ip_whitelist_enabled: 'Restrict admin access to specific IP addresses (Coming Soon)',
    audit_logging_enabled: 'Log all admin actions for audit purposes',
    data_encryption_enabled: 'Encrypt sensitive patient data in the database',
    concurrent_sessions_enabled: 'Allow users to be logged in from multiple devices (Coming Soon)',
};

// Maintenance-specific descriptions
const maintenanceDescriptions = {
    maintenance_mode: 'Enable to show maintenance message to all users',
    maintenance_message: 'Message displayed when system is under maintenance',
};
</script>

<template>
    <Head title="System Settings" />
    <AdminLayout>
        <template #header>
            <div class="flex justify-between items-center w-full">
                <h1 class="text-xl font-bold text-gray-900">System Settings</h1>
            </div>
        </template>

        <div class="max-w-6xl mx-auto">
            <!-- Tabs -->
            <div class="mb-6 border-b border-gray-200">
                <nav class="-mb-px flex space-x-8">
                    <button
                        v-for="tab in tabs"
                        :key="tab.id"
                        @click="activeTab = tab.id"
                        :class="[
                            activeTab === tab.id
                                ? 'border-teal-500 text-teal-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                            'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2'
                        ]"
                    >
                        <!-- Icons -->
                        <svg v-if="tab.icon === 'clock'" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <svg v-if="tab.icon === 'paintbrush'" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                        </svg>
                        <svg v-if="tab.icon === 'shield-check'" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        <svg v-if="tab.icon === 'wrench'" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        {{ tab.name }}
                    </button>
                </nav>
            </div>

            <!-- Session Settings -->
            <div v-if="activeTab === 'session'" class="bg-white shadow-sm rounded-lg border border-gray-200">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Session Configuration</h2>
                    <p class="mt-1 text-sm text-gray-500">Manage session behavior and security settings for the admin portal.</p>
                </div>
                
                <div class="p-6 space-y-6">
                    <!-- Session Lifetime -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Session Lifetime</label>
                            <p class="text-xs text-gray-500 mt-1">{{ sessionDescriptions.session_lifetime }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <input
                                v-model="form.session_lifetime"
                                type="number"
                                min="5"
                                max="1440"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm px-3 py-2 border"
                            />
                            <span class="text-sm text-gray-500 whitespace-nowrap">minutes</span>
                        </div>
                    </div>

                    <!-- Session Expire on Close -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Expire on Close</label>
                            <p class="text-xs text-gray-500 mt-1">{{ sessionDescriptions.session_expire_on_close }}</p>
                        </div>
                        <div class="flex items-center">
                            <button
                                @click="form.session_expire_on_close = !form.session_expire_on_close"
                                :class="[
                                    form.session_expire_on_close ? 'bg-teal-600' : 'bg-gray-200',
                                    'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2'
                                ]"
                            >
                                <span
                                    :class="[
                                        form.session_expire_on_close ? 'translate-x-5' : 'translate-x-0',
                                        'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out'
                                    ]"
                                />
                            </button>
                        </div>
                    </div>

                    <!-- Session Encryption -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Session Encryption</label>
                            <p class="text-xs text-gray-500 mt-1">{{ sessionDescriptions.session_encrypt }}</p>
                        </div>
                        <div class="flex items-center">
                            <button
                                @click="form.session_encrypt = !form.session_encrypt"
                                :class="[
                                    form.session_encrypt ? 'bg-teal-600' : 'bg-gray-200',
                                    'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2'
                                ]"
                            >
                                <span
                                    :class="[
                                        form.session_encrypt ? 'translate-x-5' : 'translate-x-0',
                                        'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out'
                                    ]"
                                />
                            </button>
                        </div>
                    </div>

                    <!-- Remember Me Duration -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Remember Me Duration</label>
                            <p class="text-xs text-gray-500 mt-1">{{ sessionDescriptions.remember_me_duration }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <input
                                v-model="form.remember_me_duration"
                                type="number"
                                min="60"
                                max="525600"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm px-3 py-2 border"
                            />
                            <span class="text-sm text-gray-500 whitespace-nowrap">minutes</span>
                        </div>
                    </div>

                    <!-- Max Login Attempts -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Max Login Attempts</label>
                            <p class="text-xs text-gray-500 mt-1">{{ sessionDescriptions.max_login_attempts }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <input
                                v-model="form.max_login_attempts"
                                type="number"
                                min="3"
                                max="10"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm px-3 py-2 border"
                            />
                            <span class="text-sm text-gray-500 whitespace-nowrap">attempts</span>
                        </div>
                    </div>

                    <!-- Lockout Duration -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Lockout Duration</label>
                            <p class="text-xs text-gray-500 mt-1">{{ sessionDescriptions.lockout_duration }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <input
                                v-model="form.lockout_duration"
                                type="number"
                                min="1"
                                max="1440"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm px-3 py-2 border"
                            />
                            <span class="text-sm text-gray-500 whitespace-nowrap">minutes</span>
                        </div>
                    </div>

                    <!-- Password Reset Expiry -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Password Reset Expiry</label>
                            <p class="text-xs text-gray-500 mt-1">{{ sessionDescriptions.password_reset_expiry }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <input
                                v-model="form.password_reset_expiry"
                                type="number"
                                min="5"
                                max="1440"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm px-3 py-2 border"
                            />
                            <span class="text-sm text-gray-500 whitespace-nowrap">minutes</span>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
                    <button
                        @click="saveGroup('session')"
                        class="bg-teal-600 hover:bg-teal-700 text-white px-4 py-2 rounded-lg text-sm font-medium shadow-sm transition-colors"
                    >
                        Save Session Settings
                    </button>
                </div>
            </div>

            <!-- Branding Settings -->
            <div v-if="activeTab === 'branding'" class="bg-white shadow-sm rounded-lg border border-gray-200">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Platform Branding</h2>
                    <p class="mt-1 text-sm text-gray-500">Customize the appearance of the admin portal.</p>
                </div>
                
                <div class="p-6 space-y-6">
                    <!-- Platform Name -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Platform Name</label>
                            <p class="text-xs text-gray-500 mt-1">{{ brandingDescriptions.platform_name }}</p>
                        </div>
                        <div>
                            <input
                                v-model="form.platform_name"
                                type="text"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm px-3 py-2 border"
                            />
                        </div>
                    </div>

                    <!-- Primary Color -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Primary Color</label>
                            <p class="text-xs text-gray-500 mt-1">{{ brandingDescriptions.primary_color }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <input
                                v-model="form.primary_color"
                                type="color"
                                class="h-10 w-14 rounded-md border border-gray-300 cursor-pointer"
                            />
                            <input
                                v-model="form.primary_color"
                                type="text"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm px-3 py-2 border"
                                placeholder="#0ea5e9"
                            />
                        </div>
                    </div>

                    <!-- Sidebar Position -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Sidebar Position</label>
                            <p class="text-xs text-gray-500 mt-1">{{ brandingDescriptions.sidebar_position }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <select
                                v-model="form.sidebar_position"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm px-3 py-2 border"
                            >
                                <option value="left">Left</option>
                                <option value="right">Right</option>
                            </select>
                        </div>
                    </div>

                    <!-- Footer Text -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Footer Text</label>
                            <p class="text-xs text-gray-500 mt-1">{{ brandingDescriptions.footer_text }}</p>
                        </div>
                        <div>
                            <input
                                v-model="form.footer_text"
                                type="text"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm px-3 py-2 border"
                            />
                        </div>
                    </div>

                    <!-- Platform Logo -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Platform Logo</label>
                            <p class="text-xs text-gray-500 mt-1">{{ brandingDescriptions.platform_logo }}</p>
                        </div>
                        <div>
                            <!-- Current Logo Preview -->
                            <div v-if="form.platform_logo && !logoPreview" class="mb-3">
                                <div class="relative inline-block">
                                    <img 
                                        :src="'/storage/logos/' + form.platform_logo" 
                                        alt="Current Logo" 
                                        class="h-16 w-auto rounded-lg border border-gray-300"
                                    />
                                    <button
                                        @click="deleteLogo"
                                        type="button"
                                        class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600"
                                    >
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Logo Upload Area -->
                            <div class="flex items-center gap-3">
                                <div class="flex-1">
                                    <div 
                                        @click="$refs.logoInput.click()"
                                        class="flex flex-col items-center justify-center w-full h-24 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 transition-colors"
                                    >
                                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                            <svg class="w-6 h-6 mb-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            <p class="text-xs text-gray-500">Click to upload</p>
                                        </div>
                                        <input 
                                            ref="logoInputRef"
                                            type="file" 
                                            class="hidden" 
                                            accept="image/png,image/jpeg,image/svg+xml,image/webp"
                                            @change="handleLogoChange"
                                        />
                                    </div>
                                </div>
                                
                                <!-- Upload Button -->
                                <button
                                    v-if="logoPreview || logoForm.logo"
                                    @click="uploadLogo"
                                    :disabled="logoForm.processing"
                                    class="px-3 py-2 bg-teal-600 text-white text-sm rounded-md hover:bg-teal-700 disabled:opacity-50"
                                >
                                    {{ logoForm.processing ? 'Uploading...' : 'Upload' }}
                                </button>
                            </div>
                            
                            <!-- Preview -->
                            <div v-if="logoPreview" class="mt-3">
                                <p class="text-xs text-gray-500 mb-1">Preview:</p>
                                <img :src="logoPreview" alt="Preview" class="h-16 w-auto rounded-lg border border-gray-300" />
                            </div>
                            
                            <p v-if="form.platform_logo && !logoPreview" class="mt-2 text-xs text-teal-600">Current: {{ form.platform_logo }}</p>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
                    <button
                        @click="saveGroup('branding')"
                        class="bg-teal-600 hover:bg-teal-700 text-white px-4 py-2 rounded-lg text-sm font-medium shadow-sm transition-colors"
                    >
                        Save Branding Settings
                    </button>
                </div>
            </div>

            <!-- Security Settings -->
            <div v-if="activeTab === 'security'" class="bg-white shadow-sm rounded-lg border border-gray-200">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Security Configuration</h2>
                    <p class="mt-1 text-sm text-gray-500">Manage security settings for the admin portal.</p>
                </div>
                
                <div class="p-6 space-y-6">
                    <!-- Two Factor Auth - Coming Soon -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center opacity-60">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 flex items-center gap-2">
                                Two-Factor Authentication
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">
                                    Coming Soon
                                </span>
                            </label>
                            <p class="text-xs text-gray-500 mt-1">{{ securityDescriptions.two_factor_auth_enabled }}</p>
                        </div>
                        <div class="flex items-center">
                            <button
                                disabled
                                class="opacity-50 cursor-not-allowed relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none bg-gray-200"
                            >
                                <span class="translate-x-0 pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out" />
                            </button>
                        </div>
                    </div>

                    <!-- IP Whitelist - Coming Soon -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center opacity-60">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 flex items-center gap-2">
                                IP Whitelist
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">
                                    Coming Soon
                                </span>
                            </label>
                            <p class="text-xs text-gray-500 mt-1">{{ securityDescriptions.ip_whitelist_enabled }}</p>
                        </div>
                        <div class="flex items-center">
                            <button
                                disabled
                                class="opacity-50 cursor-not-allowed relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none bg-gray-200"
                            >
                                <span class="translate-x-0 pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out" />
                            </button>
                        </div>
                    </div>

                    <!-- Audit Logging -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Audit Logging</label>
                            <p class="text-xs text-gray-500 mt-1">{{ securityDescriptions.audit_logging_enabled }}</p>
                        </div>
                        <div class="flex items-center">
                            <button
                                @click="form.audit_logging_enabled = !form.audit_logging_enabled"
                                :class="[
                                    form.audit_logging_enabled ? 'bg-teal-600' : 'bg-gray-200',
                                    'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2'
                                ]"
                            >
                                <span
                                    :class="[
                                        form.audit_logging_enabled ? 'translate-x-5' : 'translate-x-0',
                                        'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out'
                                    ]"
                                />
                            </button>
                        </div>
                    </div>

                    <!-- Data Encryption -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Data Encryption</label>
                            <p class="text-xs text-gray-500 mt-1">{{ securityDescriptions.data_encryption_enabled }}</p>
                        </div>
                        <div class="flex items-center">
                            <button
                                @click="form.data_encryption_enabled = !form.data_encryption_enabled"
                                :class="[
                                    form.data_encryption_enabled ? 'bg-teal-600' : 'bg-gray-200',
                                    'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2'
                                ]"
                            >
                                <span
                                    :class="[
                                        form.data_encryption_enabled ? 'translate-x-5' : 'translate-x-0',
                                        'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out'
                                    ]"
                                />
                            </button>
                        </div>
                    </div>

                    <!-- Concurrent Sessions - Coming Soon -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center opacity-60">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 flex items-center gap-2">
                                Concurrent Sessions
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">
                                    Coming Soon
                                </span>
                            </label>
                            <p class="text-xs text-gray-500 mt-1">{{ securityDescriptions.concurrent_sessions_enabled }}</p>
                        </div>
                        <div class="flex items-center">
                            <button
                                disabled
                                class="opacity-50 cursor-not-allowed relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none bg-gray-200"
                            >
                                <span class="translate-x-0 pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out" />
                            </button>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
                    <button
                        @click="saveGroup('security')"
                        class="bg-teal-600 hover:bg-teal-700 text-white px-4 py-2 rounded-lg text-sm font-medium shadow-sm transition-colors"
                    >
                        Save Security Settings
                    </button>
                </div>
            </div>

            <!-- Maintenance Settings -->
            <div v-if="activeTab === 'maintenance'" class="bg-white shadow-sm rounded-lg border border-gray-200">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Maintenance Mode</h2>
                    <p class="mt-1 text-sm text-gray-500">Control system availability and maintenance settings.</p>
                </div>
                
                <div class="p-6 space-y-6">
                    <!-- Maintenance Mode Toggle -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Maintenance Mode</label>
                            <p class="text-xs text-gray-500 mt-1">{{ maintenanceDescriptions.maintenance_mode }}</p>
                        </div>
                        <div class="flex items-center">
                            <button
                                @click="form.maintenance_mode = !form.maintenance_mode"
                                :class="[
                                    form.maintenance_mode ? 'bg-red-600' : 'bg-gray-200',
                                    'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2'
                                ]"
                            >
                                <span
                                    :class="[
                                        form.maintenance_mode ? 'translate-x-5' : 'translate-x-0',
                                        'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out'
                                    ]"
                                />
                            </button>
                        </div>
                    </div>

                    <!-- Maintenance Message -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Maintenance Message</label>
                            <p class="text-xs text-gray-500 mt-1">{{ maintenanceDescriptions.maintenance_message }}</p>
                        </div>
                        <div>
                            <textarea
                                v-model="form.maintenance_message"
                                rows="3"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm px-3 py-2 border"
                            />
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
                    <button
                        @click="saveGroup('maintenance')"
                        class="bg-teal-600 hover:bg-teal-700 text-white px-4 py-2 rounded-lg text-sm font-medium shadow-sm transition-colors"
                    >
                        Save Maintenance Settings
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
