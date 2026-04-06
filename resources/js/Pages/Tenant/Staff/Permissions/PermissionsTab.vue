<template>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <!-- Staff List Sidebar -->
        <div class="md:col-span-1 bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden h-fit">
            <div class="p-4 border-b border-gray-50 bg-gray-50/50 flex justify-between items-center">
                <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider">Staff</h3>
                <button @click="selectAllStaff" class="text-[10px] font-black text-blue-600 uppercase hover:text-blue-800 transition-colors">
                    {{ selectedStaffIds.length === staff.length ? 'Deselect All' : 'Select All' }}
                </button>
            </div>
            <div class="divide-y divide-gray-50">
                <div
                    v-for="member in staff" 
                    :key="member.id"
                    @click="toggleStaffSelection(member)"
                    :class="isStaffSelected(member.id) ? 'bg-blue-50/50' : 'hover:bg-gray-50'"
                    class="w-full text-left p-4 transition-all flex items-center justify-between cursor-pointer group"
                >
                    <div class="flex flex-col truncate mr-2">
                        <span class="font-bold text-sm text-gray-900" :class="isStaffSelected(member.id) ? 'text-blue-700' : ''">{{ member.name }}</span>
                        <span class="text-[10px] uppercase font-black tracking-widest opacity-30">{{ member.roles?.[0]?.name }}</span>
                    </div>
                    <div 
                        class="h-5 w-5 rounded-lg border-2 flex items-center justify-center transition-all duration-300"
                        :class="isStaffSelected(member.id) ? 'bg-blue-600 border-blue-600 shadow-lg shadow-blue-500/30' : 'border-gray-200 group-hover:border-gray-300'"
                    >
                        <svg v-if="isStaffSelected(member.id)" xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-white" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Permission Checklist Grid -->
        <div class="md:col-span-3 space-y-6">
            <div v-if="selectedStaffIds.length > 0" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 animate-fade-in">
                <div class="flex justify-between items-center mb-8 border-b pb-4">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">
                            Manage Permissions 
                            <span class="ml-2 px-2.5 py-0.5 rounded-full bg-blue-100 text-blue-700 text-xs font-black">
                                {{ selectedStaffIds.length }} Selected
                            </span>
                        </h3>
                        <p class="text-sm text-gray-500">Toggle features for all selected staff members</p>
                    </div>
                    <PrimaryButton 
                        @click="savePermissionsTab" 
                        :class="{ 'opacity-25': permissionForm.processing }" 
                        :disabled="permissionForm.processing"
                        class="shadow-lg shadow-blue-500/20"
                    >
                        Apply Changes
                    </PrimaryButton>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div v-for="(group, feature) in permissionGroups" :key="feature" class="bg-gray-50/50 p-5 rounded-2xl border border-gray-100/50 hover:border-blue-100 transition-colors">
                        <div class="flex justify-between items-center mb-4">
                            <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest flex items-center">
                                <span class="w-2 h-2 bg-blue-500 rounded-full mr-2"></span>
                                {{ feature }}
                            </h4>
                            <label class="flex items-center space-x-2 cursor-pointer group/toggle">
                                <span class="text-[10px] font-bold text-gray-400 group-hover/toggle:text-blue-500 transition-colors uppercase">Toggle All</span>
                                <input 
                                    type="checkbox" 
                                    :checked="isGroupChecked(group)"
                                    :indeterminate.prop="isGroupIndeterminate(group)"
                                    @change="toggleGroup(group, $event)"
                                    class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 transition cursor-pointer"
                                >
                            </label>
                        </div>
                        <div class="space-y-3">
                            <div 
                                v-for="permission in group" 
                                :key="permission.id" 
                                @click="togglePermission(permission.name)"
                                class="flex items-center justify-between p-3 rounded-xl border border-transparent hover:border-blue-100 hover:bg-blue-50/30 transition-all cursor-pointer group"
                            >
                                <div class="flex items-center space-x-3">
                                    <div 
                                        class="h-6 w-6 rounded-lg border-2 flex items-center justify-center transition-all duration-300"
                                        :class="permissionForm.permissions.includes(permission.name) ? 'bg-blue-600 border-blue-600 shadow-md shadow-blue-500/20' : 'border-gray-200 group-hover:border-gray-300'"
                                    >
                                        <svg v-if="permissionForm.permissions.includes(permission.name)" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-white" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <span class="text-sm font-bold transition-colors" :class="permissionForm.permissions.includes(permission.name) ? 'text-gray-900 font-black' : 'text-gray-500 font-bold'">{{ permission.displayName }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div v-else class="bg-white rounded-2xl border border-gray-100 shadow-sm p-12 text-center flex flex-col items-center justify-center h-[500px]">
                <div class="w-20 h-20 bg-blue-50 rounded-full flex items-center justify-center mb-6 shadow-inner animate-pulse">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-10 h-10 text-blue-500">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                    </svg>
                </div>
                <h3 class="text-xl font-black text-gray-900 uppercase tracking-widest">No Staff Selected</h3>
                <p class="text-gray-400 mt-2 max-w-sm mx-auto font-medium">Select one or multiple staff members from the sidebar to manage their granular permissions in bulk.</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    staff: { type: Array, required: true },
    allPermissions: { type: Array, required: true }
});

const selectedStaffIds = ref([]);
const permissionForm = useForm({
    staff_ids: [],
    permissions: []
});

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

// Group permissions for cleaner UI (Role-centric Modules)
const permissionGroups = computed(() => {
    const groups = {};
    const featureMap = {
        // Staff Settings (personal)
        'manage own calendar': 'Staff Settings',
        'manage own notifications': 'Staff Settings',
        'manage own working hours': 'Staff Settings',

        // Patient Module
        'patients': 'Patient Module',

        // Services Module
        'services': 'Services Module',
        
        // Treatment Module
        'treatments': 'Treatment Module',
        
        // Schedule Module
        'appointments': 'Schedule Module',
        'booking': 'Schedule Module',
        
        // Financial Module
        'billing': 'Financial Module',
        'reports': 'Financial Module',
        
        // Administration
        'staff': 'Administration',
        'clinic': 'Administration',
        'settings': 'Administration',
        'subscription': 'Administration',
        'dashboard': 'Administration',

        // Custom Branding (delegatable)
        'branding': 'Custom Branding',
    };

    props.allPermissions.forEach(p => {
        let feature = 'General Access';
        
        // Find matching module from map
        for (const [key, label] of Object.entries(featureMap)) {
            if (p.name.includes(key)) {
                feature = label;
                break;
            }
        }

        if (!groups[feature]) groups[feature] = [];
        
        // Make display name pretty and descriptive
        // e.g., 'view patients' -> 'View Patients'
        const displayName = p.name
            .trim()
            .split(' ')
            .map(word => word.charAt(0).toUpperCase() + word.slice(1))
            .join(' ');
        
        groups[feature].push({ ...p, displayName });
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
    const groupNames = groupPermissions.map(p => p.name);
    
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
