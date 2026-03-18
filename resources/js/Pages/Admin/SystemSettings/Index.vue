<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ref } from 'vue';
import TabNavigation from './Partials/TabNavigation.vue';
import SessionSettings from './Partials/SessionSettings.vue';
import BrandingSettings from './Partials/BrandingSettings.vue';
import SecuritySettings from './Partials/SecuritySettings.vue';
import MaintenanceSettings from './Partials/MaintenanceSettings.vue';

const props = defineProps({
    settings: Object,
});

// Tab state
const activeTab = ref('session');

const tabs = [
    { id: 'session', name: 'Session', icon: 'clock' },
    { id: 'branding', name: 'Branding', icon: 'paintbrush' },
    { id: 'security', name: 'Security', icon: 'shield-check' },
    { id: 'maintenance', name: 'Maintenance', icon: 'wrench' },
];

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

// Logo preview state
const logoPreview = ref(null);

// Submit form for a specific group
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

// Handle logo upload
const handleLogoUpload = (preview) => {
    logoPreview.value = preview;
    
    const formData = new FormData();
    // Get the file from the input
    const fileInput = document.querySelector('input[type="file"]');
    if (fileInput && fileInput.files[0]) {
        formData.append('logo', fileInput.files[0]);
        
        useForm({}).post(route('admin.system-settings.logo.upload'), {
            forceFormData: true,
            data: formData,
            onSuccess: () => {
                logoPreview.value = null;
            },
        });
    }
};

// Handle logo delete
const handleLogoDelete = () => {
    if (confirm('Are you sure you want to delete the logo?')) {
        useForm({}).delete(route('admin.system-settings.logo.delete'), {
            onSuccess: () => {
                logoPreview.value = null;
            },
        });
    }
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
            <!-- Tab Navigation -->
            <TabNavigation 
                :tabs="tabs" 
                :active-tab="activeTab" 
                @update:activeTab="activeTab = $event"
            />

            <!-- Session Settings -->
            <SessionSettings 
                v-if="activeTab === 'session'" 
                :form="form" 
                @save="saveGroup"
            />

            <!-- Branding Settings -->
            <BrandingSettings 
                v-if="activeTab === 'branding'" 
                :form="form" 
                @save="saveGroup"
                @uploadLogo="handleLogoUpload"
                @deleteLogo="handleLogoDelete"
            />

            <!-- Security Settings -->
            <SecuritySettings 
                v-if="activeTab === 'security'" 
                :form="form" 
                @save="saveGroup"
            />

            <!-- Maintenance Settings -->
            <MaintenanceSettings 
                v-if="activeTab === 'maintenance'" 
                :form="form" 
                @save="saveGroup"
            />
        </div>
    </AdminLayout>
</template>
