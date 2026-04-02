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
    featuresByCategory: {
        type: Object,
        default: () => ({}),
    },
    primaryColor: {
        type: String,
        default: '#0ea5e9',
    },
});

import { computed } from 'vue';

const categoryFeatures = computed(() => {
    let feats = props.featuresByCategory[props.form.category] || [];
    // If we're editing, don't show the feature itself in its own "Place After" list
    if (props.editingFeature) {
        feats = feats.filter(f => f.id !== props.editingFeature.id);
    }
    // Sort them exactly as they appear structurally
    return [...feats].sort((a,b) => a.sort_order - b.sort_order);
});

const emit = defineEmits(['close', 'submit', 'delete', 'toggle']);

const submitForm = () => {
    emit('submit');
};
</script>

<template>
    <Modal :show="show" @close="emit('close')">
        <div class="p-6 bg-base-100 border border-base-300 rounded-lg">
            <h3 class="text-xl font-bold text-base-content mb-6">
                {{ editingFeature ? 'Manage Feature' : 'Create New Feature' }}
            </h3>

            <div v-if="editingFeature" class="bg-base-200/50 p-4 rounded-xl border border-base-200 mb-6 shadow-sm">
                <div class="font-black text-base-content text-lg">{{ editingFeature.name }}</div>
                <div class="text-xs text-base-content/50 font-mono tracking-wider">{{ editingFeature.key }}</div>
            </div>
            
            <form @submit.prevent="submitForm" class="space-y-6">
                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-bold text-base-content/70">Feature Key</span>
                    </label>
                    <input
                        v-model="form.key"
                        type="text"
                        :disabled="!!editingFeature"
                        class="input input-bordered w-full"
                        :class="{ 'bg-base-200 cursor-not-allowed': editingFeature }"
                        placeholder="e.g., sms_notifications"
                    />
                    <p class="mt-1 text-xs text-base-content/50">Unique identifier (lowercase, underscores only)</p>
                    <p v-if="form.errors.key" class="mt-1 text-sm text-error font-medium">{{ form.errors.key }}</p>
                </div>

                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-bold text-base-content/70">Display Name</span>
                    </label>
                    <input
                        v-model="form.name"
                        type="text"
                        class="input input-bordered w-full"
                        placeholder="e.g., SMS Notifications"
                    />
                    <p v-if="form.errors.name" class="mt-1 text-sm text-error font-medium">{{ form.errors.name }}</p>
                </div>

                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-bold text-base-content/70">Description</span>
                    </label>
                    <textarea
                        v-model="form.description"
                        rows="2"
                        class="textarea textarea-bordered w-full"
                        placeholder="Optional description"
                    />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-bold text-base-content/70">Type</span>
                        </label>
                        <select
                            v-model="form.type"
                            class="select select-bordered w-full"
                        >
                            <option value="boolean">Yes/No (Boolean)</option>
                            <option value="numeric">Number (Numeric)</option>
                            <option value="tiered">Tiered (Levels)</option>
                        </select>
                    </div>

                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-bold text-base-content/70">Category</span>
                        </label>
                        <select
                            v-model="form.category"
                            class="select select-bordered w-full"
                        >
                            <option value="core">Core Features</option>
                            <option value="limits">Limits</option>
                            <option value="addons">Add-ons</option>
                            <option value="reports">Reports & Analytics</option>
                            <option value="expansion">Expansion</option>
                        </select>
                    </div>
                </div>

                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-bold text-base-content/70">Implementation Status</span>
                    </label>
                    <select
                        v-model="form.implementation_status"
                        class="select select-bordered w-full"
                    >
                        <option value="coming_soon">Coming Soon</option>
                        <option value="in_development">In Development</option>
                        <option value="beta">Beta</option>
                        <option value="active">Active</option>
                        <option value="deprecated">Deprecated</option>
                    </select>
                    <p class="mt-1 text-xs text-base-content/50">Overrides system routing based on development stage.</p>
                </div>

                <div v-if="form.type === 'tiered'" class="form-control">
                    <label class="label">
                        <span class="label-text font-bold text-base-content/70">Tier Options</span>
                    </label>
                    <input
                        v-model="form.options"
                        type="text"
                        class="input input-bordered w-full"
                        placeholder="Comma-separated: basic, enhanced, advanced"
                    />
                    <p class="mt-1 text-xs text-base-content/50">Enter options separated by commas</p>
                </div>

                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-bold text-base-content/70">Placement (Sort Order)</span>
                    </label>
                    <select
                        v-model.number="form.sort_order"
                        class="select select-bordered w-full"
                    >
                        <option v-if="editingFeature && !categoryFeatures.find(f => f.sort_order + 1 === form.sort_order) && form.sort_order !== 0 && form.sort_order !== 99" :value="form.sort_order">
                            Current Position ({{ form.sort_order }})
                        </option>
                        <option :value="0">Top of list (First)</option>
                        <option 
                            v-for="feat in categoryFeatures" 
                            :key="feat.id" 
                            :value="feat.sort_order + 1"
                        >
                            After {{ feat.name }}
                        </option>
                        <option :value="99">Bottom of list (Last)</option>
                    </select>
                    <p class="mt-1 text-xs text-base-content/50">Select where this item belongs globally.</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label cursor-pointer justify-start gap-4">
                            <input
                                v-model="form.is_active"
                                type="checkbox"
                                class="checkbox checkbox-primary"
                            />
                            <span class="label-text font-bold text-base-content/70">System Active</span>
                        </label>
                    </div>

                    <div class="form-control" v-if="!editingFeature">
                        <label class="label cursor-pointer justify-start gap-4">
                            <input
                                v-model="form.notify_tenants"
                                type="checkbox"
                                class="checkbox checkbox-secondary"
                            />
                            <span class="label-text font-bold text-base-content/70">Show in Tenant Updates Page</span>
                        </label>
                    </div>
                </div>

                <div class="flex items-center justify-between w-full mt-8 pt-4 border-t border-base-200">
                    <div v-if="editingFeature" class="flex gap-2">
                        <button 
                            type="button"
                            @click="emit('toggle', editingFeature)" 
                            class="btn btn-sm" 
                            :class="editingFeature.is_active ? 'btn-outline btn-error hover:text-white' : 'btn-outline btn-success hover:text-white'"
                        >
                            {{ editingFeature.is_active ? 'Deactivate' : 'Activate' }}
                        </button>
                        <button 
                            type="button"
                            @click="emit('delete', editingFeature)" 
                            class="btn btn-ghost hover:bg-error text-error hover:text-white btn-sm"
                        >
                            Delete
                        </button>
                    </div>
                    <div v-else></div>

                    <div class="flex gap-3">
                        <button
                            type="button"
                            @click="emit('close')"
                            class="btn btn-ghost btn-sm font-bold hover:bg-base-200"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="btn btn-sm font-bold text-white border-none shadow-md hover:opacity-90 transition-opacity"
                            :style="{ backgroundColor: primaryColor }"
                        >
                            {{ editingFeature ? 'Update Feature' : 'Create Feature' }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </Modal>
</template>
