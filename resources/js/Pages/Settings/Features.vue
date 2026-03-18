<script setup>
import { computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

import CurrentPlanBanner from './Partials/CurrentPlanBanner.vue';
import FeatureCategory from './Partials/FeatureCategory.vue';
import UpgradeCta from './Partials/UpgradeCta.vue';

const props = defineProps({
    tenant: Object,
    features: Object,
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
    addons: 'Enhance your clinic with premium features',
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
</script>

<template>
    <Head title="Features" />

    <AuthenticatedLayout>
        <div class="py-12">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <h2 class="text-2xl font-bold text-gray-900">Your Features</h2>
                    <p class="mt-1 text-sm text-gray-600">
                        View the features available on your current subscription plan.
                    </p>
                </div>

                <!-- Current Plan Banner -->
                <CurrentPlanBanner :subscription="subscription" />

                <!-- Features by Category -->
                <div class="space-y-8">
                    <FeatureCategory 
                        v-for="(categoryFeatures, category) in features" 
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
