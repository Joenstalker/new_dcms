<template>
    <div class="space-y-4">
        <!-- Top Section: Staff List & Role Defaults -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-stretch">
            <!-- Staff List Sidebar -->
            <div class="md:col-span-1 bg-white/95 backdrop-blur-md rounded-2xl border border-base-300 shadow-md overflow-hidden flex flex-col">
                <div class="p-4 border-b border-base-200 bg-base-100/50 flex justify-between items-center shrink-0">
                    <h3 class="text-[10px] font-black text-gray-900 uppercase tracking-widest">Staff</h3>
                    <button @click="selectAllStaff" class="text-[10px] font-black text-blue-600 uppercase hover:text-blue-800 transition-colors">
                        {{ selectedStaffIds.length === staff.length ? 'Deselect All' : 'Select All' }}
                    </button>
                </div>
                <div class="divide-y divide-base-200 overflow-y-auto max-h-[215px] md:max-h-[215px] flex-1 custom-scrollbar">
                    <div
                        v-for="member in staff" 
                        :key="member.id"
                        @click="toggleStaffSelection(member)"
                        :class="isStaffSelected(member.id) ? 'bg-blue-50/80' : 'hover:bg-base-200/50'"
                        class="w-full text-left p-4 transition-all flex items-center justify-between cursor-pointer group"
                    >
                        <div class="flex flex-col truncate mr-2">
                            <span class="font-black text-sm text-gray-900 tracking-tight" :class="isStaffSelected(member.id) ? 'text-blue-700' : ''">{{ member.name }}</span>
                            <span class="text-[10px] uppercase font-black tracking-widest opacity-40">{{ member.roles?.[0]?.name }}</span>
                        </div>
                        <div 
                            class="h-5 w-5 rounded-lg border-2 flex items-center justify-center transition-all duration-300"
                            :class="isStaffSelected(member.id) ? 'bg-blue-600 border-blue-600 shadow-lg shadow-blue-500/30' : 'border-base-300 group-hover:border-base-400'"
                        >
                            <svg v-if="isStaffSelected(member.id)" xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-white" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Role Default Permissions -->
            <div v-if="canManageDefaultPermissions" class="md:col-span-4 bg-white/95 backdrop-blur-md rounded-2xl border border-base-300 shadow-md p-6 flex flex-col">
                <div class="mb-6 border-b border-base-200 pb-4 shrink-0">
                    <div>
                        <h3 class="text-xl font-black text-gray-900 uppercase tracking-tight">Role Default Permissions</h3>
                        <p class="text-xs font-bold text-gray-500 mt-1">Manage standard access levels for clinic roles.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 flex-1">
                    <button
                        v-for="role in managedRoles"
                        :key="`defaults-${role}`"
                        type="button"
                        @click="openRoleDefaultsModal(role)"
                        class="text-left rounded-2xl border border-base-200 bg-base-100/50 p-5 hover:border-blue-300 hover:bg-blue-50/40 transition-all group flex flex-col"
                    >
                        <div class="flex items-center justify-between mb-3 shrink-0">
                            <h4 class="text-sm font-black text-gray-900 uppercase tracking-widest">{{ role }} Role</h4>
                            <span class="inline-flex items-center px-3 py-1.5 rounded-xl bg-blue-100 text-blue-700 text-[9px] font-black uppercase tracking-widest group-hover:bg-blue-600 group-hover:text-white transition-colors">
                                SET DEFAULT ROLE
                            </span>
                        </div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3 shrink-0">Default permissions selected: {{ rolePermissionCount(role) }}</p>
                        <div class="flex flex-wrap gap-2 overflow-y-auto">
                            <span
                                v-for="permission in previewRolePermissions(role)"
                                :key="`preview-${role}-${permission}`"
                                class="px-2.5 py-1.5 rounded-lg bg-white border border-base-200 text-[9px] font-black uppercase tracking-wider text-gray-600 shadow-sm"
                            >
                                {{ permission }}
                            </span>
                            <span v-if="previewRolePermissions(role).length === 0" class="text-[10px] font-black text-gray-400 uppercase tracking-widest">No defaults selected.</span>
                        </div>
                    </button>
                </div>
            </div>
        </div>

        <!-- Bottom Section: Manage Permissions OR Permission Catalog (Full Width) -->
        <div v-if="selectedStaffIds.length > 0" class="bg-white/95 backdrop-blur-md rounded-2xl border border-base-300 shadow-md p-6 animate-fade-in w-full">
            <div class="flex justify-between items-center mb-8 border-b border-base-200 pb-4">
                <div>
                    <h3 class="text-xl font-black text-gray-900 uppercase tracking-tight">
                        Manage Permissions 
                        <span class="ml-2 px-2.5 py-0.5 rounded-full bg-blue-100 text-blue-700 text-xs font-black">
                            {{ selectedStaffIds.length }} Selected
                        </span>
                    </h3>
                    <p class="text-xs font-bold text-gray-500 mt-1 uppercase tracking-wider">Toggle features for selected staff</p>
                </div>
                <PrimaryButton 
                    @click="savePermissionsTab" 
                    :class="{ 'opacity-25': permissionForm.processing }" 
                    :disabled="permissionForm.processing"
                    class="shadow-xl shadow-blue-500/20 rounded-xl px-8"
                >
                    APPLY CHANGES
                </PrimaryButton>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                <div v-for="(group, feature) in permissionGroups" :key="feature" class="bg-base-100/50 p-5 rounded-2xl border border-base-200 hover:border-blue-100 transition-colors shadow-sm">
                    <div class="flex justify-between items-center mb-4">
                        <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] flex items-center">
                            <span class="w-2 h-2 bg-blue-500 rounded-full mr-2"></span>
                            {{ feature }}
                        </h4>
                        <label class="flex items-center space-x-2 cursor-pointer group/toggle">
                            <span class="text-[9px] font-black text-gray-400 group-hover/toggle:text-blue-500 transition-colors uppercase tracking-widest">Toggle All</span>
                            <input 
                                type="checkbox" 
                                :checked="isGroupChecked(group)"
                                :indeterminate.prop="isGroupIndeterminate(group)"
                                @change="toggleGroup(group, $event)"
                                class="h-4 w-4 rounded border-base-300 text-blue-600 focus:ring-blue-500 transition cursor-pointer"
                            >
                        </label>
                    </div>
                    <div class="space-y-3">
                        <div 
                            v-for="permission in group" 
                            :key="permission.id" 
                            @click="togglePermission(permission.name)"
                            class="flex items-center justify-between p-3 rounded-xl border border-transparent hover:border-blue-100 hover:bg-blue-50/30 transition-all cursor-pointer group"
                            :class="permission.isLocked ? 'opacity-60 cursor-not-allowed border-amber-200 bg-amber-50/30 hover:border-amber-200 hover:bg-amber-50/30' : ''"
                        >
                            <div class="flex items-center space-x-3">
                                <div 
                                    class="h-6 w-6 rounded-lg border-2 flex items-center justify-center transition-all duration-300"
                                    :class="permissionForm.permissions.includes(permission.name) ? 'bg-blue-600 border-blue-600 shadow-md shadow-blue-500/20' : 'border-gray-200 group-hover:border-base-400'"
                                >
                                    <svg v-if="permissionForm.permissions.includes(permission.name)" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-white" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div>
                                    <span class="text-sm font-black transition-colors tracking-tight" :class="permissionForm.permissions.includes(permission.name) ? 'text-gray-900' : 'text-gray-500'">{{ permission.displayName }}</span>
                                    <p v-if="permission.isLocked" class="text-[10px] font-black uppercase tracking-wider text-amber-600 mt-1">
                                        Locked{{ permission.requiredPlan ? ` • ${permission.requiredPlan}` : '' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="bg-white/95 backdrop-blur-md rounded-2xl border border-base-300 shadow-md p-6 w-full">
            <div class="mb-6">
                <h3 class="text-xl font-black text-gray-900 uppercase tracking-widest">Permission Catalog</h3>
                <p class="text-xs font-bold text-gray-500 mt-1 uppercase tracking-wider">Select staff from the sidebar to edit permissions.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                <div v-for="(group, feature) in permissionGroups" :key="`readonly-${feature}`" class="rounded-xl border border-base-200 bg-base-100/50 p-4 shadow-sm">
                    <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3">{{ feature }}</h4>
                    <div class="space-y-2">
                        <div v-for="permission in group" :key="`readonly-${permission.id}`" class="px-3 py-2.5 rounded-lg bg-white border border-base-200 text-[10px] font-black uppercase tracking-widest text-gray-600">
                            <div class="flex items-center justify-between gap-2">
                                <span>{{ permission.displayName }}</span>
                                <span v-if="permission.isLocked" class="inline-flex items-center px-2 py-0.5 rounded-md bg-amber-100 text-amber-700 text-[9px] font-black uppercase tracking-wider">
                                    {{ permission.requiredPlan || 'UPGRADE' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div
        v-if="canManageDefaultPermissions && activeRoleModal"
        class="fixed inset-0 z-[120] flex items-center justify-center bg-black/45 p-4"
        @click.self="closeRoleDefaultsModal"
    >
        <div class="role-defaults-modal-surface w-full max-w-3xl rounded-2xl border border-gray-100 shadow-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <p class="text-[10px] font-black text-blue-600 uppercase tracking-widest">Set Default Role</p>
                    <h3 class="text-xl font-black text-gray-900 uppercase tracking-wide mt-1">{{ activeRoleModal }} Role</h3>
                    <p class="text-xs text-gray-500 mt-1">Click a permission to toggle it as default for new {{ activeRoleModal }} staff.</p>
                </div>
                <button type="button" @click="closeRoleDefaultsModal" class="h-9 w-9 rounded-lg border border-gray-200 text-gray-500 hover:text-gray-900 hover:border-gray-300 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="p-6 max-h-[62vh] overflow-y-auto">
                <div class="space-y-5">
                    <div
                        v-for="(group, feature) in permissionGroups"
                        :key="`modal-group-${feature}`"
                        class="role-defaults-modal-group rounded-xl border border-gray-100 p-4"
                    >
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="text-xs font-black uppercase tracking-widest text-gray-500">{{ feature }}</h4>
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] font-black uppercase tracking-wider text-gray-400">{{ roleGroupCheckedCount(activeRoleModal, group) }}/{{ group.length }}</span>
                                <button
                                    type="button"
                                    @click="selectRoleGroupPermissions(activeRoleModal, group)"
                                    class="px-2 py-1 rounded-md border border-blue-200 bg-blue-50 text-blue-700 text-[10px] font-black uppercase tracking-wider hover:bg-blue-100"
                                >
                                    Select All
                                </button>
                                <button
                                    type="button"
                                    @click="clearRoleGroupPermissions(activeRoleModal, group)"
                                    class="role-defaults-modal-clear px-2 py-1 rounded-md border border-gray-200 text-gray-600 text-[10px] font-black uppercase tracking-wider hover:bg-gray-100"
                                >
                                    Clear
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <button
                                v-for="permission in group"
                                :key="`modal-${activeRoleModal}-${permission.name}`"
                                type="button"
                                @click="toggleDefaultPermission(activeRoleModal, permission.name)"
                                class="role-defaults-modal-permission w-full px-4 py-3 rounded-xl border text-left transition-all flex items-center justify-between"
                                :class="permission.isLocked
                                    ? 'border-amber-200 bg-amber-50 text-amber-700 opacity-70 cursor-not-allowed'
                                    : (isDefaultPermissionChecked(activeRoleModal, permission.name)
                                    ? 'border-blue-500 bg-blue-50 text-blue-800'
                                    : 'border-gray-200 text-gray-700 hover:border-blue-200 hover:bg-blue-50/40')"
                                :disabled="permission.isLocked"
                            >
                                <div class="pr-2">
                                    <span class="text-xs font-black uppercase tracking-wide">{{ permission.displayName }}</span>
                                    <p v-if="permission.isLocked" class="text-[10px] font-black uppercase tracking-wider mt-1">
                                        Locked{{ permission.requiredPlan ? ` • ${permission.requiredPlan}` : '' }}
                                    </p>
                                </div>
                                <span
                                    class="h-6 w-6 rounded-lg border-2 flex items-center justify-center shrink-0"
                                    :class="isDefaultPermissionChecked(activeRoleModal, permission.name)
                                        ? 'bg-blue-600 border-blue-600 text-white'
                                        : 'border-gray-300 text-transparent'"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                <p class="text-xs font-bold text-gray-500">Selected: {{ rolePermissionCount(activeRoleModal) }}</p>
                <div class="flex items-center gap-3">
                    <button type="button" @click="closeRoleDefaultsModal" class="px-4 py-2 rounded-lg border border-gray-200 text-xs font-black uppercase tracking-wider text-gray-600 hover:bg-gray-50">
                        Cancel
                    </button>
                    <PrimaryButton
                        @click="saveRoleDefaults"
                        :class="{ 'opacity-25': defaultMapForm.processing }"
                        :disabled="defaultMapForm.processing"
                    >
                        Save Defaults
                    </PrimaryButton>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    staff: { type: Array, required: true },
    allPermissions: { type: Array, required: true },
    defaultPermissionMap: {
        type: Object,
        default: () => ({ Dentist: [], Assistant: [] }),
    }
});

const page = usePage();
const roles = computed(() => page.props.auth?.user?.roles || []);
const userPermissions = computed(() => page.props.auth?.user?.permissions || []);
const tenantPlanFeatures = computed(() => page.props.tenant_plan?.features || {});
const featureRequirementMap = computed(() => page.props.tenant_plan?.feature_requirements || {});
const canManageDefaultPermissions = computed(() => {
    return roles.value.includes('Owner') || userPermissions.value.includes('edit staff');
});
const managedRoles = ['Dentist', 'Assistant'];
const activeRoleModal = ref(null);

const selectedStaffIds = ref([]);
const permissionForm = useForm({
    staff_ids: [],
    permissions: []
});

const defaultMapForm = useForm({
    default_permission_map: {
        Dentist: [],
        Assistant: [],
    },
});

watch(() => props.defaultPermissionMap, (map) => {
    defaultMapForm.default_permission_map = {
        Dentist: [...(map?.Dentist || [])],
        Assistant: [...(map?.Assistant || [])],
    };
}, { deep: true, immediate: true });

const isStaffSelected = (id) => selectedStaffIds.value.includes(id);

const toggleStaffSelection = (member) => {
    const index = selectedStaffIds.value.indexOf(member.id);
    if (index > -1) {
        selectedStaffIds.value.splice(index, 1);
    } else {
        selectedStaffIds.value.push(member.id);
    }
    
    // Update permission form if exactly one staff is selected to show their current perms
    if (selectedStaffIds.value.length === 1) {
        const selectedStaff = props.staff.find(s => s.id === selectedStaffIds.value[0]);
        permissionForm.permissions = selectedStaff.permissions.map(p => p.name);
    } else if (selectedStaffIds.value.length === 0) {
        permissionForm.permissions = [];
    }
};

// Keep permissions in sync when staff props are updated (from backend refresh)
watch(() => props.staff, (newStaff) => {
    if (selectedStaffIds.value.length === 1) {
        const selectedStaff = newStaff.find(s => s.id === selectedStaffIds.value[0]);
        if (selectedStaff) {
            permissionForm.permissions = selectedStaff.permissions.map(p => p.name);
        }
    }
}, { deep: true });

const selectAllStaff = () => {
    if (selectedStaffIds.value.length === props.staff.length) {
        selectedStaffIds.value = [];
    } else {
        selectedStaffIds.value = props.staff.map(s => s.id);
    }
};

const togglePermission = (name) => {
    if (isPermissionLocked(name)) {
        return;
    }

    const index = permissionForm.permissions.indexOf(name);
    if (index > -1) {
        permissionForm.permissions.splice(index, 1);
    } else {
        permissionForm.permissions.push(name);
    }
};

const savePermissionsTab = () => {
    permissionForm.staff_ids = selectedStaffIds.value;
    permissionForm.post(route('staff.bulk-update-permissions'), {
        onSuccess: () => {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
            });

            Toast.fire({
                icon: 'success',
                title: 'Permissions synced successfully'
            });
        },
        preserveScroll: true
    });
};

const saveDefaultPermissionMap = () => {
    defaultMapForm.post(route('staff.default-permissions.update'), {
        preserveScroll: true,
        onSuccess: () => {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
            });

            Toast.fire({
                icon: 'success',
                title: 'Default role permissions saved'
            });
        },
    });
};

const saveRoleDefaults = () => {
    saveDefaultPermissionMap();
    closeRoleDefaultsModal();
};

const openRoleDefaultsModal = (role) => {
    activeRoleModal.value = role;
};

const closeRoleDefaultsModal = () => {
    activeRoleModal.value = null;
};

const rolePermissionCount = (role) => {
    return defaultMapForm.default_permission_map?.[role]?.length || 0;
};

const roleGroupCheckedCount = (role, groupPermissions) => {
    if (!role || !Array.isArray(groupPermissions)) return 0;
    const selected = defaultMapForm.default_permission_map?.[role] || [];
    return groupPermissions.filter((permission) => selected.includes(permission.name)).length;
};

const selectRoleGroupPermissions = (role, groupPermissions) => {
    if (!role || !Array.isArray(groupPermissions)) return;

    const selected = defaultMapForm.default_permission_map?.[role] || [];
    const selectedSet = new Set(selected);

    groupPermissions.forEach((permission) => {
        if (!permission.isLocked) {
            selectedSet.add(permission.name);
        }
    });

    defaultMapForm.default_permission_map[role] = Array.from(selectedSet);
};

const clearRoleGroupPermissions = (role, groupPermissions) => {
    if (!role || !Array.isArray(groupPermissions)) return;

    const groupNames = new Set(groupPermissions.map((permission) => permission.name));
    const selected = defaultMapForm.default_permission_map?.[role] || [];

    defaultMapForm.default_permission_map[role] = selected.filter((permission) => !groupNames.has(permission));
};

const previewRolePermissions = (role) => {
    const selected = defaultMapForm.default_permission_map?.[role] || [];
    return selected.slice(0, 3);
};

const isDefaultPermissionChecked = (role, permission) => {
    return defaultMapForm.default_permission_map?.[role]?.includes(permission) || false;
};

const toggleDefaultPermission = (role, permission) => {
    if (isPermissionLocked(permission)) {
        return;
    }

    const current = defaultMapForm.default_permission_map?.[role] || [];
    const exists = current.includes(permission);

    defaultMapForm.default_permission_map[role] = exists
        ? current.filter((item) => item !== permission)
        : [...current, permission];
};

const permissionCatalog = computed(() => {
    const baseCatalog = (props.allPermissions || [])
        .filter((permission) => permission.name !== 'manage clinic')
        .map((permission) => {
            const displayName = permission.name
                .trim()
                .split(' ')
                .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                .join(' ');

            return {
                ...permission,
                displayName,
                isLocked: isPermissionLocked(permission.name),
                requiredPlan: requiredPlanForPermission(permission.name),
            };
        });

    const existingNames = new Set(baseCatalog.map((permission) => permission.name));
    const gatedExtras = Object.keys(permissionFeatureMap)
        .filter((permissionName) => !existingNames.has(permissionName) && isPermissionLocked(permissionName))
        .map((permissionName, index) => ({
            id: `virtual-locked-${index}`,
            name: permissionName,
            displayName: permissionName
                .trim()
                .split(' ')
                .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                .join(' '),
            isLocked: true,
            requiredPlan: requiredPlanForPermission(permissionName),
        }));

    return [...baseCatalog, ...gatedExtras]
        .sort((a, b) => a.displayName.localeCompare(b.displayName));
});

const permissionFeatureMap = {
    'manage clinic branding': 'custom_branding',
    'view analytics': 'advanced_analytics',
    'manage branches': 'multi_branch',
    'manage system features': 'custom_system_features',
    'manage security settings': 'security_settings',
    'manage own notifications': 'sms_notifications',
};

const requiredPlanForPermission = (permissionName) => {
    const featureKey = permissionFeatureMap[permissionName];

    if (!featureKey) {
        return null;
    }

    return featureRequirementMap.value?.[featureKey] || null;
};

const isPermissionLocked = (permissionName) => {
    const featureKey = permissionFeatureMap[permissionName];

    if (!featureKey) {
        return false;
    }

    return tenantPlanFeatures.value?.[featureKey] === false;
};

// Group permissions for cleaner UI (Role-centric Modules)
const permissionGroups = computed(() => {
    const groups = {};
    const featureMap = {
        // Operational Modules
        'patients': 'Patient Module',
        'appointments': 'Appointments Module',
        'billing': 'Billing & POS Module',
        'treatments': 'Treatment Records Module',
        'progress notes': 'Progress Notes Module',
        'progress note': 'Progress Notes Module',
        'medical records': 'Medical Records Module',
        'services': 'Service & Pricing Module',
        'staff': 'Staff Management Module',
        'reports': 'Reports Module',

        // Owner/Administration Features (delegatable)
        'analytics': 'Owner Features',
        'branches': 'Owner Features',
        'subscription': 'Owner Features',
        'billing portal': 'Owner Features',
        'manage clinic': 'Owner Features',
        'clinic branding': 'Owner Features',
        'security settings': 'Owner Features',
        'system features': 'Owner Features',
        'system updates': 'Owner Features',
        'manage settings': 'Owner Features',

        // Personal and Support
        'manage own calendar': 'Personal Staff Settings',
        'manage own notifications': 'Personal Staff Settings',
        'manage own working hours': 'Personal Staff Settings',
        'support': 'Support',
        'activity logs': 'Audit & Activity',
        'dashboard': 'Dashboard Access',
    };

    permissionCatalog.value.forEach(p => {
        let feature = 'General Access';
        
        // Find matching module from map
        for (const [key, label] of Object.entries(featureMap)) {
            if (p.name.includes(key)) {
                feature = label;
                break;
            }
        }

        if (!groups[feature]) groups[feature] = [];
        
        groups[feature].push(p);
    });

    // Sort permissions within each group: View -> Create -> Edit -> Delete
    for (const feature in groups) {
        groups[feature].sort((a, b) => {
            const order = { 'view': 1, 'create': 2, 'edit': 3, 'approve': 4, 'delete': 5 };
            const getOrder = (name) => {
                const firstWord = name.toLowerCase().split(' ')[0];
                return order[firstWord] || 99;
            };
            return getOrder(a.name) - getOrder(b.name);
        });
    }

    return groups;
});

const toggleGroup = (groupPermissions, event) => {
    const isChecked = event.target.checked;
    const groupNames = groupPermissions.filter(p => !p.isLocked).map(p => p.name);
    
    if (isChecked) {
        // Add permissions not already in the list
        groupNames.forEach(name => {
            if (!permissionForm.permissions.includes(name)) {
                permissionForm.permissions.push(name);
            }
        });
    } else {
        // Remove permissions in this group
        permissionForm.permissions = permissionForm.permissions.filter(
            p => !groupNames.includes(p)
        );
    }
};

const isGroupChecked = (groupPermissions) => {
    if (!groupPermissions || groupPermissions.length === 0) return false;
    return groupPermissions.every(p => permissionForm.permissions.includes(p.name));
};

const isGroupIndeterminate = (groupPermissions) => {
    if (!groupPermissions || groupPermissions.length === 0) return false;
    const checkedCount = groupPermissions.filter(p => permissionForm.permissions.includes(p.name)).length;
    return checkedCount > 0 && checkedCount < groupPermissions.length;
};
</script>

<style scoped>
.role-defaults-modal-surface,
.role-defaults-modal-group,
.role-defaults-modal-permission,
.role-defaults-modal-clear {
    background-color: #ffffff !important;
}
</style>
