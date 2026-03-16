<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    features: Object,
    plans: Array,
});

const page = usePage();
const showModal = ref(false);
const showViewModal = ref(false);
const editingFeature = ref(null);
const viewingFeature = ref(null);

const categoryLabels = {
    core: 'Core Features',
    limits: 'Limits',
    addons: 'Add-ons',
    reports: 'Reports & Analytics',
    expansion: 'Expansion',
};

const categoryOrder = ['core', 'limits', 'addons', 'reports', 'expansion'];

const form = useForm({
    key: '',
    name: '',
    description: '',
    type: 'boolean',
    category: 'core',
    options: [],
    sort_order: 0,
    is_active: true,
});

const openCreateModal = () => {
    editingFeature.value = null;
    form.reset();
    form.key = '';
    form.name = '';
    form.description = '';
    form.type = 'boolean';
    form.category = 'core';
    form.options = [];
    form.sort_order = 0;
    form.is_active = true;
    showModal.value = true;
};

const openEditModal = (feature) => {
    editingFeature.value = feature;
    form.key = feature.key;
    form.name = feature.name;
    form.description = feature.description || '';
    form.type = feature.type;
    form.category = feature.category || 'core';
    form.options = feature.options || [];
    form.sort_order = feature.sort_order;
    form.is_active = feature.is_active;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingFeature.value = null;
};

const openViewModal = (feature) => {
    viewingFeature.value = feature;
    showViewModal.value = true;
};

const closeViewModal = () => {
    showViewModal.value = false;
    viewingFeature.value = null;
};

const getFeatureValueForPlan = (feature, plan) => {
    const planFeature = feature.plans?.find(p => p.id === plan.id);
    if (!planFeature) return null;
    
    if (feature.type === 'boolean') return planFeature.pivot?.value_boolean;
    if (feature.type === 'numeric') return planFeature.pivot?.value_numeric;
    if (feature.type === 'tiered') return planFeature.pivot?.value_tier;
    return null;
};

const formatFeatureValue = (feature, value) => {
    if (feature.type === 'boolean') {
        return value ? '✓ Enabled' : '✗ Disabled';
    }
    if (feature.type === 'numeric') {
        return value === null ? 'Unlimited' : value.toLocaleString();
    }
    if (feature.type === 'tiered') {
        return value ? value.charAt(0).toUpperCase() + value.slice(1) : 'None';
    }
    return value;
};

const submitForm = () => {
    if (editingFeature.value) {
        form.put(`/admin/features/${editingFeature.value.id}`, {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post('/admin/features', {
            onSuccess: () => closeModal(),
        });
    }
};

const getTypeLabel = (type) => {
    return {
        boolean: 'Yes/No',
        numeric: 'Number',
        tiered: 'Tiered',
    }[type] || type;
};

const featuresByCategory = computed(() => {
    const grouped = {};
    categoryOrder.forEach(cat => {
        if (props.features[cat]) {
            grouped[cat] = props.features[cat];
        }
    });
    return grouped;
});
</script>

<template>
    <Head title="Feature Management" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Feature Management</h2>
                    <p class="mt-1 text-sm text-gray-500">
                        Manage subscription plan features dynamically. Add, edit, or remove features.
                    </p>
                </div>
                <button
                    @click="openCreateModal"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-medium text-sm text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Feature
                </button>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Features by Category -->
                <div class="space-y-8">
                    <div v-for="(features, category) in featuresByCategory" :key="category">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800 mr-3">
                                {{ categoryLabels[category] || category }}
                            </span>
                            <span class="text-gray-500 font-normal text-sm">{{ features.length }} features</span>
                        </h3>
                        
                        <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl overflow-hidden">
                            <ul role="list" class="divide-y divide-gray-100">
                                <li v-for="feature in features" :key="feature.id" class="px-6 py-5 hover:bg-gray-50 transition-colors">
                                    <div class="flex items-center justify-between gap-x-6">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-x-3">
                                                <p class="text-sm font-semibold leading-6 text-gray-900">
                                                    {{ feature.name }}
                                                </p>
                                                <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">
                                                    {{ feature.key }}
                                                </span>
                                                <span 
                                                    class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset"
                                                    :class="{
                                                        'bg-blue-50 text-blue-700 ring-blue-700/10': feature.type === 'boolean',
                                                        'bg-emerald-50 text-emerald-700 ring-emerald-600/10': feature.type === 'numeric',
                                                        'bg-purple-50 text-purple-700 ring-purple-700/10': feature.type === 'tiered',
                                                    }"
                                                >
                                                    {{ getTypeLabel(feature.type) }}
                                                </span>
                                                <span 
                                                    v-if="!feature.is_active" 
                                                    class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/10"
                                                >
                                                    Inactive
                                                </span>
                                            </div>
                                            <div class="mt-1 flex items-center gap-x-2 text-sm leading-5 text-gray-500">
                                                <p v-if="feature.description" class="truncate">{{ feature.description }}</p>
                                                <svg v-if="feature.description && feature.options?.length" viewBox="0 0 2 2" class="h-0.5 w-0.5 fill-current"><circle cx="1" cy="1" r="1" /></svg>
                                                <p v-if="feature.options && feature.options.length">Options: {{ feature.options.join(', ') }}</p>
                                            </div>
                                        </div>
                                        <div class="flex flex-none items-center gap-x-2">
                                            <button
                                                @click="openViewModal(feature)"
                                                class="rounded-md bg-white px-2.5 py-1.5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 transition-colors"
                                            >
                                                View
                                            </button>
                                            <button
                                                @click="openEditModal(feature)"
                                                class="rounded-md bg-white px-2.5 py-1.5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 transition-colors"
                                            >
                                                Edit
                                            </button>
                                            <button
                                                @click="form.delete(`/admin/features/${feature.id}`, { preserveScroll: true })"
                                                class="rounded-md bg-white px-2.5 py-1.5 text-sm font-semibold text-red-600 shadow-sm ring-1 ring-inset ring-red-300 hover:bg-red-50 transition-colors"
                                            >
                                                Delete
                                            </button>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Plans Section -->
                <div class="mt-12 bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl p-6 lg:p-8">
                    <div class="sm:flex sm:items-center sm:justify-between mb-6">
                        <div>
                            <h3 class="text-base font-semibold leading-6 text-gray-900">Subscription Plans Overview</h3>
                            <p class="mt-2 text-sm text-gray-500">A quick glance at how features map to your current pricing tiers.</p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div v-for="plan in plans" :key="plan.id" class="rounded-2xl border border-gray-200 p-6 shadow-sm flex flex-col justify-between hover:border-indigo-300 transition-colors">
                            <div>
                                <h4 class="text-lg font-semibold leading-8 text-gray-900">{{ plan.name }}</h4>
                                <p class="mt-4 flex items-baseline gap-x-2">
                                    <span class="text-3xl font-bold tracking-tight text-gray-900">₱{{ plan.price_monthly }}</span>
                                    <span class="text-sm font-semibold leading-6 text-gray-500">/mo</span>
                                </p>
                            </div>
                            <div class="mt-6">
                                <Link
                                    :href="`/admin/plans/${plan.id}/edit`"
                                    class="text-indigo-600 hover:text-indigo-500 text-sm font-semibold leading-6 inline-flex items-center gap-1"
                                >
                                    Manage Plan Features <span aria-hidden="true">&rarr;</span>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <Modal :show="showModal" @close="closeModal">
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
                            @click="closeModal"
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

        <!-- View Modal -->
        <Modal :show="showViewModal" @close="closeViewModal" maxWidth="lg">
            <div v-if="viewingFeature" class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-gray-900">
                        {{ viewingFeature.name }}
                    </h3>
                    <button @click="closeViewModal" class="text-gray-400 hover:text-gray-500">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Feature Details -->
                <div class="bg-gray-50 rounded-lg p-4 mb-6">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Key</p>
                            <p class="text-sm font-medium text-gray-900">{{ viewingFeature.key }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Type</p>
                            <p class="text-sm font-medium text-gray-900">{{ getTypeLabel(viewingFeature.type) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Category</p>
                            <p class="text-sm font-medium text-gray-900">{{ categoryLabels[viewingFeature.category] || viewingFeature.category }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Status</p>
                            <span 
                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                :class="viewingFeature.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                            >
                                {{ viewingFeature.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                    </div>
                    <div v-if="viewingFeature.description" class="mt-4">
                        <p class="text-xs text-gray-500 uppercase">Description</p>
                        <p class="text-sm text-gray-700">{{ viewingFeature.description }}</p>
                    </div>
                    <div v-if="viewingFeature.options && viewingFeature.options.length" class="mt-4">
                        <p class="text-xs text-gray-500 uppercase">Options</p>
                        <p class="text-sm text-gray-700">{{ viewingFeature.options.join(', ') }}</p>
                    </div>
                </div>

                <!-- Plan Assignments Preview -->
                <h4 class="text-sm font-semibold text-gray-900 mb-4">Plan Assignments Preview</h4>
                <div class="space-y-3">
                    <div 
                        v-for="plan in plans" 
                        :key="plan.id"
                        class="flex items-center justify-between p-3 rounded-lg border"
                        :class="getFeatureValueForPlan(viewingFeature, plan) ? 'bg-green-50 border-green-200' : 'bg-gray-50 border-gray-200'"
                    >
                        <div class="flex items-center gap-3">
                            <div 
                                class="h-8 w-8 rounded-full flex items-center justify-center"
                                :class="getFeatureValueForPlan(viewingFeature, plan) ? 'bg-green-100 text-green-600' : 'bg-gray-200 text-gray-500'"
                            >
                                <svg v-if="getFeatureValueForPlan(viewingFeature, plan)" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <svg v-else class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ plan.name }}</p>
                                <p class="text-xs text-gray-500">
                                    ₱{{ plan.price_monthly }}/month
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p 
                                class="text-sm font-medium"
                                :class="getFeatureValueForPlan(viewingFeature, plan) ? 'text-green-700' : 'text-gray-500'"
                            >
                                {{ formatFeatureValue(viewingFeature, getFeatureValueForPlan(viewingFeature, plan)) }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Tenant Preview Hint -->
                <div class="mt-6 p-4 bg-indigo-50 rounded-lg border border-indigo-100">
                    <div class="flex items-start gap-3">
                        <svg class="h-5 w-5 text-indigo-600 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-indigo-900">How tenants will see this</p>
                            <p class="text-xs text-indigo-700 mt-1">
                                Tenants on plans with this feature enabled will see it in their Settings > Features page with a green checkmark. Disabled features will appear grayed out with an upgrade prompt.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button
                        @click="closeViewModal"
                        class="px-4 py-2 bg-indigo-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    >
                        Close
                    </button>
                </div>
            </div>
        </Modal>
    </AdminLayout>
</template>
