<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    stats: Object
});
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Clinic Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Stat Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-blue-500">
                        <div class="text-xs font-bold text-blue-500 uppercase tracking-wide">Daily Appointments</div>
                        <div class="text-2xl font-bold text-gray-800 mt-1">{{ stats.daily_appointments }}</div>
                    </div>
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-green-500">
                        <div class="text-xs font-bold text-green-500 uppercase tracking-wide">Monthly Revenue</div>
                        <div class="text-2xl font-bold text-gray-800 mt-1">${{ stats.monthly_revenue }}</div>
                    </div>
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-purple-500">
                        <div class="text-xs font-bold text-purple-500 uppercase tracking-wide">Total Patients</div>
                        <div class="text-2xl font-bold text-gray-800 mt-1">{{ stats.total_patients }}</div>
                    </div>
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-yellow-500">
                        <div class="text-xs font-bold text-yellow-500 uppercase tracking-wide">Pending Bookings</div>
                        <div class="text-2xl font-bold text-gray-800 mt-1">{{ stats.pending_appointments }}</div>
                    </div>
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        Welcome back! Use the navigation menu above to manage your clinic operations.
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
