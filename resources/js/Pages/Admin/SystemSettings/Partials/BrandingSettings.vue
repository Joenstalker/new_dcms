<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['save', 'uploadLogo', 'deleteLogo']);

const logoForm = useForm({
    logo: null,
});

const logoPreview = ref(null);
const logoInputRef = ref(null);

const brandingDescriptions = {
    platform_name: 'Name displayed on login pages and email notifications',
    platform_logo: 'Upload your logo to display on the login page (PNG, JPG, SVG, WebP)',
    primary_color: 'Primary accent color for the admin portal (hex code)',
    footer_text: 'Copyright text displayed in the footer',
    sidebar_position: 'Position of the sidebar navigation (left or right)',
};

const handleLogoChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        logoForm.logo = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            logoPreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const uploadLogo = () => {
    emit('uploadLogo', logoPreview.value);
};

const deleteLogo = () => {
    emit('deleteLogo');
};

const saveGroup = () => {
    emit('save', 'branding');
};
</script>

<template>
    <div class="bg-white shadow-sm rounded-lg border border-gray-200">
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
                @click="saveGroup"
                class="bg-teal-600 hover:bg-teal-700 text-white px-4 py-2 rounded-lg text-sm font-medium shadow-sm transition-colors"
            >
                Save Branding Settings
            </button>
        </div>
    </div>
</template>
