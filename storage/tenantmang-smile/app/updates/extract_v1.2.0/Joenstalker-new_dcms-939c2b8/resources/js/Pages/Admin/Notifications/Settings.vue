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
            <h1 class="text-xl font-bold text-base-content leading-tight">Notification Settings</h1>
        </template>

        <div class="max-w-4xl mx-auto py-8 px-4">
        <!-- Page Header -->
        <div class="mb-10">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6">
                <div>
                    <div class="flex items-center gap-4">
                        <Link
                            :href="route('admin.notifications.index')"
                            class="btn btn-ghost btn-sm btn-circle text-base-content/40 hover:text-base-content hover:bg-base-200"
                        >
                            <svg class="w-5 h-5 transition-transform group-hover:-translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                            </svg>
                        </Link>
                        <div>
                            <h1 class="text-3xl font-black text-base-content tracking-tight">Notification Preferences</h1>
                            <p class="mt-1 text-sm text-base-content/50 font-medium">
                                Configure which activities you want to stay updated on
                            </p>
                        </div>
                    </div>
                </div>
                <button
                    @click="saveSettings"
                    :disabled="saving"
                    class="btn btn-primary btn-md font-black shadow-lg shadow-primary/20 min-w-[160px]"
                >
                    <span v-if="saving" class="loading loading-spinner loading-xs mr-2"></span>
                    <svg v-else class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    {{ saving ? 'Saving...' : 'Save Changes' }}
                </button>
            </div>

            <!-- Success Message -->
            <div v-if="saved" class="mt-6 ml-14 flex items-center gap-3 text-sm font-bold text-success bg-success/10 px-6 py-3 rounded-2xl border border-success/20 animate-in fade-in slide-in-from-top-4 duration-500">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                Settings saved successfully!
            </div>
        </div>

        <!-- Settings Categories -->
        <div class="space-y-8">
            <div 
                v-for="category in notificationCategories" 
                :key="category.key"
                class="bg-base-100 rounded-3xl shadow-sm border border-base-300 overflow-hidden transition-all duration-300 hover:shadow-md"
            >
                <!-- Category Header -->
                <div class="px-8 py-5 bg-base-200/50 border-b border-base-300">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-black text-base-content tracking-tight">{{ category.title }}</h3>
                            <p class="text-xs text-base-content/40 font-bold uppercase tracking-widest mt-1">{{ category.description }}</p>
                        </div>
                        <label class="flex items-center gap-4 cursor-pointer group">
                            <span class="text-xs font-black text-base-content/40 uppercase tracking-widest group-hover:text-primary transition-colors">Enable All</span>
                            <input 
                                type="checkbox" 
                                :checked="isCategoryEnabled(category)"
                                @change="toggleCategory(category, $event.target.checked)"
                                class="toggle toggle-primary toggle-sm"
                            />
                        </label>
                    </div>
                </div>

                <!-- Settings List -->
                <ul class="divide-y divide-base-300/30">
                    <li 
                        v-for="setting in category.settings" 
                        :key="setting.type"
                        class="px-8 py-5 flex items-center justify-between hover:bg-base-200/20 transition-colors"
                    >
                        <div class="flex-1 pr-6">
                            <p class="text-sm font-bold text-base-content">{{ setting.label }}</p>
                            <p class="text-xs text-base-content/50 mt-1 font-medium leading-relaxed">{{ setting.description }}</p>
                        </div>
                        <div class="flex items-center gap-6">
                            <!-- Channel Selection -->
                            <select
                                v-if="setting.channels && setting.channels.length > 1"
                                :value="setting.channel"
                                @change="updateSetting(setting.type, 'channel', $event.target.value)"
                                class="select select-bordered select-sm font-bold text-xs min-w-[120px]"
                            >
                                <option value="database">In-App Only</option>
                                <option value="email">Email Only</option>
                                <option value="both">Both Channels</option>
                            </select>
                            
                            <!-- Enable/Disable Toggle -->
                            <input 
                                type="checkbox" 
                                :checked="setting.enabled"
                                @change="updateSetting(setting.type, 'enabled', $event.target.checked)"
                                class="toggle toggle-primary"
                            />
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="notificationCategories.length === 0" class="bg-base-100 rounded-3xl shadow-sm border border-base-300 p-16 text-center">
            <div class="w-20 h-20 rounded-full bg-base-200 flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10 text-base-content/20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                </svg>
            </div>
            <h3 class="text-xl font-black text-base-content mb-2">No settings available</h3>
            <p class="text-base-content/40 font-medium">No notification categories found in the system.</p>
        </div>
        </div>
    </AdminLayout>
</template>
