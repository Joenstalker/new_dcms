<template>
  <AdminLayout>
    <template #header>
      <h2 class="text-xl font-bold text-gray-900">Platform Analytics</h2>
    </template>

    <div class="px-4 py-6 sm:px-0">
      <p class="text-gray-600 mb-6">Comprehensive analytics across your entire platform</p>

    <!-- Tab Navigation -->
    <div class="mb-6 border-b border-gray-200">
      <nav class="-mb-px flex space-x-8">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          @click="activeTab = tab.id"
          :class="[
            activeTab === tab.id
              ? 'border-indigo-500 text-indigo-600'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
            'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
          ]"
        >
          {{ tab.name }}
        </button>
      </nav>
    </div>

    <!-- Tenants Tab -->
    <div v-if="activeTab === 'tenants'">
      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="p-3 rounded-full bg-indigo-100 text-indigo-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">Total Tenants</p>
              <p class="text-2xl font-semibold text-gray-900">{{ tenants.total }}</p>
            </div>
          </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100 text-green-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">Active Tenants</p>
              <p class="text-2xl font-semibold text-gray-900">{{ tenants.active }}</p>
            </div>
          </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="p-3 rounded-full bg-gray-100 text-gray-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">Inactive Tenants</p>
              <p class="text-2xl font-semibold text-gray-900">{{ tenants.inactive }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Charts Row -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Tenant Growth Chart -->
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Tenant Growth (Last 12 Months)</h3>
          <div class="h-64">
            <canvas ref="tenantGrowthChart"></canvas>
          </div>
        </div>

        <!-- Tenants by Plan -->
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Tenants by Plan</h3>
          <div class="h-64">
            <canvas ref="tenantPlanChart"></canvas>
          </div>
        </div>
      </div>

      <!-- Recent Tenants Table -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-semibold text-gray-900">Recent Tenants</h3>
        </div>
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Domain</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plan</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="tenant in tenants.recent" :key="tenant.id">
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ tenant.name }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ tenant.domain }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ tenant.plan }}</td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="[
                  'px-2 inline-flex text-xs leading-5 font-semibold rounded-full',
                  tenant.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                ]">
                  {{ tenant.status }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ tenant.created_at }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Subscriptions Tab -->
    <div v-if="activeTab === 'subscriptions'">
      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="p-3 rounded-full bg-indigo-100 text-indigo-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">Total Subscriptions</p>
              <p class="text-2xl font-semibold text-gray-900">{{ subscriptions.total }}</p>
            </div>
          </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100 text-green-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">Monthly Recurring Revenue</p>
              <p class="text-2xl font-semibold text-gray-900">${{ subscriptions.total_mrr?.toLocaleString() || 0 }}</p>
            </div>
          </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100 text-blue-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">New Subscriptions</p>
              <p class="text-2xl font-semibold text-gray-900">{{ subscriptions.new_subscriptions?.length || 0 }}</p>
            </div>
          </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="p-3 rounded-full" :class="subscriptions.churn_rate > 10 ? 'bg-red-100 text-red-600' : 'bg-yellow-100 text-yellow-600'">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">Churn Rate (30d)</p>
              <p class="text-2xl font-semibold text-gray-900">{{ subscriptions.churn_rate }}%</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Charts Row -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- MRR Trend -->
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">MRR Trend (Last 12 Months)</h3>
          <div class="h-64">
            <canvas ref="mrrChart"></canvas>
          </div>
        </div>

        <!-- Subscriptions by Plan -->
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Subscriptions by Plan</h3>
          <div class="h-64">
            <canvas ref="subscriptionPlanChart"></canvas>
          </div>
        </div>
      </div>

      <!-- Subscriptions by Status -->
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Subscriptions by Status</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div v-for="status in subscriptions.by_status" :key="status.status" class="text-center p-4 bg-gray-50 rounded-lg">
            <p class="text-3xl font-bold text-gray-900">{{ status.count }}</p>
            <p class="text-sm text-gray-500 capitalize">{{ status.status }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Features Tab -->
    <div v-if="activeTab === 'features'">
      <!-- Feature Usage Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">SMS Notifications</h3>
            <span class="p-2 rounded-full bg-blue-100 text-blue-600">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
              </svg>
            </span>
          </div>
          <p class="text-3xl font-bold text-gray-900">{{ features.usage?.sms || 0 }}</p>
          <p class="text-sm text-gray-500">Active subscriptions</p>
          <div class="mt-4 bg-gray-200 rounded-full h-2">
            <div class="bg-blue-600 h-2 rounded-full" :style="{ width: getPercentage(features.usage?.sms, subscriptions.total) + '%' }"></div>
          </div>
          <p class="text-xs text-gray-500 mt-1">{{ getPercentage(features.usage?.sms, subscriptions.total) }}% adoption</p>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Custom Branding</h3>
            <span class="p-2 rounded-full bg-purple-100 text-purple-600">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
              </svg>
            </span>
          </div>
          <p class="text-3xl font-bold text-gray-900">{{ features.usage?.branding || 0 }}</p>
          <p class="text-sm text-gray-500">Active subscriptions</p>
          <div class="mt-4 bg-gray-200 rounded-full h-2">
            <div class="bg-purple-600 h-2 rounded-full" :style="{ width: getPercentage(features.usage?.branding, subscriptions.total) + '%' }"></div>
          </div>
          <p class="text-xs text-gray-500 mt-1">{{ getPercentage(features.usage?.branding, subscriptions.total) }}% adoption</p>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Advanced Analytics</h3>
            <span class="p-2 rounded-full bg-green-100 text-green-600">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
            </span>
          </div>
          <p class="text-3xl font-bold text-gray-900">{{ features.usage?.analytics || 0 }}</p>
          <p class="text-sm text-gray-500">Active subscriptions</p>
          <div class="mt-4 bg-gray-200 rounded-full h-2">
            <div class="bg-green-600 h-2 rounded-full" :style="{ width: getPercentage(features.usage?.analytics, subscriptions.total) + '%' }"></div>
          </div>
          <p class="text-xs text-gray-500 mt-1">{{ getPercentage(features.usage?.analytics, subscriptions.total) }}% adoption</p>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">QR Booking</h3>
            <span class="p-2 rounded-full bg-orange-100 text-orange-600">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
              </svg>
            </span>
          </div>
          <p class="text-3xl font-bold text-gray-900">{{ features.usage?.qr_booking || 0 }}</p>
          <p class="text-sm text-gray-500">Active subscriptions</p>
          <div class="mt-4 bg-gray-200 rounded-full h-2">
            <div class="bg-orange-600 h-2 rounded-full" :style="{ width: getPercentage(features.usage?.qr_booking, subscriptions.total) + '%' }"></div>
          </div>
          <p class="text-xs text-gray-500 mt-1">{{ getPercentage(features.usage?.qr_booking, subscriptions.total) }}% adoption</p>
        </div>
      </div>

      <!-- Feature by Plan Table -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-semibold text-gray-900">Features by Plan</h3>
        </div>
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plan</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SMS</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Branding</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Analytics</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">QR Booking</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="plan in features.by_plan" :key="plan.name">
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ plan.name }}</td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span v-if="plan.has_sms" class="text-green-600">✓</span>
                <span v-else class="text-gray-400">✗</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span v-if="plan.has_branding" class="text-green-600">✓</span>
                <span v-else class="text-gray-400">✗</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span v-if="plan.has_analytics" class="text-green-600">✓</span>
                <span v-else class="text-gray-400">✗</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span v-if="plan.has_qr_booking" class="text-green-600">✓</span>
                <span v-else class="text-gray-400">✗</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Support Tab -->
    <div v-if="activeTab === 'support'">
      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="p-3 rounded-full bg-indigo-100 text-indigo-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">Total Tickets</p>
              <p class="text-2xl font-semibold text-gray-900">{{ support.total }}</p>
            </div>
          </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">Open Tickets</p>
              <p class="text-2xl font-semibold text-gray-900">{{ support.open }}</p>
            </div>
          </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100 text-green-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">Resolved</p>
              <p class="text-2xl font-semibold text-gray-900">{{ support.resolved }}</p>
            </div>
          </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100 text-blue-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">Avg Response Time</p>
              <p class="text-2xl font-semibold text-gray-900">{{ support.avg_response_time }}h</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Charts Row -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- By Status -->
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Tickets by Status</h3>
          <div class="h-48">
            <canvas ref="ticketStatusChart"></canvas>
          </div>
        </div>

        <!-- By Priority -->
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Tickets by Priority</h3>
          <div class="h-48">
            <canvas ref="ticketPriorityChart"></canvas>
          </div>
        </div>

        <!-- By Category -->
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Tickets by Category</h3>
          <div class="h-48">
            <canvas ref="ticketCategoryChart"></canvas>
          </div>
        </div>
      </div>

      <!-- Recent Tickets -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-semibold text-gray-900">Recent Tickets</h3>
        </div>
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tenant</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="ticket in support.recent" :key="ticket.id">
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ ticket.subject }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ ticket.tenant }}</td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="[
                  'px-2 inline-flex text-xs leading-5 font-semibold rounded-full',
                  ticket.status === 'open' ? 'bg-red-100 text-red-800' :
                  ticket.status === 'in_progress' ? 'bg-yellow-100 text-yellow-800' :
                  ticket.status === 'resolved' ? 'bg-green-100 text-green-800' :
                  'bg-gray-100 text-gray-800'
                ]">
                  {{ ticket.status }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="[
                  'px-2 inline-flex text-xs leading-5 font-semibold rounded-full',
                  ticket.priority === 'urgent' ? 'bg-red-100 text-red-800' :
                  ticket.priority === 'high' ? 'bg-orange-100 text-orange-800' :
                  ticket.priority === 'medium' ? 'bg-blue-100 text-blue-800' :
                  'bg-gray-100 text-gray-800'
                ]">
                  {{ ticket.priority }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 capitalize">{{ ticket.category }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ ticket.created_at }}</td>
            </tr>
            <tr v-if="support.recent?.length === 0">
              <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                No support tickets yet. Tickets will appear here when tenants submit support requests.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, onMounted, watch, nextTick } from 'vue';
import { usePage, Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Chart from 'chart.js/auto';

const page = usePage();
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

// Chart refs
const tenantGrowthChart = ref(null);
const tenantPlanChart = ref(null);
const mrrChart = ref(null);
const subscriptionPlanChart = ref(null);
const ticketStatusChart = ref(null);
const ticketPriorityChart = ref(null);
const ticketCategoryChart = ref(null);

// Helper function
const getPercentage = (value, total) => {
  if (!total || total === 0) return 0;
  return Math.round((value / total) * 100);
};

// Initialize charts
const initCharts = () => {
  // Tenant Growth Chart
  if (tenantGrowthChart.value && props.tenants?.growth) {
    new Chart(tenantGrowthChart.value, {
      type: 'line',
      data: {
        labels: props.tenants.growth.map(g => g.month),
        datasets: [{
          label: 'New Tenants',
          data: props.tenants.growth.map(g => g.count),
          borderColor: 'rgb(79, 70, 229)',
          backgroundColor: 'rgba(79, 70, 229, 0.1)',
          fill: true,
          tension: 0.3
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          y: { beginAtZero: true, ticks: { stepSize: 1 } }
        }
      }
    });
  }

  // Tenant Plan Chart
  if (tenantPlanChart.value && props.tenants?.by_plan) {
    new Chart(tenantPlanChart.value, {
      type: 'doughnut',
      data: {
        labels: props.tenants.by_plan.map(p => p.plan_name || 'No Plan'),
        datasets: [{
          data: props.tenants.by_plan.map(p => p.count),
          backgroundColor: [
            'rgb(79, 70, 229)',
            'rgb(34, 197, 94)',
            'rgb(249, 115, 22)',
            'rgb(234, 179, 8)',
            'rgb(161, 98, 7)'
          ]
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom' } }
      }
    });
  }

  // MRR Chart
  if (mrrChart.value && props.subscriptions?.mrr) {
    new Chart(mrrChart.value, {
      type: 'bar',
      data: {
        labels: props.subscriptions.mrr.map(m => m.month),
        datasets: [{
          label: 'MRR ($)',
          data: props.subscriptions.mrr.map(m => m.mrr || 0),
          backgroundColor: 'rgb(34, 197, 94)',
          borderRadius: 4
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          y: { beginAtZero: true }
        }
      }
    });
  }

  // Subscription Plan Chart
  if (subscriptionPlanChart.value && props.subscriptions?.by_plan) {
    new Chart(subscriptionPlanChart.value, {
      type: 'pie',
      data: {
        labels: props.subscriptions.by_plan.map(p => p.plan_name),
        datasets: [{
          data: props.subscriptions.by_plan.map(p => p.count),
          backgroundColor: [
            'rgb(79, 70, 229)',
            'rgb(34, 197, 94)',
            'rgb(249, 115, 22)',
            'rgb(234, 179, 8)'
          ]
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom' } }
      }
    });
  }

  // Ticket Status Chart
  if (ticketStatusChart.value && props.support?.by_status?.length > 0) {
    new Chart(ticketStatusChart.value, {
      type: 'doughnut',
      data: {
        labels: props.support.by_status.map(s => s.status),
        datasets: [{
          data: props.support.by_status.map(s => s.count),
          backgroundColor: [
            'rgb(239, 68, 68)',
            'rgb(234, 179, 8)',
            'rgb(59, 130, 246)',
            'rgb(34, 197, 94)',
            'rgb(156, 163, 175)'
          ]
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom' } }
      }
    });
  }

  // Ticket Priority Chart
  if (ticketPriorityChart.value && props.support?.by_priority?.length > 0) {
    new Chart(ticketPriorityChart.value, {
      type: 'bar',
      data: {
        labels: props.support.by_priority.map(p => p.priority),
        datasets: [{
          label: 'Tickets',
          data: props.support.by_priority.map(p => p.count),
          backgroundColor: [
            'rgb(239, 68, 68)',
            'rgb(249, 115, 22)',
            'rgb(234, 179, 8)',
            'rgb(34, 197, 94)'
          ],
          borderRadius: 4
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          y: { beginAtZero: true }
        }
      }
    });
  }

  // Ticket Category Chart
  if (ticketCategoryChart.value && props.support?.by_category?.length > 0) {
    new Chart(ticketCategoryChart.value, {
      type: 'doughnut',
      data: {
        labels: props.support.by_category.map(c => c.category),
        datasets: [{
          data: props.support.by_category.map(c => c.count),
          backgroundColor: [
            'rgb(79, 70, 229)',
            'rgb(34, 197, 94)',
            'rgb(249, 115, 22)',
            'rgb(234, 179, 8)',
            'rgb(156, 163, 175)'
          ]
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom' } }
      }
    });
  }
};

onMounted(() => {
  nextTick(() => {
    initCharts();
  });
});

watch(activeTab, () => {
  nextTick(() => {
    initCharts();
  });
});
</script>
