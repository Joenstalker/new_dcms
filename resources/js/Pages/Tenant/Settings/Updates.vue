<script setup>
import { ref, computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    pending_updates: {
        type: Array,
        default: () => []
    },
    subscription: {
        type: Object,
        default: null
    }
});

const isApplying = ref(false);
const applyingIds = ref([]);

const form = useForm({
    feature_ids: [],
});

const hasUpdates = computed(() => props.pending_updates?.length > 0);

const pendingUpdates = computed(() => {
    return props.pending_updates?.map(update => ({
        ...update,
        feature: {
            ...update.feature,
            // Ensure all required fields exist
            name: update.feature?.name || 'Unknown Feature',
            description: update.feature?.description || '',
            implementation_status: update.feature?.implementation_status || 'coming_soon',
            code_identifier: update.feature?.code_identifier || null,
            system_release: update.feature?.system_release || null,
        }
    })) || [];
});

const activeUpdates = computed(() => {
    return pendingUpdates.value.filter(u => u.feature.implementation_status === 'active');
});

const selectAll = () => {
    if (form.feature_ids.length === activeUpdates.value.length) {
        form.feature_ids = [];
    } else {
        form.feature_ids = activeUpdates.value.map(u => u.feature.id);
    }
};

const loadingProgress = ref(0);
let loadingInterval = null;
const isChecking = ref(false);

const checkForUpdates = async () => {
    if (isChecking.value) return;
    isChecking.value = true;
    try {
        const res = await fetch(route('settings.updates.check'));
        if (!res.ok) throw new Error('Network response was not ok');
        const data = await res.json();

        if (data.has_updates) {
            const updates = data.updates || [];
            const count = updates.length;

            // Build HTML list of updates with release notes
            let html = `<p>Found ${count} update(s):</p><div style="text-align:left; margin-top:8px;">`;
            updates.forEach((u) => {
                html += `<div style="margin-bottom:12px;"><strong>${u.name}</strong>`;
                if (u.system_release && u.system_release.version) {
                    html += ` <span style="color:#2563eb;">(${u.system_release.version})</span>`;
                }
                html += `<div style="margin-top:6px; color:#374151; font-size:13px;">${u.description || ''}</div>`;
                if (u.system_release && u.system_release.release_notes) {
                    html += `<details style="margin-top:8px;"><summary style="cursor:pointer; color:#6b7280; font-size:13px">Release notes</summary><div style="margin-top:6px; white-space:pre-wrap; color:#374151; font-size:13px">${u.system_release.release_notes}</div></details>`;
                }
                if (u.system_release && u.system_release.requires_db_update) {
                    html += `<div style="margin-top:8px; padding:8px; background:#fff7ed; border-radius:6px; color:#92400e; font-size:13px">This update requires database changes.</div>`;
                }
                html += `</div>`;
            });
            html += `</div>`;

            const result = await Swal.fire({
                title: 'Updates Available',
                html,
                width: '720px',
                icon: 'info',
                showCancelButton: true,
                confirmButtonText: 'View Updates',
                cancelButtonText: 'Later',
            });

            if (result.isConfirmed) {
                // navigate to updates page so user can apply
                window.location.href = route('settings.updates');
            }
        } else {
            await Swal.fire({
                title: "You're all up to date!",
                text: 'No new updates are available at this time.',
                icon: 'success',
                confirmButtonText: 'OK'
            });
        }
    } catch (e) {
        console.error(e);
        await Swal.fire({
            title: 'Check Failed',
            text: 'Unable to check for updates. Please try again later.',
            icon: 'error',
            confirmButtonText: 'OK'
        });
    } finally {
        isChecking.value = false;
    }
};

const applyUpdates = () => {
    if (form.feature_ids.length === 0) return;
    
    isApplying.value = true;
    loadingProgress.value = 0;
    applyingIds.value = [...form.feature_ids];
    
    // Simulate robust progress feeling
    loadingInterval = setInterval(() => {
        if (loadingProgress.value < 99) {
            loadingProgress.value += Math.floor(Math.random() * 10) + 2;
            if (loadingProgress.value > 99) loadingProgress.value = 99;
        }
    }, 150);
    
    form.post(route('settings.updates.apply'), {
        onSuccess: () => {
            clearInterval(loadingInterval);
            loadingProgress.value = 100;
            setTimeout(() => {
                isApplying.value = false;
                applyingIds.value = [];
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Updates successfully applied!',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                });
            }, 300);
        },
        onError: () => {
            clearInterval(loadingInterval);
            isApplying.value = false;
            applyingIds.value = [];
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'error',
                title: 'Operation Failed',
                text: 'Something went wrong during the update procedure.',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        }
    });
};

const getStatusBadge = (status) => {
    const badges = {
        'coming_soon': { label: 'Coming Soon', class: 'badge-warning', icon: '⏳' },
        'in_development': { label: 'In Development', class: 'badge-info', icon: '🔧' },
        'active': { label: 'Ready', class: 'badge-success', icon: '✅' },
    };
    return badges[status] || { label: status, class: 'badge-ghost', icon: '❓' };
};

const isApplyingFeature = (featureId) => {
    return applyingIds.value.includes(featureId);
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="System Updates" />
        
        <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8 flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">System Updates</h2>
                    <p class="mt-1 text-sm text-gray-600">
                        New features available for your clinic. Click Update to apply.
                    </p>
                </div>
                <button 
                    @click.prevent="checkForUpdates" 
                    :disabled="isChecking" 
                    class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors shadow-sm disabled:opacity-50"
                >
                    <span v-if="isChecking" class="flex items-center">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-gray-700" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Checking...
                    </span>
                    <span v-else class="flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Check for Updates
                    </span>
                </button>
            </div>

            <!-- Plan Info (Removed) -->

            <!-- No Updates -->
            <div v-if="!hasUpdates" class="text-center py-16 bg-gray-50 rounded-lg border-2 border-dashed border-gray-200">
                <svg class="mx-auto h-16 w-16 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900">You're all up to date!</h3>
                <p class="mt-2 text-gray-500 max-w-sm mx-auto">
                    No new updates available at this time. You can click the "Check for Updates" button above to manually refresh.
                </p>
            </div>

            <!-- Updates List -->
            <div v-else class="space-y-4">
                <!-- Select All -->
                <div class="flex items-center justify-between bg-gray-50 p-4 rounded-lg border border-gray-200">
                    <label for="select-all-updates" class="flex items-center cursor-pointer group">
                        <div class="relative flex items-center">
                            <input 
                                id="select-all-updates"
                                type="checkbox" 
                                :checked="form.feature_ids.length === activeUpdates.length && activeUpdates.length > 0"
                                :disabled="activeUpdates.length === 0"
                                @change="selectAll"
                                class="absolute h-6 w-6 cursor-pointer opacity-0"
                            />
                            <div 
                                class="w-6 h-6 border-2 rounded-md transition-all duration-200 flex items-center justify-center cursor-pointer"
                                :class="form.feature_ids.length === activeUpdates.length && activeUpdates.length > 0 ? 'bg-primary border-primary' : 'bg-white border-gray-300 group-hover:border-primary'"
                            >
                                <svg v-if="form.feature_ids.length === activeUpdates.length && activeUpdates.length > 0" class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                        </div>
                        <span class="ml-3 font-medium text-gray-700">Select All Updates</span>
                    </label>
                    <span class="text-sm text-gray-500">{{ activeUpdates.length }} ready to install ({{ pendingUpdates.length - activeUpdates.length }} coming soon)</span>
                </div>

                <!-- Update Cards -->
                <div 
                    v-for="update in pendingUpdates" 
                    :key="update.id"
                    class="border-2 rounded-lg p-5 hover:shadow-md transition-all duration-200 cursor-pointer"
                    :class="form.feature_ids.includes(update.feature.id) ? 'border-blue-500 bg-blue-50' : 'border-gray-200 bg-white'"
                    @click="update.feature.implementation_status === 'active' && !isApplying ? (form.feature_ids.includes(update.feature.id) ? form.feature_ids = form.feature_ids.filter(id => id !== update.feature.id) : form.feature_ids.push(update.feature.id)) : null"
                >
                    <div class="flex items-start gap-4">
                        <div class="relative flex items-center mt-1">
                            <input 
                                type="checkbox" 
                                :checked="form.feature_ids.includes(update.feature.id)"
                                :value="update.feature.id"
                                class="sr-only"
                                :disabled="isApplying || update.feature.implementation_status !== 'active'"
                            />
                            <div 
                                class="w-6 h-6 border-2 rounded-md transition-all duration-200 flex items-center justify-center"
                                :class="[
                                    form.feature_ids.includes(update.feature.id) ? 'bg-primary border-primary' : 'bg-white border-gray-300 hover:border-primary',
                                    (isApplying || update.feature.implementation_status !== 'active') ? 'opacity-50 cursor-not-allowed' : ''
                                ]"
                            >
                                <svg v-if="form.feature_ids.includes(update.feature.id)" class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                        </div>
                        
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2 flex-wrap">
                                <h3 class="font-semibold text-lg text-gray-900">{{ update.feature.name }}</h3>
                                <span 
                                    class="badge badge-sm gap-1"
                                    :class="getStatusBadge(update.feature.implementation_status).class"
                                >
                                    <span>{{ getStatusBadge(update.feature.implementation_status).icon }}</span>
                                    {{ getStatusBadge(update.feature.implementation_status).label }}
                                </span>

                                <!-- System Version Badge -->
                                <span v-if="update.feature.type === 'system_version'" class="badge badge-outline badge-primary badge-sm">
                                    System Release
                                </span>
                            </div>
                            
                            <!-- Changelog Rendering -->
                            <div v-if="update.feature.system_release" class="mb-4">
                                <div class="bg-gray-50 rounded-lg p-3 border border-gray-100 max-h-60 overflow-y-auto">
                                    <div class="text-xs font-bold uppercase text-gray-400 mb-2 font-mono flex items-center justify-between">
                                        Release Notes
                                        <span class="text-primary">{{ update.feature.system_release.version }}</span>
                                    </div>
                                    <div class="text-sm text-gray-700 whitespace-pre-wrap leading-relaxed">
                                        {{ update.feature.system_release.release_notes }}
                                    </div>
                                </div>
                            </div>
                            
                            <p v-else class="text-gray-600 text-sm mb-3">{{ update.feature.description }}</p>
                            
                            <!-- Database Migration Warning -->
                            <div v-if="update.feature.system_release?.requires_db_update" class="text-xs text-red-700 bg-red-50 border border-red-100 px-3 py-2 rounded-lg inline-flex items-center gap-2 mb-2 w-full">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <span class="font-bold">CRITICAL:</span> This update includes database structure changes. System may be briefly unavailable during peak application.
                            </div>

                            <!-- Warning for non-active features -->
                            <div v-if="update.feature.implementation_status !== 'active'" class="text-xs text-amber-700 bg-amber-50 px-3 py-2 rounded-lg inline-block mb-2">
                                <span class="font-medium">⚠️ Note:</span> This feature is not yet fully available. You can update now but may need to wait for full functionality.
                            </div>
                            
                            <!-- Code identifier if available -->
                            <div v-if="update.feature.code_identifier" class="text-xs text-gray-400 mt-2">
                                Feature ID: <code class="bg-gray-100 px-1 rounded">{{ update.feature.code_identifier }}</code>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Apply Button (Only show if there are active updates to install) -->
                <div v-if="activeUpdates.length > 0" class="mt-8 flex items-center justify-start gap-4 pb-8">
                    <button 
                        @click="applyUpdates"
                        :disabled="form.feature_ids.length === 0 || isApplying"
                        class="btn btn-primary px-6"
                        :class="[isApplying ? 'cursor-wait' : 'hover:-translate-y-0.5 transition-transform']"
                    >
                        <span v-if="isApplying" class="loading loading-spinner loading-sm"></span>
                        <svg v-else class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        {{ isApplying ? `Applying Updates... ${loadingProgress}%` : 'Apply Selected Updates' }}
                    </button>
                    
                    <p v-if="form.feature_ids.length === 0" class="text-sm text-gray-400">
                        {{ pendingUpdates.every(u => u.feature.implementation_status !== 'active') 
                            ? 'Roadmap items are not yet installable.' 
                            : 'Select at least one active update to proceed.' 
                        }}
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
