<script setup>
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    show: {
        type: Boolean,
        required: true,
    },
    editingFeature: {
        type: Object,
        default: null,
    },
    form: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['close', 'submit']);

const submitForm = () => {
    emit('submit');
};
</script>

<template>
    <Modal :show="show" @close="emit('close')">
        <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">
                {{ editingFeature ? 'Edit Feature' : 'Create New Feature' }}
            </h3>
            
            <form @submit.prevent="submitForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Feature Key</label>
                    <input
                        v-model="form.key"
                        type="text"
                        :disabled="!!editingFeature"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        :class="{ 'bg-gray-100': editingFeature }"
                        placeholder="e.g., sms_notifications"
                    />
                    <p class="mt-1 text-xs text-gray-500">Unique identifier (lowercase, underscores only)</p>
                    <p v-if="form.errors.key" class="mt-1 text-sm text-red-600">{{ form.errors.key }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Display Name</label>
                    <input
                        v-model="form.name"
                        type="text"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        placeholder="e.g., SMS Notifications"
                    />
                    <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea
                        v-model="form.description"
                        rows="2"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        placeholder="Optional description"
                    />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Type</label>
                        <select
                            v-model="form.type"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        >
                            <option value="boolean">Yes/No (Boolean)</option>
                            <option value="numeric">Number (Numeric)</option>
                            <option value="tiered">Tiered (Levels)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Category</label>
                        <select
                            v-model="form.category"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        >
                            <option value="core">Core Features</option>
                            <option value="limits">Limits</option>
                            <option value="addons">Add-ons</option>
                            <option value="reports">Reports & Analytics</option>
                            <option value="expansion">Expansion</option>
                        </select>
                    </div>
                </div>

                <div v-if="form.type === 'tiered'">
                    <label class="block text-sm font-medium text-gray-700">Tier Options</label>
                    <input
                        v-model="form.options"
                        type="text"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        placeholder="Comma-separated: basic, enhanced, advanced"
                    />
                    <p class="mt-1 text-xs text-gray-500">Enter options separated by commas</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Sort Order</label>
                    <input
                        v-model.number="form.sort_order"
                        type="number"
                        min="0"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    />
                </div>

                <div class="flex items-center">
                    <input
                        v-model="form.is_active"
                        type="checkbox"
                        id="is_active"
                        class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                    />
                    <label for="is_active" class="ml-2 block text-sm text-gray-900">Active</label>
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <button
                        type="button"
                        @click="emit('close')"
                        class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-4 py-2 bg-indigo-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
                    >
                        {{ editingFeature ? 'Update' : 'Create' }}
                    </button>
                </div>
            </form>
        </div>
    </Modal>
</template>
