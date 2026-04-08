<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const page = usePage();
const branding = computed(() => page.props.branding || {});
const primaryColor = computed(() => branding.value.primary_color || '#0ea5e9');

const props = defineProps({
    rows: {
        type: Array,
        default: () => [],
    },
    total_live_tenants: {
        type: Number,
        default: 0,
    },
});

const formatDate = (iso) => {
    if (!iso) return 'N/A';
    const d = new Date(iso);
    return Number.isNaN(d.getTime()) ? 'N/A' : d.toLocaleDateString();
};
</script>

<template>
    <Head title="Release Adoption" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between w-full">
                <h2 class="font-bold text-xl text-base-content leading-tight">Release Adoption</h2>
                <Link
                    :href="route('admin.features.index')"
                    class="btn btn-sm border-0 text-white hover:brightness-110"
                    :style="{ backgroundColor: primaryColor }"
                >
                    Back to Features
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-base-100 border border-base-300 rounded-xl p-4 mb-4">
                    <p class="text-sm text-base-content/70">
                        Live tenants tracked: <span class="font-semibold text-base-content">{{ total_live_tenants }}</span>
                    </p>
                </div>

                <div class="bg-base-100 border border-base-300 rounded-xl overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="table w-full">
                            <thead>
                                <tr>
                                    <th>Release</th>
                                    <th>Feature</th>
                                    <th>Released</th>
                                    <th class="text-right">Targeted</th>
                                    <th class="text-right">Applied</th>
                                    <th class="text-right">Pending</th>
                                    <th class="text-right">Dismissed</th>
                                    <th class="text-right">Adoption</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in rows" :key="row.feature_id">
                                    <td class="font-semibold">{{ row.release_version || 'Unversioned' }}</td>
                                    <td>
                                        <div class="font-medium">{{ row.feature_name }}</div>
                                        <div class="text-xs text-base-content/50">{{ row.feature_key }}</div>
                                    </td>
                                    <td>{{ formatDate(row.released_at) }}</td>
                                    <td class="text-right">{{ row.targeted_tenants }}</td>
                                    <td class="text-right text-success font-semibold">{{ row.applied }}</td>
                                    <td class="text-right text-warning font-semibold">{{ row.pending }}</td>
                                    <td class="text-right text-base-content/60">{{ row.dismissed }}</td>
                                    <td class="text-right">
                                        <span class="badge badge-outline" :style="{ borderColor: primaryColor, color: primaryColor }">
                                            {{ row.adoption_rate }}%
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="rows.length === 0">
                                    <td colspan="8" class="text-center text-base-content/50 py-10">
                                        No release-linked features found yet.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
