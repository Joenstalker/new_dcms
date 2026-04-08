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
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const branding = computed(() => page.props.branding || {});
const primaryColor = computed(() => branding.value.primary_color || '#0ea5e9');

const props = defineProps({
    features: Object,
    archivedFeatures: Array,
    releaseFeatures: {
        type: Array,
        default: () => [],
    },
    plans: Array,
});

const showModal = ref(false);
const showViewModal = ref(false);
const editingFeature = ref(null);
const viewingFeature = ref(null);
const currentBatchId = ref(null);
const isSyncing = ref(false);
const activeTab = ref('live');

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
    implementation_status: 'coming_soon',
    notify_tenants: false,
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
    form.implementation_status = 'coming_soon';
    form.notify_tenants = true; // Auto-checked on create to show in updates
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
    form.implementation_status = feature.implementation_status || 'coming_soon';
    form.notify_tenants = false; // By default don't re-notify on edit unless explicit
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
            form.put(`/admin/features/${feature.id}/toggle-active`, {
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

const archiveFeature = (feature) => {
    Swal.fire({
        title: 'Archive Feature?',
        text: `Archiving "${feature.name}" will hide it from all tenants and subscription plans. It can only be deleted from the archive.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#f59e0b', // amber-500
        confirmButtonText: 'Yes, archive it',
    }).then((result) => {
        if (result.isConfirmed) {
            form.post(route('admin.features.archive', feature.id), {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        title: 'Archived!',
                        text: 'Feature moved to archive.',
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

const restoreFeature = (feature) => {
    Swal.fire({
        title: 'Restore Feature?',
        text: `Restore "${feature.name}" to live status? It will become available in relevant categories again.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0ea5e9', // primary
        confirmButtonText: 'Yes, restore it',
    }).then((result) => {
        if (result.isConfirmed) {
            form.post(route('admin.features.restore', feature.id), {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        title: 'Restored!',
                        text: 'Feature is now live.',
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


const handleBatchFinished = () => {
    isSyncing.value = false;
    Swal.fire({
        title: 'Synchronization Complete',
        text: 'All tenants have been updated with the latest features.',
        icon: 'success',
        confirmButtonColor: '#0ea5e9',
    });
};

const formatDate = (isoDate) => {
    if (!isoDate) return 'N/A';
    const d = new Date(isoDate);
    return Number.isNaN(d.getTime()) ? 'N/A' : d.toLocaleDateString();
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
                <!-- Tab Switching -->
                <div class="tabs tabs-boxed mb-6 bg-base-200/50 p-1 w-fit border border-base-300">
                    <button 
                        @click="activeTab = 'live'"
                        class="tab tab-sm md:tab-md"
                        :class="{ 'tab-active !bg-white !text-primary shadow-sm': activeTab === 'live' }"
                    >
                        Live Categories
                    </button>
                    <button 
                        @click="activeTab = 'archived'"
                        class="tab tab-sm md:tab-md"
                        :class="{ 'tab-active !bg-white !text-primary shadow-sm': activeTab === 'archived' }"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                        </svg>
                        Archive
                        <span v-if="archivedFeatures.length" class="ml-2 badge badge-sm">
                            {{ archivedFeatures.length }}
                        </span>
                    </button>
                    <button
                        @click="activeTab = 'releases'"
                        class="tab tab-sm md:tab-md"
                        :class="{ 'tab-active !bg-white !text-primary shadow-sm': activeTab === 'releases' }"
                    >
                        Releases
                        <span v-if="releaseFeatures.length" class="ml-2 badge badge-sm">
                            {{ releaseFeatures.length }}
                        </span>
                    </button>
                </div>

                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                    <p class="text-sm text-base-content/70">
                        {{
                            activeTab === 'live'
                                ? 'Manage subscription plan business features dynamically.'
                                : activeTab === 'archived'
                                    ? 'View and manage archived business features.'
                                    : 'GitHub release versions and OTA release entries for tenants.'
                        }}
                    </p>
                    <div class="flex items-center gap-2">
                        <a
                            :href="route('admin.features.adoption')"
                            class="btn btn-sm btn-outline"
                        >
                            Release Adoption
                        </a>
                        <button
                            @click="openCreateModal"
                            class="btn btn-sm shrink-0 border-0 text-white hover:brightness-110 shadow-md transition-all"
                            :style="{ backgroundColor: primaryColor }"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                            </svg>
                            Add Feature
                        </button>
                    </div>
                </div>

                <!-- Batch Progress Bar -->
                <BatchProgressBar 
                    v-if="currentBatchId" 
                    :batch-id="currentBatchId" 
                    title="Bulk OTA Feature Synchronization"
                    @finished="handleBatchFinished"
                />

                <!-- Features by Category -->
                <div v-if="activeTab === 'live'">
                    <FeatureList 
                        :features-by-category="featuresByCategory"
                        :category-labels="categoryLabels"
                        :plans="plans"
                        :get-type-label="getTypeLabel"
                        :primary-color="primaryColor"
                        @manage="openEditModal"
                    />
                </div>

                <!-- Archived Features List -->
                <div v-else-if="activeTab === 'archived'">
                    <div v-if="archivedFeatures.length === 0" class="card bg-base-100 border border-base-300 p-12 text-center">
                        <svg class="w-16 h-16 mx-auto mb-4 text-base-content/20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                        </svg>
                        <h4 class="text-lg font-bold text-base-content/50">No archived features</h4>
                        <p class="text-sm text-base-content/40">Archived features will appear here.</p>
                    </div>
                    <FeatureList 
                        v-else
                        :features-by-category="{ archived: archivedFeatures }"
                        :category-labels="{ archived: 'Archived Features' }"
                        :plans="plans"
                        :get-type-label="getTypeLabel"
                        :primary-color="primaryColor"
                        :is-archive="true"
                        @manage="openEditModal"
                    />
                </div>

                <!-- Release Entries -->
                <div v-else>
                    <div v-if="releaseFeatures.length === 0" class="card bg-base-100 border border-base-300 p-12 text-center">
                        <svg class="w-16 h-16 mx-auto mb-4 text-base-content/20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-3-3v6m9 2.25A2.25 2.25 0 0 1 18.75 21H5.25A2.25 2.25 0 0 1 3 18.75V5.25A2.25 2.25 0 0 1 5.25 3h7.5l5.25 5.25v10.5Z" />
                        </svg>
                        <h4 class="text-lg font-bold text-base-content/50">No release entries found</h4>
                        <p class="text-sm text-base-content/40">Run release sync to import GitHub releases.</p>
                    </div>

                    <div v-else class="bg-base-100 border border-base-300 rounded-xl overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="table w-full">
                                <thead>
                                    <tr>
                                        <th>Version</th>
                                        <th>Name</th>
                                        <th>Feature Key</th>
                                        <th>Released</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="releaseFeature in releaseFeatures" :key="releaseFeature.id">
                                        <td class="font-semibold">{{ releaseFeature.system_release?.version || 'N/A' }}</td>
                                        <td>{{ releaseFeature.name }}</td>
                                        <td class="font-mono text-xs text-base-content/60">{{ releaseFeature.key }}</td>
                                        <td>{{ formatDate(releaseFeature.released_at || releaseFeature.system_release?.released_at) }}</td>
                                        <td>
                                            <span class="badge badge-success badge-sm">
                                                {{ releaseFeature.implementation_status?.replace('_', ' ') || 'active' }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <FeatureFormModal
            :show="showModal"
            :editing-feature="editingFeature"
            :form="form"
            :features-by-category="featuresByCategory"
            :primary-color="primaryColor"
            @close="closeModal"
            @submit="submitForm"
            @delete="deleteFeature"
            @toggle="toggleFeature"
            @archive="archiveFeature"
            @restore="restoreFeature"
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
