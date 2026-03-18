<script setup>
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
};

const saveGroup = () => {
    emit('save', 'maintenance');
};
</script>

<template>
    <div class="bg-white shadow-sm rounded-lg border border-gray-200">
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
                @click="saveGroup"
                class="bg-teal-600 hover:bg-teal-700 text-white px-4 py-2 rounded-lg text-sm font-medium shadow-sm transition-colors"
            >
                Save Maintenance Settings
            </button>
        </div>
    </div>
</template>
