<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    stats: Object,
    concerns: Array,
});

const currentTab = ref('overview');

const form = useForm({
    status: '',
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const roles = computed(() => user.value?.roles || []);

const isOwner = computed(() => roles.value.includes('Owner'));
const isDentist = computed(() => roles.value.includes('Dentist'));
const isAssistant = computed(() => roles.value.includes('Assistant'));

const portalName = computed(() => {
    if (isOwner.value) return 'Clinic Owner Portal';
    if (isDentist.value) return 'Dentist Portal';
    if (isAssistant.value) return 'Assistant Portal';
    return 'Staff Portal';
});

const updateStatus = (concern, newStatus) => {
    form.status = newStatus;
    form.patch(route('tenant.concern.update', concern.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    {{ portalName }}
                </h2>
                <!-- Tabs -->
                <div class="flex bg-gray-100 p-1 rounded-xl">
                    <button @click="currentTab = 'overview'" 
                        :class="['px-4 py-2 text-sm font-bold rounded-lg transition-all', currentTab === 'overview' ? 'bg-white shadow text-blue-600' : 'text-gray-500 hover:text-gray-700']">
                        Overview
                    </button>
                    <button @click="currentTab = 'concerns'" 
                        :class="['px-4 py-2 text-sm font-bold rounded-lg transition-all flex items-center gap-2', currentTab === 'concerns' ? 'bg-white shadow text-blue-600' : 'text-gray-500 hover:text-gray-700']">
                        Concerns
                        <span v-if="concerns.some(c => c.status === 'pending')" class="w-2 h-2 bg-red-500 rounded-full animate-pulse"></span>
                    </button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                
                <!-- Overview Content -->
                <div v-if="currentTab === 'overview'" class="space-y-8 animate-in fade-in duration-500">
                    <!-- Stat Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <!-- Daily Appointments (Visible to All) -->
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-3xl p-6 border-b-4 border-blue-500 transition-hover hover:-translate-y-1 duration-300">
                            <div class="text-xs font-bold text-blue-500 uppercase tracking-wide mb-1">
                                {{ isDentist ? 'My Appointments' : 'Daily Appointments' }}
                            </div>
                            <div class="text-3xl font-black text-gray-900">{{ stats.daily_appointments }}</div>
                        </div>

                        <!-- Monthly Revenue (Owner Only) -->
                        <div v-if="isOwner" class="bg-white overflow-hidden shadow-sm sm:rounded-3xl p-6 border-b-4 border-green-500 transition-hover hover:-translate-y-1 duration-300">
                            <div class="text-xs font-bold text-green-500 uppercase tracking-wide mb-1">Monthly Revenue</div>
                            <div class="text-3xl font-black text-gray-900">₱{{ stats.monthly_revenue }}</div>
                        </div>

                        <!-- Total Patients (Owner & Assistant) -->
                        <div v-if="isOwner || isAssistant" class="bg-white overflow-hidden shadow-sm sm:rounded-3xl p-6 border-b-4 border-purple-500 transition-hover hover:-translate-y-1 duration-300">
                            <div class="text-xs font-bold text-purple-500 uppercase tracking-wide mb-1">Total Patients</div>
                            <div class="text-3xl font-black text-gray-900">{{ stats.total_patients }}</div>
                        </div>

                        <!-- Pending Bookings (Owner & Assistant) -->
                        <div v-if="isOwner || isAssistant" class="bg-white overflow-hidden shadow-sm sm:rounded-3xl p-6 border-b-4 border-yellow-500 transition-hover hover:-translate-y-1 duration-300">
                            <div class="text-xs font-bold text-yellow-500 uppercase tracking-wide mb-1">Pending Bookings</div>
                            <div class="text-3xl font-black text-gray-900">{{ stats.pending_appointments }}</div>
                        </div>

                        <!-- Dentist Specific: Assigned Patients Placeholder -->
                        <div v-if="isDentist" class="bg-white overflow-hidden shadow-sm sm:rounded-3xl p-6 border-b-4 border-orange-500 transition-hover hover:-translate-y-1 duration-300">
                            <div class="text-xs font-bold text-orange-500 uppercase tracking-wide mb-1">Assigned Patients</div>
                            <div class="text-3xl font-black text-gray-900 px-1">--</div>
                        </div>

                        <!-- Storage Usage (Owner only) -->
                        <div v-if="isOwner" class="bg-white overflow-hidden shadow-sm sm:rounded-3xl p-6 border-b-4 border-indigo-500 transition-hover hover:-translate-y-1 duration-300">
                            <div class="flex justify-between items-center mb-1">
                                <div class="text-xs font-bold text-indigo-500 uppercase tracking-wide">Storage Used</div>
                                <div class="text-[10px] font-bold text-gray-400 italic">
                                    {{ (stats.storage_used_bytes / stats.max_storage_bytes * 100).toFixed(1) }}%
                                </div>
                            </div>
                            <div class="text-2xl font-black text-gray-900 leading-none mb-2">
                                {{ stats.storage_used_bytes >= 1048576 ? (stats.storage_used_bytes / 1048576).toFixed(1) + ' MB' : (stats.storage_used_bytes / 1024).toFixed(0) + ' KB' }}
                            </div>
                            <div class="w-full bg-gray-100 h-1.5 rounded-full overflow-hidden shadow-inner">
                                <div 
                                    class="h-full bg-indigo-500 rounded-full transition-all duration-1000" 
                                    :style="{ width: Math.min((stats.storage_used_bytes / stats.max_storage_bytes * 100), 100) + '%' }"
                                ></div>
                            </div>
                            <div class="text-[10px] text-gray-400 mt-2 flex justify-between items-center">
                                <span>Plan: <span class="font-bold">{{ (stats.max_storage_bytes / 1048576).toFixed(0) }}MB</span></span>
                                <span v-if="stats.storage_used_bytes > stats.max_storage_bytes" class="text-red-500 font-bold animate-pulse">Limit Exceeded</span>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-hidden bg-white shadow-sm sm:rounded-3xl p-8 border border-gray-100 relative group">
                        <div class="relative z-10">
                            <h3 class="text-2xl font-black text-gray-900 mb-2">
                                Welcome Back, {{ user.name.split(' ')[0] }}! 👋
                            </h3>
                            <p class="text-gray-500 leading-relaxed max-w-2xl">
                                <span v-if="isOwner">You are currently in the <strong>Admin Control Center</strong>. Manage your clinic operations and staff efficiently.</span>
                                <span v-else-if="isDentist">You are in the <strong>Dentist Portal</strong>. View your assigned treatments and patient records.</span>
                                <span v-else-if="isAssistant">You are in the <strong>Assistant Portal</strong>. Manage appointments and patient inquiries.</span>
                                <br v-if="stats.daily_appointments > 0"/>
                                <span v-if="stats.daily_appointments > 0" class="mt-2 block">
                                    You have <span class="text-blue-600 font-bold underline">{{ stats.daily_appointments }} appointments</span> scheduled for today.
                                </span>
                            </p>
                        </div>
                        <div class="absolute top-0 right-0 p-8 opacity-5 group-hover:scale-110 group-hover:rotate-12 transition-transform duration-700">
                            <span class="text-9xl">🦷</span>
                        </div>
                    </div>
                </div>

                <!-- Concerns Content -->
                <div v-else-if="currentTab === 'concerns'" class="animate-in slide-in-from-bottom duration-500 space-y-6">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-3xl p-8 border border-gray-100">
                        <div class="flex justify-between items-center mb-8">
                            <div>
                                <h3 class="text-2xl font-black text-gray-900">Patient Concerns</h3>
                                <p class="text-gray-500">Messages sent via the clinic landing page.</p>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="text-xs font-bold text-gray-400 uppercase tracking-widest border-b border-gray-50">
                                        <th class="pb-4 px-4 text-center">Date</th>
                                        <th class="pb-4 px-4">Patient</th>
                                        <th class="pb-4 px-4">Subject</th>
                                        <th class="pb-4 px-4">Status</th>
                                        <th class="pb-4 px-4 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50 text-sm">
                                    <tr v-for="concern in concerns" :key="concern.id" class="group hover:bg-gray-50 transition-colors">
                                        <td class="py-6 px-4 text-center text-gray-400">
                                            {{ new Date(concern.created_at).toLocaleDateString() }}
                                        </td>
                                        <td class="py-6 px-4">
                                            <div class="font-bold text-gray-900">{{ concern.name }}</div>
                                            <div class="text-xs text-gray-500">{{ concern.email }}</div>
                                        </td>
                                        <td class="py-6 px-4">
                                            <div class="font-medium text-gray-700">{{ concern.subject || 'No Subject' }}</div>
                                            <div class="text-xs text-gray-400 truncate max-w-xs">{{ concern.message }}</div>
                                        </td>
                                        <td class="py-6 px-4">
                                            <span :class="['px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest', 
                                                concern.status === 'pending' ? 'bg-yellow-100 text-yellow-700' : 
                                                concern.status === 'in_progress' ? 'bg-blue-100 text-blue-700' :
                                                'bg-green-100 text-green-700']">
                                                {{ concern.status }}
                                            </span>
                                        </td>
                                        <td class="py-6 px-4 text-right">
                                            <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                                <button v-if="isOwner || isAssistant" @click="updateStatus(concern, 'resolved')" title="Mark as Resolved" 
                                                    class="p-2 bg-green-50 text-green-600 rounded-lg hover:bg-green-100 transition-colors">
                                                    ✅
                                                </button>
                                                <button v-if="isOwner || isAssistant" @click="updateStatus(concern, 'in_progress')" title="Mark as In Progress" 
                                                    class="p-2 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition-colors">
                                                    ⏳
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <!-- Empty State -->
                                    <tr v-if="concerns.length === 0">
                                        <td colspan="5" class="py-20 text-center text-gray-400 italic">
                                            No patient concerns recorded yet.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
