<script setup>
import { computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

import CurrentPlanBanner from './Partials/CurrentPlanBanner.vue';
import FeatureCategory from '../CustomBranding/Partials/FeatureCategory.vue';
import UpgradeCta from './Partials/UpgradeCta.vue';

const props = defineProps({
    tenant: Object,
    features: Object,
    has_pending_updates: Boolean,
});

const page = usePage();
const subscription = computed(() => page.props.subscription || null);

const categoryLabels = {
    core: 'Core Features',
    limits: 'Usage Limits',
    addons: 'Premium Add-ons',
    reports: 'Reports & Analytics',
    expansion: 'Expansion',
};

const categoryDescriptions = {
    core: 'Essential features available to all plans',
    limits: 'Your current plan limits',
    addons: 'Enhance your clinic with extra features',
    reports: 'Analytics and reporting capabilities',
    expansion: 'Grow your business with advanced options',
};

const getCategoryStatus = (category) => {
    const categoryFeatures = props.features[category] || [];
    if (categoryFeatures.length === 0) return 'unavailable';
    
    const allEnabled = categoryFeatures.every(f => f.is_enabled);
    const someEnabled = categoryFeatures.some(f => f.is_enabled);
    
    if (allEnabled) return 'full';
    if (someEnabled) return 'partial';
    return 'locked';
};

// Filter out features that are NOT part of the active plan
const filteredFeatures = computed(() => {
    const result = {};
    for (const [category, categoryFeatures] of Object.entries(props.features)) {
        // Only keep features that are explicitly enabled or have a pending OTA update
        const available = categoryFeatures.filter(f => f.is_enabled || f.has_pending_update);
        
        // Only include the entire category if there's at least one feature available to display
        if (available.length > 0) {
            result[category] = available;
        }
    }
    return result;
});
</script>

<template>
    <Head title="Features" />

    <AuthenticatedLayout>
        <div class="py-12">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <h2 class="text-2xl font-bold text-gray-900">My Features</h2>
                    <p class="mt-1 text-sm text-gray-600">
                        View the features available on your current subscription plan.
                    </p>
                </div>

                <!-- Current Plan Banner -->
                <CurrentPlanBanner 
                    :subscription="subscription" 
                    :has-updates="has_pending_updates"
                />

                <!-- Features by Category -->
                <div class="space-y-8">
                    <FeatureCategory 
                        v-for="(categoryFeatures, category) in filteredFeatures" 
                        :key="category"
                        :category="category"
                        :category-features="categoryFeatures"
                        :category-labels="categoryLabels"
                        :category-descriptions="categoryDescriptions"
                        :status="getCategoryStatus(category)"
                    />
                </div>

                <!-- Upgrade CTA -->
                <UpgradeCta :show="!!subscription" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
