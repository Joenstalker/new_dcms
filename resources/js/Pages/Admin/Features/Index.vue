<script setup>
import { ref, computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FeatureFormModal from './Partials/FeatureFormModal.vue';
import FeatureViewModal from './Partials/FeatureViewModal.vue';
import FeatureList from './Partials/FeatureList.vue';
import BatchProgressBar from '@/Components/BatchProgressBar.vue';
import axios from 'axios';
import Swal from 'sweetalert2';
const props = defineProps({
    features: Object,
    plans: Array,
});

const showModal = ref(false);
const showViewModal = ref(false);
const editingFeature = ref(null);
const viewingFeature = ref(null);
const currentBatchId = ref(null);
const isSyncing = ref(false);

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
    Swal.fire({
        target: document.querySelector('dialog[open]') || 'body',
        title: editingFeature.value ? 'Updating Feature' : 'Creating Feature',
        text: 'Please wait...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    if (editingFeature.value) {
        form.put(`/admin/features/${editingFeature.value.id}`, {
            onSuccess: () => {
                closeModal();
                Swal.close();
            },
            onError: () => Swal.close(),
        });
    } else {
        form.post('/admin/features', {
            onSuccess: () => {
                closeModal();
                Swal.close();
            },
            onError: () => Swal.close(),
        });
    }
};

const deleteFeature = (feature) => {
    Swal.fire({
        title: 'Delete Feature?',
        text: `Are you sure you want to delete "${feature.name}"? This will remove it from all subscription plans.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444', // error
        cancelButtonColor: '#94a3b8', // slate-400
        confirmButtonText: 'Yes, delete it',
    }).then((result) => {
        if (result.isConfirmed) {
            form.delete(`/admin/features/${feature.id}`, { 
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        title: 'Deleted!',
                        text: 'Feature has been removed.',
                        icon: 'success',
                        timer: 2000,
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                    });
                }
            });
        }
    });
};

const toggleFeature = (feature) => {
    const action = feature.is_active ? 'Deactivate' : 'Activate';
    Swal.fire({
        title: `${action} Feature?`,
        text: `Are you sure you want to ${action.toLowerCase()} "${feature.name}"?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: feature.is_active ? '#ef4444' : '#0ea5e9',
        confirmButtonText: `Yes, ${action.toLowerCase()} it`,
    }).then((result) => {
        if (result.isConfirmed) {
            form.put(`/admin/features/${feature.id}`, {
                is_active: !feature.is_active,
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        title: 'Updated!',
                        text: `Feature has been ${action.toLowerCase()}d.`,
                        icon: 'success',
                        timer: 2000,
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                    });
                }
            });
        }
    });
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

const syncAllUpdates = () => {
    Swal.fire({
        title: 'Push OTA Synchronization?',
        text: 'This will trigger a bulk push for all active features to all eligible tenants. This process runs in the background.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0ea5e9', // primary
        confirmButtonText: 'Yes, start sync',
    }).then(async (result) => {
        if (result.isConfirmed) {
            isSyncing.value = true;
            try {
                const response = await axios.post(route('admin.features.sync-all'));
                if (response.data.batch_id) {
                    currentBatchId.value = response.data.batch_id;
                    Swal.fire({
                        title: 'Sync Started',
                        text: 'The background process is now running.',
                        icon: 'info',
                        timer: 2000,
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                    });
                } else {
                    window.location.reload();
                }
            } catch (error) {
                console.error('Sync failed:', error);
                Swal.fire('Error!', 'Failed to start bulk synchronization.', 'error');
                isSyncing.value = false;
            }
        }
    });
};

const handleBatchFinished = () => {
    isSyncing.value = false;
    Swal.fire({
        title: 'Synchronization Complete',
        text: 'All tenants have been updated with the latest features.',
        icon: 'success',
        confirmButtonColor: '#0ea5e9',
    });
};
</script>

<template>
    <Head title="Feature Management" />

    <AdminLayout>
        <template #header>
            <h2 class="font-bold text-xl text-base-content leading-tight">Feature Management</h2>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                    <p class="text-sm text-base-content/70">
                        Manage subscription plan features dynamically. Add, edit, or remove features.
                    </p>
                    <button
                        @click="openCreateModal"
                        class="btn btn-primary btn-sm shrink-0"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Feature
                    </button>
                    <button
                        @click="syncAllUpdates"
                        :disabled="isSyncing"
                        class="btn btn-secondary btn-sm shrink-0"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        {{ isSyncing ? 'Syncing...' : 'Sync All Updates' }}
                    </button>
                </div>

                <!-- Batch Progress Bar -->
                <BatchProgressBar 
                    v-if="currentBatchId" 
                    :batch-id="currentBatchId" 
                    title="Bulk OTA Feature Synchronization"
                    @finished="handleBatchFinished"
                />

                <!-- Features by Category -->
                <FeatureList 
                    :features-by-category="featuresByCategory"
                    :category-labels="categoryLabels"
                    :plans="plans"
                    :get-type-label="getTypeLabel"
                    @view="openViewModal"
                    @edit="openEditModal"
                    @delete="deleteFeature"
                    @toggle="toggleFeature"
                />
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <FeatureFormModal
            :show="showModal"
            :editing-feature="editingFeature"
            :form="form"
            @close="closeModal"
            @submit="submitForm"
        />

        <!-- View Modal -->
        <FeatureViewModal
            :show="showViewModal"
            :feature="viewingFeature"
            :plans="plans"
            :get-type-label="getTypeLabel"
            :get-feature-value-for-plan="getFeatureValueForPlan"
            :format-feature-value="formatFeatureValue"
            :category-labels="categoryLabels"
            @close="closeViewModal"
        />
    </AdminLayout>
</template>
