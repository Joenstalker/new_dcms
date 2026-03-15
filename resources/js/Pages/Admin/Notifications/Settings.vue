<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    settings: Array,
});

const localSettings = ref([...props.settings]);
const saving = ref(false);
const saved = ref(false);

const notificationCategories = computed(() => {
    const categories = {
        tenants: {
            title: 'Tenant Management',
            description: 'Notifications related to tenant activities',
            types: ['new_tenant', 'tenant_suspended', 'tenant_reactivated'],
        },
        subscriptions: {
            title: 'Subscriptions',
            description: 'Notifications related to subscription events',
            types: ['subscription_created', 'subscription_renewed', 'subscription_expiring', 'subscription_expired', 'subscription_cancelled'],
        },
        support: {
            title: 'Support & Tickets',
            description: 'Notifications related to support tickets',
            types: ['new_support_ticket', 'support_ticket_replied', 'feature_request'],
        },
        payments: {
            title: 'Payments',
            description: 'Notifications related to payments',
            types: ['payment_received', 'payment_failed'],
        },
        features: {
            title: 'Features',
            description: 'Notifications related to platform features',
            types: ['new_feature_published'],
        },
    };

    return Object.entries(categories).map(([key, category]) => ({
        key,
        ...category,
        settings: localSettings.value.filter(s => category.types.includes(s.type)),
    })).filter(c => c.settings.length > 0);
});

const updateSetting = (type, field, value) => {
    const setting = localSettings.value.find(s => s.type === type);
    if (setting) {
        setting[field] = value;
    }
};

const toggleCategory = (category, enabled) => {
    category.settings.forEach(setting => {
        updateSetting(setting.type, 'enabled', enabled);
    });
};

const isCategoryEnabled = (category) => {
    return category.settings.some(s => s.enabled);
};

const saveSettings = async () => {
    saving.value = true;
    saved.value = false;

    try {
        await fetch(route('admin.notifications.settings.update'), {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                settings: localSettings.value,
            }),
        });
        saved.value = true;
        setTimeout(() => {
            saved.value = false;
        }, 3000);
    } catch (error) {
        console.error('Failed to save settings:', error);
    } finally {
        saving.value = false;
    }
};
</script>

<template>
    <Head title="Notification Settings" />
    <AdminLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-gray-900">Notification Settings</h1>
        </template>

        <div class="max-w-4xl mx-auto py-6">
        <!-- Page Header -->
        <div class="mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <Link
                            :href="route('admin.notifications.index')"
                            class="p-2 text-gray-500 hover:text-gray-700 rounded-lg hover:bg-gray-100"
                        >
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </Link>
                        <h1 class="text-2xl font-bold text-gray-900">Notification Settings</h1>
                    </div>
                    <p class="mt-1 text-sm text-gray-500 ml-10">
                        Configure which notifications you want to receive
                    </p>
                </div>
                <button
                    @click="saveSettings"
                    :disabled="saving"
                    class="inline-flex items-center px-4 py-2 bg-teal-600 rounded-lg text-sm font-medium text-white hover:bg-teal-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <svg v-if="saving" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <svg v-else class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    {{ saving ? 'Saving...' : 'Save Changes' }}
                </button>
            </div>

            <!-- Success Message -->
            <div v-if="saved" class="mt-4 ml-10 flex items-center gap-2 text-sm text-green-600 bg-green-50 px-4 py-2 rounded-lg">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Settings saved successfully!
            </div>
        </div>

        <!-- Settings Categories -->
        <div class="space-y-6">
            <div 
                v-for="category in notificationCategories" 
                :key="category.key"
                class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden"
            >
                <!-- Category Header -->
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">{{ category.title }}</h3>
                            <p class="text-sm text-gray-500 mt-0.5">{{ category.description }}</p>
                        </div>
                        <label class="flex items-center cursor-pointer">
                            <span class="mr-3 text-sm font-medium text-gray-700">Enable All</span>
                            <div class="relative">
                                <input 
                                    type="checkbox" 
                                    :checked="isCategoryEnabled(category)"
                                    @change="toggleCategory(category, $event.target.checked)"
                                    class="sr-only peer"
                                />
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-teal-100 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-teal-600"></div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Settings List -->
                <ul class="divide-y divide-gray-100">
                    <li 
                        v-for="setting in category.settings" 
                        :key="setting.type"
                        class="px-6 py-4 flex items-center justify-between"
                    >
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-900">{{ setting.label }}</p>
                            <p class="text-sm text-gray-500 mt-0.5">{{ setting.description }}</p>
                        </div>
                        <div class="flex items-center gap-4">
                            <!-- Channel Selection -->
                            <select
                                v-if="setting.channels && setting.channels.length > 1"
                                :value="setting.channel"
                                @change="updateSetting(setting.type, 'channel', $event.target.value)"
                                class="text-sm border-gray-300 rounded-lg focus:ring-teal-500 focus:border-teal-500 py-1.5 px-3"
                            >
                                <option value="database">In-App</option>
                                <option value="email">Email</option>
                                <option value="both">Both</option>
                            </select>
                            
                            <!-- Enable/Disable Toggle -->
                            <label class="flex items-center cursor-pointer">
                                <div class="relative">
                                    <input 
                                        type="checkbox" 
                                        :checked="setting.enabled"
                                        @change="updateSetting(setting.type, 'enabled', $event.target.checked)"
                                        class="sr-only peer"
                                    />
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-teal-100 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-teal-600"></div>
                                </div>
                            </label>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="notificationCategories.length === 0" class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
            <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
            </svg>
            <h3 class="text-lg font-medium text-gray-900 mb-1">No settings available</h3>
            <p class="text-gray-500">No notification settings found.</p>
        </div>
        </div>
    </AdminLayout>
</template>
