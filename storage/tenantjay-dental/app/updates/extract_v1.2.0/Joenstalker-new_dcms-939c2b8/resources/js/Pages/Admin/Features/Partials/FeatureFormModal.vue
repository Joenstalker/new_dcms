<script setup>
import Modal from '@/Components/Modal.vue';
import { ref, computed } from 'vue';

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

const activeTab = ref('general');

const categoryFeatures = computed(() => {
    let feats = props.featuresByCategory[props.form.category] || [];
    // If we're editing, don't show the feature itself in its own "Place After" list
    if (props.editingFeature) {
        feats = feats.filter(f => f.id !== props.editingFeature.id);
    }
    // Sort them exactly as they appear structurally
    return [...feats].sort((a,b) => a.sort_order - b.sort_order);
});

const emit = defineEmits(['close', 'submit', 'delete', 'toggle', 'archive', 'restore']);

const submitForm = () => {
    emit('submit');
};
</script>

<template>
    <Modal :show="show" @close="emit('close')">
        <div class="p-0 bg-base-100 border border-base-300 rounded-lg overflow-hidden flex flex-col max-h-[90vh]">
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-base-200 flex justify-between items-center bg-base-200/30 shrink-0">
                <h3 class="text-xl font-bold text-base-content">
                    {{ editingFeature ? 'Manage Feature' : 'Create New Feature' }}
                </h3>
                <button @click="emit('close')" class="btn btn-sm btn-ghost btn-circle">✕</button>
            </div>

            <!-- Context Info (Only when editing) -->
            <div v-if="editingFeature" class="px-6 py-4 border-b border-base-200 bg-base-50 flex items-center gap-4 shrink-0">
                <div class="badge badge-lg py-4 px-6 gap-2 border-base-300 bg-white" :style="{ color: primaryColor }">
                    <span class="font-black">{{ editingFeature.name }}</span>
                    <span class="text-xs font-mono opacity-50">{{ editingFeature.key }}</span>
                </div>
                <div v-if="editingFeature.archived_at" class="badge badge-warning font-bold gap-1 mt-1">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" /></svg>
                    Archived
                </div>
            </div>

            <!-- Tabs Navigation -->
            <div class="tabs tabs-lifted px-6 pt-4 bg-base-100 border-b border-base-200 shrink-0">
                <button 
                    type="button"
                    @click="activeTab = 'general'"
                    class="tab tab-bordered font-bold" 
                    :class="{ 'tab-active border-primary text-primary': activeTab === 'general' }"
                >
                    General
                </button>
                <button 
                    type="button"
                    @click="activeTab = 'config'"
                    class="tab tab-bordered font-bold" 
                    :class="{ 'tab-active border-primary text-primary': activeTab === 'config' }"
                >
                    Configuration
                </button>
                <button 
                    type="button"
                    @click="activeTab = 'status'"
                    class="tab tab-bordered font-bold" 
                    :class="{ 'tab-active border-primary text-primary': activeTab === 'status' }"
                >
                    Status
                </button>
            </div>
            
            <form @submit.prevent="submitForm" class="flex flex-col min-h-0">
                <!-- Main Content Area -->
                <div class="overflow-y-auto p-6 min-h-0">
                    <!-- Tab Content: General -->
                    <div v-show="activeTab === 'general'" class="space-y-5 animate-in fade-in slide-in-from-bottom-2 duration-300">
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
                                placeholder="e.g., ai_diagnosis"
                            />
                            <p v-if="form.errors.key" class="mt-1 text-sm text-error font-medium">{{ form.errors.key }}</p>
                            <p class="mt-1 text-xs text-base-content/50">Unique identifier (lowercase and underscores only)</p>
                        </div>

                        <div class="form-control">
                            <label class="label">
                                <span class="label-text font-bold text-base-content/70">Display Name</span>
                            </label>
                            <input
                                v-model="form.name"
                                type="text"
                                class="input input-bordered w-full"
                                placeholder="e.g., AI Diagnosis Engine"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-sm text-error font-medium">{{ form.errors.name }}</p>
                        </div>

                        <div class="form-control">
                            <label class="label">
                                <span class="label-text font-bold text-base-content/70">Description</span>
                            </label>
                            <textarea
                                v-model="form.description"
                                rows="3"
                                class="textarea textarea-bordered w-full"
                                placeholder="Explain what this feature does..."
                            />
                        </div>
                    </div>

                    <!-- Tab Content: Configuration -->
                    <div v-show="activeTab === 'config'" class="space-y-5 animate-in fade-in slide-in-from-bottom-2 duration-300">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text font-bold text-base-content/70">Type</span>
                                </label>
                                <select v-model="form.type" class="select select-bordered w-full themed-select">
                                    <option value="boolean">Yes/No (Boolean)</option>
                                    <option value="numeric">Number (Numeric)</option>
                                    <option value="tiered">Tiered (Levels)</option>
                                </select>
                            </div>

                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text font-bold text-base-content/70">Category</span>
                                </label>
                                <select v-model="form.category" class="select select-bordered w-full themed-select">
                                    <option value="core">Core Features</option>
                                    <option value="limits">Limits</option>
                                    <option value="addons">Add-ons</option>
                                    <option value="reports">Reports & Analytics</option>
                                    <option value="expansion">Expansion</option>
                                </select>
                            </div>
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
                            <p class="mt-1 text-xs text-base-content/50">Enter possible tier values separated by commas</p>
                        </div>

                        <div class="form-control">
                            <label class="label">
                                <span class="label-text font-bold text-base-content/70">Placement (Sort Order)</span>
                            </label>
                            <select v-model.number="form.sort_order" class="select select-bordered w-full themed-select">
                                <option :value="0">Top of list (First)</option>
                                <option v-for="feat in categoryFeatures" :key="feat.id" :value="feat.sort_order + 1">
                                    After {{ feat.name }}
                                </option>
                                <option :value="99">Bottom of list (Last)</option>
                            </select>
                            <p class="mt-1 text-xs text-base-content/50">Determines the display sequence in the system.</p>
                        </div>
                    </div>

                    <!-- Tab Content: Status -->
                    <div v-show="activeTab === 'status'" class="space-y-6 animate-in fade-in slide-in-from-bottom-2 duration-300">
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text font-bold text-base-content/70">Implementation Phase</span>
                            </label>
                            <select v-model="form.implementation_status" class="select select-bordered w-full themed-select">
                                <option value="coming_soon">Coming Soon</option>
                                <option value="in_development">In Development</option>
                                <option value="beta">Beta</option>
                                <option value="active">Active</option>
                                <option value="deprecated">Deprecated</option>
                                <option value="maintenance">Under Maintenance</option>
                            </select>
                            <p class="mt-1 text-xs text-base-content/50">Controls how the feature is perceived by the routing engine.</p>

                            <div v-if="editingFeature" class="mt-3 rounded-lg border border-warning/30 bg-warning/10 px-3 py-2 text-xs text-base-content/80">
                                Setting a feature back to <span class="font-semibold">Coming Soon</span> now starts a fresh OTA rollout cycle.
                                Existing tenant update records for this feature are reset to <span class="font-semibold">Pending</span>, so tenants will receive it again as a new update.
                            </div>
                        </div>

                        <div class="bg-base-200/30 p-4 rounded-xl border border-base-200 space-y-4">
                            <label class="label cursor-pointer justify-start gap-4">
                                <input v-model="form.is_active" type="checkbox" class="checkbox checkbox-primary" />
                                <div>
                                    <span class="label-text font-bold text-base-content">System Active</span>
                                    <p class="text-xs text-base-content/50">Master switch for feature availability platform-wide.</p>
                                </div>
                            </label>

                            <div v-if="!editingFeature || !editingFeature.archived_at" class="divider my-0 opacity-50"></div>

                            <label v-if="!editingFeature" class="label cursor-pointer justify-start gap-4">
                                <input v-model="form.notify_tenants" type="checkbox" class="checkbox checkbox-secondary" />
                                <div>
                                    <span class="label-text font-bold text-base-content">Notify Tenants Immediately</span>
                                    <p class="text-xs text-base-content/50">Automatically push this as an OTA update upon creation.</p>
                                </div>
                            </label>
                        </div>

                        <div v-if="editingFeature?.archived_at" class="alert alert-warning shadow-sm py-2">
                            <svg class="stroke-current shrink-0 h-4 w-4" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                            <span class="text-xs font-bold uppercase tracking-tight">Feature is Archived</span>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="flex items-center justify-between w-full px-6 py-4 bg-base-200/30 border-t border-base-200 shrink-0">
                    <div v-if="editingFeature" class="flex gap-2">
                        <button 
                            v-if="!editingFeature.archived_at"
                            type="button"
                            @click="emit('archive', editingFeature)" 
                            class="btn btn-sm btn-ghost text-warning hover:bg-warning hover:text-white font-bold"
                        >
                            Archive
                        </button>
                        
                        <button 
                            v-if="editingFeature.archived_at"
                            type="button"
                            @click="emit('restore', editingFeature)" 
                            class="btn btn-sm btn-success text-white font-bold"
                        >
                            Restore
                        </button>
                        
                        <button 
                            v-if="editingFeature.archived_at"
                            type="button"
                            @click="emit('delete', editingFeature)" 
                            class="btn btn-sm btn-ghost hover:bg-error text-error hover:text-white font-bold"
                        >
                            Delete
                        </button>
                    </div>
                    <div v-else></div>

                    <div class="flex gap-3">
                        <button
                            type="button"
                            @click="emit('close')"
                            class="btn btn-ghost btn-sm font-bold"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="btn btn-sm font-bold text-white border-none shadow-md px-6 min-w-[120px]"
                            :style="{ backgroundColor: primaryColor }"
                        >
                            {{ editingFeature ? 'Save Changes' : 'Create Feature' }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </Modal>
</template>

<style scoped>
.themed-select {
    background-color: hsl(var(--b1));
    color: hsl(var(--bc));
}

.themed-select option,
.themed-select optgroup {
    background-color: hsl(var(--b1));
    color: hsl(var(--bc));
}

.themed-select option:checked,
.themed-select option:hover {
    background-color: hsl(var(--b2));
    color: hsl(var(--bc));
}
</style>
