<script setup>
import { ref, computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FeatureFormModal from './Partials/FeatureFormModal.vue';
import FeatureViewModal from './Partials/FeatureViewModal.vue';
import FeatureList from './Partials/FeatureList.vue';
import PlanOverview from './Partials/PlanOverview.vue';

const props = defineProps({
    features: Object,
    plans: Array,
});

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

const deleteFeature = (feature) => {
    if (confirm('Are you sure you want to delete this feature?')) {
        form.delete(`/admin/features/${feature.id}`, { preserveScroll: true });
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
                    <h2 class="font-bold text-xl text-base-content leading-tight">Feature Management</h2>
                    <p class="mt-1 text-sm text-base-content/50">
                        Manage subscription plan features dynamically. Add, edit, or remove features.
                    </p>
                </div>
                <button
                    @click="openCreateModal"
                    class="btn btn-primary btn-sm"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Feature
                </button>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Features by Category -->
                <FeatureList 
                    :features-by-category="featuresByCategory"
                    :category-labels="categoryLabels"
                    :plans="plans"
                    :get-type-label="getTypeLabel"
                    @view="openViewModal"
                    @edit="openEditModal"
                    @delete="deleteFeature"
                />

                <!-- Plans Section -->
                <PlanOverview :plans="plans" />
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
