<script setup>
import { computed, ref } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const page = usePage();
const primaryColor = computed(() => page.props.branding?.primary_color || '#0ea5e9');

const props = defineProps({
    summary: {
        type: Object,
        required: true,
    },
    globalVersion: {
        type: String,
        required: true,
    },
    latestRelease: {
        type: Object,
        default: null,
    },
    tenants: {
        type: Array,
        default: () => [],
    },
    autoRolloutEnabled: {
        type: Boolean,
        default: false,
    },
    lastRollout: {
        type: Object,
        default: () => ({}),
    },
});

const statusFilter = ref('all');
const search = ref('');
const autoRolloutEnabled = ref(props.autoRolloutEnabled);
const isSubmitting = ref(false);

const filteredTenants = computed(() => {
    return props.tenants.filter((tenant) => {
        const statusOk = statusFilter.value === 'all' || tenant.compliance_status === statusFilter.value;
        const needle = search.value.trim().toLowerCase();

        const searchOk = needle === ''
            || (tenant.name || '').toLowerCase().includes(needle)
            || (tenant.id || '').toLowerCase().includes(needle)
            || (tenant.domain || '').toLowerCase().includes(needle);

        return statusOk && searchOk;
    });
});

const statusBadgeClass = (status) => {
    if (status === 'up_to_date') return 'bg-success/20 text-success';
    if (status === 'outdated') return 'bg-warning/20 text-warning';
    if (status === 'ahead') return 'bg-info/20 text-info';
    return 'bg-base-300 text-base-content/70';
};

const statusLabel = (status) => {
    if (status === 'up_to_date') return 'Up to Date';
    if (status === 'outdated') return 'Outdated';
    if (status === 'ahead') return 'Ahead';
    return 'Unknown';
};

const formatDate = (value) => {
    if (!value) return 'N/A';
    return new Date(value).toLocaleString();
};

const saveAutoRollout = () => {
    if (isSubmitting.value) return;

    isSubmitting.value = true;
    router.post(route('admin.version-compliance.auto-rollout'), {
        enabled: !!autoRolloutEnabled.value,
    }, {
        preserveScroll: true,
        onFinish: () => {
            isSubmitting.value = false;
        },
    });
};

const triggerRollout = () => {
    if (isSubmitting.value) return;

    isSubmitting.value = true;
    router.post(route('admin.version-compliance.rollout'), {}, {
        preserveScroll: true,
        onFinish: () => {
            isSubmitting.value = false;
        },
    });
};
</script>

<template>
    <Head title="Version Compliance" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between w-full">
                <h1 class="text-xl font-bold text-base-content">Version Compliance</h1>
                <span class="text-sm font-semibold text-base-content/60">
                    Global Version: {{ globalVersion }}
                </span>
            </div>
        </template>

        <div class="max-w-7xl mx-auto space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div class="bg-base-100 border border-base-300 rounded-xl p-4 shadow-sm">
                    <p class="text-xs font-bold uppercase text-base-content/50">Total Tenants</p>
                    <p class="text-2xl font-black mt-2">{{ summary.total_tenants }}</p>
                </div>
                <div class="bg-base-100 border border-base-300 rounded-xl p-4 shadow-sm">
                    <p class="text-xs font-bold uppercase text-base-content/50">Up to Date</p>
                    <p class="text-2xl font-black mt-2 text-success">{{ summary.up_to_date }}</p>
                </div>
                <div class="bg-base-100 border border-base-300 rounded-xl p-4 shadow-sm">
                    <p class="text-xs font-bold uppercase text-base-content/50">Outdated</p>
                    <p class="text-2xl font-black mt-2 text-warning">{{ summary.outdated }}</p>
                </div>
                <div class="bg-base-100 border border-base-300 rounded-xl p-4 shadow-sm">
                    <p class="text-xs font-bold uppercase text-base-content/50">Unknown</p>
                    <p class="text-2xl font-black mt-2 text-base-content/70">{{ summary.unknown }}</p>
                </div>
                <div class="bg-base-100 border border-base-300 rounded-xl p-4 shadow-sm">
                    <p class="text-xs font-bold uppercase text-base-content/50">Ahead</p>
                    <p class="text-2xl font-black mt-2 text-info">{{ summary.ahead }}</p>
                </div>
            </div>

            <div class="bg-base-100 border border-base-300 rounded-xl p-4 shadow-sm flex flex-col lg:flex-row lg:items-center gap-3 lg:gap-4">
                <div class="w-full lg:w-80">
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search tenant id, name, or domain"
                        class="input input-bordered w-full"
                    />
                </div>

                <div class="w-full lg:w-60">
                    <select v-model="statusFilter" class="select select-bordered w-full">
                        <option value="all">All Statuses</option>
                        <option value="up_to_date">Up to Date</option>
                        <option value="outdated">Outdated</option>
                        <option value="unknown">Unknown</option>
                        <option value="ahead">Ahead</option>
                    </select>
                </div>

                <div class="text-sm text-base-content/60 lg:ml-auto">
                    Showing {{ filteredTenants.length }} of {{ tenants.length }} tenants
                </div>
            </div>

            <div class="bg-base-100 border border-base-300 rounded-xl p-4 shadow-sm space-y-4">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div>
                        <h3 class="text-base font-bold text-base-content">Automatic Rollout</h3>
                        <p class="text-sm text-base-content/60">When enabled, new detected releases will automatically queue upgrade jobs for outdated tenants.</p>
                    </div>

                    <div class="flex items-center gap-3">
                        <input
                            id="auto-rollout"
                            v-model="autoRolloutEnabled"
                            type="checkbox"
                            class="toggle toggle-primary"
                            :style="{ '--tglbg': primaryColor }"
                        />
                        <label for="auto-rollout" class="text-sm font-medium text-base-content">Enable Auto Rollout</label>
                        <button class="btn btn-sm btn-primary" :disabled="isSubmitting" @click="saveAutoRollout">
                            Save
                        </button>
                    </div>
                </div>

                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 border-t border-base-300 pt-4">
                    <div class="text-sm text-base-content/70">
                        Last rollout batch:
                        <span class="font-semibold">{{ lastRollout?.batch_id || 'N/A' }}</span>
                        <span class="ml-2">Target version: <span class="font-semibold">{{ lastRollout?.target_version || 'N/A' }}</span></span>
                        <span class="ml-2">Queued jobs: <span class="font-semibold">{{ lastRollout?.total_jobs ?? 0 }}</span></span>
                    </div>

                    <button class="btn btn-sm btn-outline" :disabled="isSubmitting" @click="triggerRollout">
                        Run Rollout Now
                    </button>
                </div>
            </div>

            <div class="bg-base-100 border border-base-300 rounded-xl shadow-sm overflow-x-auto">
                <table class="table w-full">
                    <thead>
                        <tr>
                            <th>Tenant</th>
                            <th>Domain</th>
                            <th>Tenant Version</th>
                            <th>Global Version</th>
                            <th>Status</th>
                            <th>Updated At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="tenant in filteredTenants" :key="tenant.id">
                            <td>
                                <div class="font-semibold">{{ tenant.name }}</div>
                                <div class="text-xs text-base-content/60">{{ tenant.id }}</div>
                            </td>
                            <td>{{ tenant.domain || 'N/A' }}</td>
                            <td>{{ tenant.tenant_version || 'N/A' }}</td>
                            <td>{{ tenant.global_version }}</td>
                            <td>
                                <span class="px-2 py-1 rounded-full text-xs font-semibold" :class="statusBadgeClass(tenant.compliance_status)">
                                    {{ statusLabel(tenant.compliance_status) }}
                                </span>
                            </td>
                            <td>{{ formatDate(tenant.last_updated_at) }}</td>
                        </tr>
                        <tr v-if="filteredTenants.length === 0">
                            <td colspan="6" class="text-center py-10 text-base-content/50">
                                No tenants match the current filters.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="bg-base-100 border border-base-300 rounded-xl p-4 shadow-sm text-sm text-base-content/70">
                <p>
                    Latest release:
                    <span class="font-semibold">{{ latestRelease?.version || 'N/A' }}</span>
                    <span v-if="latestRelease?.released_at">(released {{ formatDate(latestRelease.released_at) }})</span>
                </p>
                <p v-if="latestRelease?.requires_db_update" class="mt-1 text-warning font-medium">
                    This release requires database updates.
                </p>
            </div>
        </div>
    </AdminLayout>
</template>
