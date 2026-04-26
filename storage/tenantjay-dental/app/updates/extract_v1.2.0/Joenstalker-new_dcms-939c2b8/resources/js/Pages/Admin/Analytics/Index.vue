<template>
  <AdminLayout>
    <template #header>
      <h2 class="text-xl font-bold text-gray-900">Platform Analytics</h2>
    </template>

    <div class="px-4 py-6 sm:px-0">
      <p class="text-gray-600 mb-6">Comprehensive analytics across your entire platform</p>

      <!-- Tab Navigation -->
      <TabNavigation 
        :tabs="tabs" 
        :active-tab="activeTab" 
        @update:activeTab="activeTab = $event"
      />

      <!-- Tenants Tab -->
      <TenantsTab 
        v-if="activeTab === 'tenants'" 
        :tenants="tenants" 
      />

      <!-- Subscriptions Tab -->
      <SubscriptionsTab 
        v-if="activeTab === 'subscriptions'" 
        :subscriptions="subscriptions" 
      />

      <!-- Features Tab -->
      <FeaturesTab 
        v-if="activeTab === 'features'" 
        :features="features"
        :subscriptions="subscriptions"
      />

      <!-- Support Tab -->
      <SupportTab 
        v-if="activeTab === 'support'" 
        :support="support" 
      />
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TabNavigation from './Partials/TabNavigation.vue';
import TenantsTab from './Partials/TenantsTab.vue';
import SubscriptionsTab from './Partials/SubscriptionsTab.vue';
import FeaturesTab from './Partials/FeaturesTab.vue';
import SupportTab from './Partials/SupportTab.vue';

const props = defineProps({
  tenants: Object,
  subscriptions: Object,
  features: Object,
  support: Object
});

const activeTab = ref('tenants');
const tabs = [
  { id: 'tenants', name: 'Tenants' },
  { id: 'subscriptions', name: 'Subscriptions' },
  { id: 'features', name: 'Features' },
  { id: 'support', name: 'Support & Tickets' }
];
</script>
