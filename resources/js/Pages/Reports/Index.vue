<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    stats: Object,
    recent_appointments: Array
});
</script>

<template>
    <Head title="Reports & Analytics" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Clinic Insights & Reports
            </h2>
        </template>

        <div class="p-6 bg-white rounded-lg shadow-md mt-6">
            <h3 class="text-lg font-medium text-gray-800 mb-6 border-b pb-2">Clinic Reports & Monitoring</h3>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-blue-50 p-6 rounded-lg shadow-sm border border-blue-100 flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-500">Today's Appointments</div>
                        <div class="text-2xl font-bold text-gray-900">{{ stats.daily_appointments }}</div>
                    </div>
                </div>

                <div class="bg-green-50 p-6 rounded-lg shadow-sm border border-green-100 flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-500">Monthly Revenue</div>
                        <div class="text-2xl font-bold text-gray-900">${{ Number(stats.monthly_revenue).toLocaleString(undefined, {minimumFractionDigits: 2}) }}</div>
                    </div>
                </div>

                <div class="bg-purple-50 p-6 rounded-lg shadow-sm border border-purple-100 flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600 mr-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-500">Total Registered Patients</div>
                        <div class="text-2xl font-bold text-gray-900">{{ stats.total_patients }}</div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="mt-8 bg-white border rounded-lg shadow-sm overflow-hidden">
                <h3 class="px-6 py-4 border-b font-medium text-gray-800 bg-gray-50">Recent Appointments</h3>
                <ul class="divide-y text-sm">
                    <li v-for="apt in recent_appointments" :key="apt.id" class="px-6 py-4 flex justify-between items-center hover:bg-gray-50">
                        <div>
                            <div class="font-medium text-gray-900">{{ new Date(apt.appointment_date).toLocaleString() }}</div>
                            <div class="text-gray-500" v-if="apt.patient">{{ apt.patient.first_name }} {{ apt.patient.last_name }} - {{ apt.service || 'General' }}</div>
                            <div class="text-gray-500" v-else>{{ apt.guest_first_name }} {{ apt.guest_last_name }} (Guest) - {{ apt.service || 'General' }}</div>
                        </div>
                        <span :class="{
                            'px-2 py-1 rounded-full text-xs font-medium': true,
                            'bg-yellow-100 text-yellow-800': apt.status === 'scheduled',
                            'bg-green-100 text-green-800': apt.status === 'completed',
                            'bg-red-100 text-red-800': apt.status === 'cancelled',
                            'bg-blue-100 text-blue-800': apt.status === 'walk-in',
                        }">{{ apt.status.toUpperCase() }}</span>
                    </li>
                    <li v-if="recent_appointments.length === 0" class="px-6 py-4 text-center text-gray-500">No recent appointments.</li>
                </ul>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
