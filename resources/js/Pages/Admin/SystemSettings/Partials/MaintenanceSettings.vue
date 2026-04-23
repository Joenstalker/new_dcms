<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['save']);

const maintenanceDescriptions = {
    maintenance_mode: 'Enable to show maintenance message to all users',
    maintenance_message: 'Message displayed when system is under maintenance',
    central_api_url: 'The URL of Laptop A (Central) used to fetch latest system updates.',
};

const saveGroup = () => {
    emit('save', 'maintenance');
};

const branding = computed(() => usePage().props.branding || {});
const primaryColor = computed(() => branding.value.primary_color || '#0ea5e9');
</script>

<template>
    <div class="bg-base-100 shadow-sm rounded-lg border border-base-300 overflow-hidden">
        <div class="p-6 border-b border-base-300">
            <h2 class="text-lg font-bold text-base-content">Maintenance Mode</h2>
            <p class="mt-1 text-sm text-base-content/50">Control system availability and maintenance settings.</p>
        </div>
        
        <div class="p-6 space-y-6">
            <!-- Maintenance Mode Toggle -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                <div class="md:col-span-2">
                    <label class="block text-sm font-bold text-base-content/70">Maintenance Mode</label>
                    <p class="text-xs text-base-content/50 mt-1">{{ maintenanceDescriptions.maintenance_mode }}</p>
                </div>
                <div class="flex items-center">
                    <input 
                        type="checkbox" 
                        class="toggle border-2 border-base-300 shadow-sm"
                        :style="form.maintenance_mode ? { backgroundColor: primaryColor, borderColor: primaryColor } : {}"
                        v-model="form.maintenance_mode"
                    />
                </div>
            </div>

            <!-- Maintenance Message -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">
                <div class="md:col-span-2">
                    <label class="block text-sm font-bold text-base-content/70">Maintenance Message</label>
                    <p class="text-xs text-base-content/50 mt-1">{{ maintenanceDescriptions.maintenance_message }}</p>
                </div>
                <div>
                    <textarea
                        v-model="form.maintenance_message"
                        rows="3"
                        class="block w-full rounded-md border-base-300 bg-base-100 text-base-content shadow-sm focus:border-primary focus:ring-primary sm:text-sm px-3 py-2 border"
                    />
                </div>
            </div>

            <!-- Central Update Server URL -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center pt-6 border-t border-base-200">
                <div class="md:col-span-2">
                    <label class="block text-sm font-bold text-base-content/70">Central Update Server (Laptop A URL)</label>
                    <p class="text-xs text-base-content/50 mt-1">{{ maintenanceDescriptions.central_api_url }}</p>
                </div>
                <div>
                    <input
                        type="url"
                        v-model="form.central_api_url"
                        placeholder="https://laptop-a-ngrok.io"
                        class="block w-full rounded-md border-base-300 bg-base-100 text-base-content shadow-sm focus:border-primary focus:ring-primary sm:text-sm px-3 py-2 border"
                    />
                </div>
            </div>
        </div>

        <div class="px-6 py-4 bg-base-200/50 border-t border-base-300 flex justify-end">
            <button
                @click="saveGroup"
                class="btn btn-error btn-sm text-white"
            >
                Save Maintenance Settings
            </button>
        </div>
    </div>
</template>
