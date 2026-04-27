<template>
  <AdminLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold text-gray-900">Platform Analytics</h2>
        <button
          @click="isExportModalOpen = true"
          class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
        >
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
          </svg>
          Export PDF
        </button>
      </div>
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

    <!-- Export Modal -->
    <Modal :show="isExportModalOpen" @close="isExportModalOpen = false">
      <div class="p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Export Analytics Report</h3>
        
        <div class="space-y-4">
          <!-- Filter Period -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Filter Period</label>
            <select v-model="exportFilter" class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
              <option value="today">Today</option>
              <option value="week">This Week</option>
              <option value="month">This Month</option>
              <option value="year">This Year</option>
              <option value="custom">Custom Range</option>
            </select>
          </div>

          <!-- Custom Date Range -->
          <div v-if="exportFilter === 'custom'" class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
              <input 
                type="date" 
                v-model="startDate" 
                class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
              >
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
              <input 
                type="date" 
                v-model="endDate" 
                class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
              >
            </div>
          </div>

          <!-- Data Types -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Include in Report</label>
            <div class="grid grid-cols-2 gap-3">
              <label v-for="type in [
                { id: 'tenants', label: 'Tenants' },
                { id: 'subscriptions', label: 'Subscriptions' },
                { id: 'features', label: 'Features' },
                { id: 'support', label: 'Support & Tickets' }
              ]" :key="type.id" class="flex items-center space-x-2 cursor-pointer group">
                <div class="relative flex items-center">
                  <input 
                    type="checkbox" 
                    v-model="selectedTypes" 
                    :value="type.id" 
                    class="peer sr-only"
                  >
                  <div class="w-5 h-5 border-2 border-gray-300 rounded peer-checked:bg-indigo-600 peer-checked:border-indigo-600 flex items-center justify-center transition-all duration-200">
                    <svg class="w-3.5 h-3.5 text-white scale-0 peer-checked:scale-100 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                    </svg>
                  </div>
                </div>
                <span class="text-sm text-gray-600 group-hover:text-gray-900 transition-colors">{{ type.label }}</span>
              </label>
            </div>
            <div class="mt-3">
              <button @click="toggleAllTypes" class="text-xs text-indigo-600 hover:text-indigo-900 font-medium">
                {{ selectedTypes.length === 4 ? 'Deselect All' : 'Select All' }}
              </button>
            </div>
          </div>
        </div>

        <div class="mt-6 flex justify-end space-x-3">
          <button
            @click="isExportModalOpen = false"
            class="px-4 py-2 bg-gray-100 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150"
          >
            Cancel
          </button>
          <button
            @click="handleExportPdf"
            :disabled="selectedTypes.length === 0"
            class="px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50"
          >
            Download PDF
          </button>
        </div>
      </div>
    </Modal>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TabNavigation from './Partials/TabNavigation.vue';
import TenantsTab from './Partials/TenantsTab.vue';
import SubscriptionsTab from './Partials/SubscriptionsTab.vue';
import FeaturesTab from './Partials/FeaturesTab.vue';
import SupportTab from './Partials/SupportTab.vue';
import Modal from '@/Components/Modal.vue';

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

const isExportModalOpen = ref(false);
const exportFilter = ref('month');
const startDate = ref('');
const endDate = ref('');
const selectedTypes = ref(['tenants', 'subscriptions', 'features', 'support']);

const toggleAllTypes = () => {
  if (selectedTypes.value.length === 4) {
    selectedTypes.value = [];
  } else {
    selectedTypes.value = ['tenants', 'subscriptions', 'features', 'support'];
  }
};

const handleExportPdf = () => {
  // Validate custom dates if selected
  if (exportFilter.value === 'custom') {
    if (!startDate.value || !endDate.value) {
      alert('Please select both start and end dates for custom range.');
      return;
    }
  }

  // We use a hidden form to submit a POST request for the download
  const form = document.createElement('form');
  form.method = 'POST';
  form.action = route('admin.analytics.export');
  form.target = '_blank';

  // Add CSRF token
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  if (csrfToken) {
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = '_token';
    input.value = csrfToken;
    form.appendChild(input);
  }

  // Add filter
  const filterInput = document.createElement('input');
  filterInput.type = 'hidden';
  filterInput.name = 'filter';
  filterInput.value = exportFilter.value;
  form.appendChild(filterInput);

  // Add custom dates if filter is custom
  if (exportFilter.value === 'custom') {
    const startInput = document.createElement('input');
    startInput.type = 'hidden';
    startInput.name = 'start_date';
    startInput.value = startDate.value;
    form.appendChild(startInput);

    const endInput = document.createElement('input');
    endInput.type = 'hidden';
    endInput.name = 'end_date';
    endInput.value = endDate.value;
    form.appendChild(endInput);
  }

  // Add types
  selectedTypes.value.forEach(type => {
    const typeInput = document.createElement('input');
    typeInput.type = 'hidden';
    typeInput.name = 'types[]';
    typeInput.value = type;
    form.appendChild(typeInput);
  });

  document.body.appendChild(form);
  form.submit();
  document.body.removeChild(form);
  
  isExportModalOpen.value = false;
};
</script>
