<template>
    <Head title="Staff Management" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Staff Management
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Premium Tab Navigation -->
                <div class="mb-8 flex items-center space-x-1 bg-gray-100/50 p-1.5 rounded-3xl w-fit border border-gray-200/50">
                    <button 
                        @click="activeTab = 'list'"
                        :class="activeTab === 'list' 
                            ? 'bg-gray-900 text-white shadow-xl shadow-gray-200' 
                            : 'text-gray-500 hover:text-gray-900 hover:bg-white/80'"
                        class="px-8 py-3 rounded-[1.25rem] text-[10px] font-black uppercase tracking-[0.2em] transition-all duration-500 flex items-center"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 mr-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        Staff Overview
                    </button>
                    <button 
                        @click="activeTab = 'permissions'"
                        :class="activeTab === 'permissions' 
                            ? 'bg-gray-900 text-white shadow-xl shadow-gray-200' 
                            : 'text-gray-500 hover:text-gray-900 hover:bg-white/80'"
                        class="px-8 py-3 rounded-[1.25rem] text-[10px] font-black uppercase tracking-[0.2em] transition-all duration-500 flex items-center"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 mr-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        Permissions
                    </button>
                    <button 
                        @click="activeTab = 'schedules'"
                        :class="activeTab === 'schedules' 
                            ? 'bg-gray-900 text-white shadow-xl shadow-gray-200' 
                            : 'text-gray-500 hover:text-gray-900 hover:bg-white/80'"
                        class="px-8 py-3 rounded-[1.25rem] text-[10px] font-black uppercase tracking-[0.2em] transition-all duration-500 flex items-center"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 mr-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        Schedules
                    </button>

                </div>

                <div v-if="activeTab === 'list'">
                    <!-- Add Staff Button -->


                    <div v-if="$page.props.flash?.success && activeTab === 'list'" class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg shadow-sm border border-green-200">
                        {{ $page.props.flash.success }}
                    </div>

                    <div v-if="limitReached" class="mb-8 animate-in fade-in slide-in-from-top-4 duration-500">
                        <div class="bg-warning/10 border border-warning/20 rounded-2xl p-5 flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-2xl bg-warning/20 flex items-center justify-center text-warning shadow-sm">
                                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-base font-black text-gray-900 tracking-tight">Staff Limit Reached</h4>
                                    <p class="text-xs text-gray-500 font-bold uppercase tracking-wider mt-0.5">Plan Max: {{ tenantLimits.max_users }} members. Upgrade to expand your team.</p>
                                </div>
                            </div>
                            <Link
                                :href="route('billing.portal')"
                                class="btn btn-sm btn-warning font-black text-white shadow-xl shadow-warning/20 rounded-[1.25rem] px-6 h-10 min-h-0 border-none"
                            >
                                Upgrade Now
                            </Link>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-gray-100 p-8">
                        <div class="flex justify-between items-center mb-8">
                            <h2 class="text-xl font-black text-gray-900 uppercase tracking-widest">Active Staff Members</h2>
                            <button 
                                @click="checkLimitAndOpenAddStaff"
                                class="inline-flex items-center px-6 py-3 bg-gray-900 text-white border border-transparent rounded-[1.25rem] font-black text-[10px] uppercase tracking-[0.25em] hover:bg-black transition-all shadow-xl shadow-gray-200 active:scale-95 group"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2.5 group-hover:rotate-90 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                                </svg>
                                Add Staff Member
                            </button>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="border-b border-gray-100 text-[10px] font-black text-gray-400 uppercase tracking-[0.25em]">
                                        <th class="pb-5 px-4 font-black">Staff Member</th>
                                        <th class="pb-5 px-4 font-black">Role</th>
                                        <th class="pb-5 px-4 font-black">Email</th>
                                        <th class="pb-5 px-4 font-black text-right">Settings</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    <tr v-for="member in staff" :key="member.id" class="group hover:bg-gray-50/70 transition-all duration-300">
                                        <td class="py-5 px-4">
                                            <div class="flex items-center space-x-4">
                                                <div class="h-12 w-12 rounded-2xl bg-blue-50/50 flex items-center justify-center font-black text-blue-600 border border-blue-100 uppercase tracking-tighter text-sm group-hover:scale-110 transition-transform duration-300 overflow-hidden">
                                                    <img v-if="member.profile_picture_url" :src="member.profile_picture_url" class="h-full w-full object-cover" />
                                                    <span v-else>{{ member.name.substring(0, 2) }}</span>
                                                </div>
                                                <div class="flex flex-col">
                                                    <span class="font-black text-gray-900 text-sm tracking-tight group-hover:text-blue-600 transition-colors">{{ member.name }}</span>
                                                    <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Staff ID #{{ member.id.toString().padStart(4, '0') }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-5 px-4">
                                            <span class="px-3 py-1.5 rounded-xl bg-gray-100 text-gray-700 text-[9px] font-black uppercase tracking-widest border border-gray-200">
                                                {{ member.roles?.[0]?.name || 'Staff' }}
                                            </span>
                                        </td>
                                        <td class="py-5 px-4">
                                            <span class="text-xs font-bold text-gray-500">{{ member.email }}</span>
                                        </td>
                                        <td class="py-5 px-4 text-right">
                                            <button 
                                                @click="selectStaffForManage(member)"
                                                class="inline-flex items-center px-5 py-2.5 bg-white border border-gray-200 rounded-2xl font-black text-[10px] text-gray-600 uppercase tracking-widest hover:bg-gray-900 hover:border-gray-900 hover:text-white transition-all shadow-sm active:scale-95 group/btn overflow-hidden relative"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 mr-2.5 group-hover/btn:rotate-45 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                                Manage
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="staff.length === 0">
                                        <td colspan="4" class="py-20 text-center bg-gray-50/50 rounded-2xl">
                                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">No staff members found in the current clinic database.</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div v-else-if="activeTab === 'permissions'">
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
                                                @click="permissionForm.permissions.includes(permission.name) ? permissionForm.permissions = permissionForm.permissions.filter(p => p !== permission.name) : permissionForm.permissions.push(permission.name)"
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
                </div>
                <div v-else-if="activeTab === 'schedules'">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-gray-100 p-8">
                        <div class="flex justify-between items-center mb-8">
                            <h2 class="text-xl font-black text-gray-900 uppercase tracking-widest">Clinic Schedule</h2>
                            <div class="flex space-x-3">
                                <select class="rounded-xl border-gray-100 bg-gray-50/50 text-[10px] font-black uppercase tracking-widest focus:ring-gray-900 focus:border-gray-900 px-6 py-3">
                                    <option value="">All Staff</option>
                                    <option v-for="member in staff" :key="member.id" :value="member.id">
                                        {{ member.name }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="calendar-container border border-gray-100 rounded-3xl overflow-hidden p-8 shadow-inner bg-gray-50/20">
                            <FullCalendar :options="calendarOptions" />
                        </div>
                    </div>
                    </div>
                </div>
        </div>



        <!-- Manage Staff Modal (Profile) -->
        <Modal :show="showingStaffModal" @close="showingStaffModal = false" maxWidth="md">
            <div class="bg-gradient-to-br from-blue-600 to-indigo-700 h-32 relative">
                <button @click="showingStaffModal = false" class="absolute top-4 right-4 h-8 w-8 bg-white/10 hover:bg-white/20 rounded-lg flex items-center justify-center text-white transition-colors">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <div class="px-8 pb-8 -mt-16">
                <div class="flex flex-col items-center text-center">
                    <div class="h-32 w-32 rounded-[2rem] bg-white p-2 shadow-2xl relative group mb-6">
                        <div class="h-full w-full rounded-[1.5rem] bg-gray-50 flex items-center justify-center border-4 border-gray-100 overflow-hidden relative group-hover:border-blue-400 transition-colors">
                            <img v-if="selectedStaff?.profile_picture_url" :src="selectedStaff.profile_picture_url" class="h-full w-full object-cover" />
                            <span v-else class="text-4xl font-black text-gray-200 uppercase tracking-tighter">{{ selectedStaff?.name?.substring(0, 2) }}</span>
                        </div>
                    </div>

                    <div class="mb-8">
                        <h2 class="text-2xl font-black text-gray-900 tracking-tight uppercase">{{ selectedStaff?.name }}</h2>
                        <div class="flex items-center justify-center space-x-2 mt-2">
                            <span class="px-3 py-1 bg-blue-50 text-blue-700 text-[10px] font-black uppercase tracking-[0.2em] rounded-lg border border-blue-100">
                                {{ selectedStaff?.roles?.[0]?.name }}
                            </span>
                            <span class="h-1.5 w-1.5 rounded-full bg-green-500 shadow-sm shadow-green-400/50"></span>
                            <span class="text-[10px] font-black text-gray-400 tracking-widest uppercase">Active</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 w-full gap-4 mb-8">
                        <div class="p-4 bg-gray-50 rounded-2xl border border-gray-100 text-left group hover:bg-blue-50/30 transition-colors">
                            <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest block mb-1">Email Address</span>
                            <span class="text-sm font-black text-gray-900 truncate">{{ selectedStaff?.email }}</span>
                        </div>
                    </div>

                    <div class="flex items-center space-x-3 w-full">
                        <button 
                            @click="editStaff(selectedStaff)"
                            class="flex-1 py-4 bg-gray-900 hover:bg-black text-white rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] shadow-xl shadow-gray-200 transition-all active:scale-95 flex items-center justify-center"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Edit Profile
                        </button>
                        <button 
                            @click="confirmDelete(selectedStaff)"
                            class="p-4 bg-red-50 hover:bg-red-100 text-red-600 rounded-2xl transition-colors active:scale-95"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </Modal>

        <!-- Edit Staff Modal -->
        <Modal :show="showingEditModal" @close="showingEditModal = false" maxWidth="md">
            <div class="bg-gradient-to-br from-amber-500 to-orange-600 h-28 relative">
                <div class="absolute -bottom-8 left-10 h-16 w-16 rounded-2xl bg-white p-2 shadow-2xl border border-white/50 flex items-center justify-center font-black text-amber-600 uppercase tracking-tighter text-lg shadow-amber-200/40 overflow-hidden">
                    <img v-if="editForm.profile_picture_url" :src="editForm.profile_picture_url" class="h-full w-full object-cover rounded-xl" />
                    <span v-else>{{ editForm.name ? editForm.name.substring(0, 2) : 'ST' }}</span>
                </div>
                <button @click="showingEditModal = false" class="absolute top-4 right-4 h-9 w-9 bg-white/10 hover:bg-white/20 rounded-xl flex items-center justify-center text-white transition-all hover:rotate-90">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="p-10 pt-12">
                <div class="mb-10 border-b border-gray-50 pb-8">
                    <h3 class="text-2xl font-black text-gray-900 tracking-tight uppercase">Update Profile</h3>
                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-[0.2em] mt-2">Modify team member credentials and roles</p>
                </div>
                
                <form @submit.prevent="updateStaff" class="space-y-8">
                    <div class="space-y-3">
                        <InputLabel for="edit_name" value="Full Name" class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1" />
                        <TextInput
                            id="edit_name"
                            type="text"
                            class="block w-full border-gray-100 focus:border-blue-500 focus:ring-blue-500 rounded-2xl p-5 font-black text-sm bg-gray-50/50 shadow-inner"
                            v-model="editForm.name"
                            required
                        />
                        <InputError class="mt-2" :message="editForm.errors.name" />
                    </div>

                    <div class="space-y-3">
                        <InputLabel for="edit_email" value="Email Address" class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1" />
                        <TextInput
                            id="edit_email"
                            type="email"
                            class="block w-full border-gray-100 focus:border-blue-500 focus:ring-blue-500 rounded-2xl p-5 font-black text-sm bg-gray-50/50 shadow-inner"
                            v-model="editForm.email"
                            required
                        />
                        <InputError class="mt-2" :message="editForm.errors.email" />
                    </div>

                    <div class="space-y-3">
                        <InputLabel for="edit_role" value="Assigned Role" class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1" />
                        <select 
                            id="edit_role"
                            v-model="editForm.role"
                            class="block w-full border-gray-100 focus:border-blue-500 focus:ring-blue-500 rounded-2xl p-5 font-black text-sm bg-gray-50/50 shadow-inner appearance-none bg-[url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%20width%3D%2224%22%20height%3D%2224%22%20viewBox%3D%220%200%2024%2024%22%20fill%3D%22none%22%20stroke%3D%22%239ca3af%22%20stroke-width%3D%222%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%3E%3Cpolyline%20points%3D%226%209%2012%2015%2018%209%22%3E%3C/polyline%3E%3C/svg%3E')] bg-[length:1.5em_1.5em] bg-[right_1.5em_center] bg-no-repeat"
                            required
                        >
                            <option value="Dentist">Dentist</option>
                            <option value="Assistant">Assistant</option>
                        </select>
                        <InputError class="mt-2" :message="editForm.errors.role" />
                    </div>

                    <div class="pt-8 flex items-center space-x-4">
                        <SecondaryButton @click="showingEditModal = false" class="flex-[0.4] py-5 justify-center border-gray-200 rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] hover:bg-gray-50 transition-all active:scale-95">
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton 
                            class="flex-1 py-5 justify-center bg-gray-900 border-none rounded-2xl font-black text-[10px] uppercase tracking-[0.3em] shadow-2xl shadow-gray-300 transition-all hover:bg-black active:scale-95" 
                            :class="{ 'opacity-25': editForm.processing }" 
                            :disabled="editForm.processing"
                        >
                            Save Changes
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Add Staff Modal -->
        <Modal :show="showingAddModal" @close="showingAddModal = false" maxWidth="md">
            <div class="bg-gradient-to-br from-blue-600 to-indigo-700 h-28 relative">
                <div class="absolute -bottom-8 left-10 h-16 w-16 rounded-2xl bg-white p-2 shadow-2xl border border-white/50 flex items-center justify-center font-black text-blue-600 uppercase tracking-tighter text-lg shadow-blue-200/40">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                </div>
                <button @click="showingAddModal = false" class="absolute top-4 right-4 h-9 w-9 bg-white/10 hover:bg-white/20 rounded-xl flex items-center justify-center text-white transition-all hover:rotate-90">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="p-10 pt-12">
                <div class="mb-10 border-b border-gray-50 pb-8">
                    <h3 class="text-2xl font-black text-gray-900 tracking-tight uppercase">New Staff Member</h3>
                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-[0.2em] mt-2">Send an invitation to join your clinic</p>
                </div>
                
                <form @submit.prevent="handleAddStaff" class="space-y-8">
                    <div class="space-y-3">
                        <InputLabel for="name" value="Full Name" class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1" />
                        <TextInput
                            id="name"
                            type="text"
                            class="block w-full border-gray-100 focus:border-blue-500 focus:ring-blue-500 rounded-2xl p-5 font-black text-sm bg-gray-50/50 shadow-inner"
                            v-model="addForm.name"
                            required
                            placeholder="e.g. Dr. John Doe"
                        />
                        <InputError class="mt-2" :message="addForm.errors.name" />
                    </div>

                    <div class="space-y-3">
                        <InputLabel for="email" value="Email Address" class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1" />
                        <TextInput
                            id="email"
                            type="email"
                            class="block w-full border-gray-100 focus:border-blue-500 focus:ring-blue-500 rounded-2xl p-5 font-black text-sm bg-gray-50/50 shadow-inner"
                            v-model="addForm.email"
                            required
                            placeholder="john@example.com"
                        />
                        <InputError class="mt-2" :message="addForm.errors.email" />
                    </div>

                    <div class="space-y-3">
                        <InputLabel for="role" value="Clinic Role" class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1" />
                        <select 
                            id="role"
                            v-model="addForm.role"
                            class="block w-full border-gray-100 focus:border-blue-500 focus:ring-blue-500 rounded-2xl p-5 font-black text-sm bg-gray-50/50 shadow-inner appearance-none bg-[url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%20width%3D%2224%22%20height%3D%2224%22%20viewBox%3D%220%200%2024%2024%22%20fill%3D%22none%22%20stroke%3D%22%239ca3af%22%20stroke-width%3D%222%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%3E%3Cpolyline%20points%3D%226%209%2012%2015%2018%209%22%3E%3C/polyline%3E%3C/svg%3E')] bg-[length:1.5em_1.5em] bg-[right_1.5em_center] bg-no-repeat"
                            required
                        >
                            <option v-for="role in roles" :key="role.id" :value="role.name">{{ role.name }}</option>
                        </select>
                        <InputError class="mt-2" :message="addForm.errors.role" />
                    </div>

                    <div class="pt-8 flex items-center space-x-4">
                        <SecondaryButton @click="showingAddModal = false" class="flex-[0.4] py-5 justify-center border-gray-200 rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] hover:bg-gray-50 transition-all active:scale-95">
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton 
                            class="flex-1 py-5 justify-center bg-gray-900 border-none rounded-2xl font-black text-[10px] uppercase tracking-[0.3em] shadow-2xl shadow-gray-400 transition-all hover:bg-black active:scale-95" 
                            :class="{ 'opacity-25': addForm.processing }" 
                            :disabled="addForm.processing"
                        >
                            Send Invitation
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>


<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Swal from 'sweetalert2';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import googleCalendarPlugin from '@fullcalendar/google-calendar';


const props = defineProps({
    staff: Array,
    roles: Array,
    allPermissions: Array,
    api_key: String,
    initialTab: { type: String, default: 'list' }
});


const page = usePage();

// Tabs State
const activeTab = ref(props.initialTab);

const tenantLimits = computed(() => usePage().props.tenant_plan?.limits || {});
const tenantUsage = computed(() => usePage().props.tenant_plan?.current_usage || {});

const limitReached = computed(() => {
    const max = tenantLimits.value.max_users;
    const current = tenantUsage.value.users || props.staff?.length || 0;
    return max !== undefined && max !== null && max !== -1 && current >= max;
});

const checkLimitAndOpenAddStaff = () => {
    const maxUsers = tenantLimits.value.max_users;
    const currentUsers = tenantUsage.value.users || props.staff?.length || 0;
    
    if (maxUsers !== undefined && maxUsers !== null && currentUsers >= maxUsers) {
        Swal.fire({
            title: 'Staff Limit Reached',
            text: `Your plan allows up to ${maxUsers} staff members. You currently have ${currentUsers}. Please upgrade to grow your team.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#111827',
            cancelButtonColor: '#94a3b8',
            confirmButtonText: 'View Upgrades',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                router.get(route('settings.features'));
            }
        });
        return;
    }
    showingAddModal.value = true;
};

// Add Staff Modal State
const showingAddModal = ref(false);
const addForm = useForm({
    name: '',
    email: '',
    role: 'Dentist',
});

const handleAddStaff = () => {
    addForm.post(route('staff.store'), {
        onSuccess: () => {
            showingAddModal.value = false;
            addForm.reset();
            Swal.fire({
                icon: 'success',
                title: 'Staff Invited!',
                text: 'A permanent login invitation has been sent to their email.',
                timer: 3000,
                showConfirmButton: false
            });
        },
        onError: () => {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to create staff member. Please check the email uniqueness.',
            });
        }
    });
};

// Calendar State
const calendarOptions = computed(() => ({
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin, googleCalendarPlugin],
    initialView: 'timeGridWeek',
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay'
    },
    editable: true,
    selectable: true,
    selectMirror: true,
    dayMaxEvents: true,
    weekends: true,
    height: 'auto',
    googleCalendarApiKey: props.api_key,
    events: {
        googleCalendarId: 'primary'
    },
    eventClick: (info) => console.log('Event clicked:', info.event),
    select: (selectInfo) => console.log('Selection:', selectInfo)
}));


// View Modal State
const showingStaffModal = ref(false);
const selectedStaff = ref(null);

const selectStaffForManage = (member) => {
    selectedStaff.value = member;
    showingStaffModal.value = true;
};

// Edit Modal State
const showingEditModal = ref(false);
const editForm = useForm({
    id: null,
    name: '',
    email: '',
    role: '',
    profile_picture_url: '',
});

const editStaff = (member) => {
    editForm.id = member.id;
    editForm.name = member.name;
    editForm.email = member.email;
    editForm.role = member.roles?.[0]?.name || 'Staff';
    editForm.profile_picture_url = member.profile_picture_url;
    showingEditModal.value = true;
    showingStaffModal.value = false;
};

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
    // Note: for multiple selection, we keep whatever is currently in the form or allow clean slate
};

const selectAllStaff = () => {
    if (selectedStaffIds.value.length === props.staff.length) {
        selectedStaffIds.value = [];
    } else {
        selectedStaffIds.value = props.staff.map(s => s.id);
    }
};

const savePermissionsTab = () => {
    permissionForm.staff_ids = selectedStaffIds.value;
    permissionForm.post(route('staff.bulk-update-permissions'), {
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: 'Permissions Updated',
                text: `Permissions updated for ${selectedStaffIds.value.length} staff members.`,
                timer: 2000,
                showConfirmButton: false
            });
        },
        preserveScroll: true
    });
};

// Group permissions for cleaner UI
const permissionGroups = computed(() => {
    const groups = {};
    const featureMap = {
        'appointments': 'Appointments',
        'patients': 'Patients Management',
        'treatments': 'Treatment Records',
        'billing': 'Billing & POS',
        'services': 'Service & Pricing',
        'staff': 'Staff Management',
        'reports': 'Reports & Analytics',
        'settings': 'Clinic Settings',
        'subscription': 'Subscription',
        'clinic': 'Clinic Profile',
        'booking': 'Online Booking'
    };

    props.allPermissions.forEach(p => {
        let feature = 'General Access';
        
        // Find matching feature from map
        for (const [key, label] of Object.entries(featureMap)) {
            if (p.name.includes(key)) {
                feature = label;
                break;
            }
        }

        if (!groups[feature]) groups[feature] = [];
        
        // Make display name prettier
        const displayName = p.name
            .replace(new RegExp(Object.keys(featureMap).join('|'), 'gi'), '') // Remove feature name from display
            .trim()
            .split(' ')
            .map(word => word.charAt(0).toUpperCase() + word.slice(1))
            .join(' ') || 'Access'; // Fallback if name becomes empty
        
        groups[feature].push({ ...p, displayName });
    });
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

const updateStaff = () => {
    editForm.put(route('staff.update', editForm.id), {
        onSuccess: () => {
            showingEditModal.value = false;
            Swal.fire({
                icon: 'success',
                title: 'Updated!',
                text: 'Staff member updated successfully.',
                timer: 2000,
                showConfirmButton: false
            });
        },
        onError: () => {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Something went wrong. Please check the form.',
            });
        },
        preserveScroll: true
    });
};

const confirmDelete = (member) => {
    const id = member.id;
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this! This will permanently delete the staff member from the database.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#2563eb',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, remove them!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('staff.destroy', id), {
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Removed!',
                        text: 'Staff member has been permanently removed.',
                        timer: 2000,
                        showConfirmButton: false
                    });
                },
                onError: () => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to remove staff member.'
                    });
                }
            });
        }
    });
};

// No more local can helper, using global from app.js
</script>

<style scoped>
.calendar-container {
    background: white;
}

:deep(.fc-header-toolbar) {
    margin-bottom: 2rem !important;
}

:deep(.fc-toolbar-title) {
    font-size: 1.25rem !important;
    font-weight: 900 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.1em !important;
    color: #111827 !important;
}

:deep(.fc-button-primary) {
    background-color: #f9fafb !important;
    border: 1px solid #f3f4f6 !important;
    color: #4b5563 !important;
    font-size: 10px !important;
    font-weight: 900 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.1em !important;
    padding: 10px 20px !important;
    border-radius: 12px !important;
    transition: all 0.3s !important;
}

:deep(.fc-button-primary:hover) {
    background-color: #111827 !important;
    color: white !important;
    border-color: #111827 !important;
}

:deep(.fc-button-active) {
    background-color: #111827 !important;
    color: white !important;
    border-color: #111827 !important;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important;
}

:deep(.fc-daygrid-event) {
    border-radius: 8px !important;
    padding: 4px 8px !important;
    font-size: 10px !important;
    font-weight: 700 !important;
    text-transform: uppercase !important;
}

:deep(.fc-col-header-cell) {
    padding: 15px 0 !important;
    background-color: #f9fafb !important;
    border-color: #f3f4f6 !important;
}

:deep(.fc-col-header-cell-cushion) {
    font-size: 10px !important;
    font-weight: 900 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.1em !important;
    color: #9ca3af !important;
}
</style>

