<script setup>
import { computed } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        required: true,
    },
    newTenantName: {
        type: String,
        default: '',
    },
    newTenantDomain: {
        type: String,
        default: '',
    },
    databasePreview: {
        type: Object,
        default: null,
    },
    isLoadingPreview: {
        type: Boolean,
        default: false,
    },
    isFormValid: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:newTenantName', 'update:newTenantDomain', 'close', 'create']);

const updateName = (value) => {
    emit('update:newTenantName', value);
};

const updateDomain = (value) => {
    emit('update:newTenantDomain', value);
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="emit('close')"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <!-- Modal panel -->
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Create New Clinic
                            </h3>
                            <div class="mt-4 space-y-4">
                                <!-- Clinic Name -->
                                <div>
                                    <label for="tenant-name" class="block text-sm font-medium text-gray-700">
                                        Clinic Name <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="tenant-name"
                                        :value="newTenantName"
                                        @input="updateName($event.target.value)"
                                        type="text"
                                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-teal-500 focus:border-teal-500 sm:text-sm"
                                        placeholder="e.g., Dental Clinic"
                                    />
                                </div>

                                <!-- Domain/Subdomain -->
                                <div>
                                    <label for="tenant-domain" class="block text-sm font-medium text-gray-700">
                                        Domain/Subdomain <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="tenant-domain"
                                        :value="newTenantDomain"
                                        @input="updateDomain($event.target.value)"
                                        type="text"
                                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-teal-500 focus:border-teal-500 sm:text-sm"
                                        placeholder="e.g., dentalclinic"
                                    />
                                    <p class="mt-1 text-xs text-gray-500">
                                        This will be used to generate the hashed database name
                                    </p>
                                </div>

                                <!-- Database Name Preview -->
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm font-medium text-gray-700">Database Name Preview</span>
                                        <span v-if="isLoadingPreview" class="text-xs text-gray-500">Loading...</span>
                                    </div>
                                    
                                    <div v-if="databasePreview" class="space-y-2">
                                        <div class="flex items-center">
                                            <svg class="h-5 w-5 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4" />
                                            </svg>
                                            <code class="text-sm font-mono text-teal-600 bg-white px-2 py-1 rounded border">
                                                {{ databasePreview.database_name }}
                                            </code>
                                        </div>
                                        <p class="text-xs text-gray-500">
                                            Domain: <span class="font-mono">{{ databasePreview.domain }}</span>
                                        </p>
                                        <p v-if="databasePreview.already_exists" class="text-xs text-red-500 flex items-center">
                                            <svg class="h-4 w-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                            </svg>
                                            This database name already exists
                                        </p>
                                        <p v-else class="text-xs text-green-600 flex items-center">
                                            <svg class="h-4 w-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                            </svg>
                                            Database name is available
                                        </p>
                                    </div>
                                    
                                    <div v-else class="text-sm text-gray-500 italic">
                                        Enter a clinic name to see the database name preview
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button
                        @click="emit('create')"
                        :disabled="!isFormValid"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-teal-600 text-base font-medium text-white hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        Create Clinic
                    </button>
                    <button
                        @click="emit('close')"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                    >
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
