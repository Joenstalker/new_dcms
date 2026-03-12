<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({
    appointments: Array,
    patients: Array, // for new appointment modal
});

const updateStatus = (id, status) => {
    if(confirm(`Are you sure you want to mark this appointment as ${status}?`)) {
        router.put(`/appointments/${id}`, { status }, {
            preserveScroll: true
        });
    }
};
</script>

<template>
    <Head title="Appointments" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Appointments Management
            </h2>
        </template>

        <div class="p-6 bg-white rounded-lg shadow-md mt-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-medium text-gray-800">Appointment Overview</h3>
                <button class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition">
                    New Appointment
                </button>
            </div>

            <div v-if="$page.props.flash && $page.props.flash.success" class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                {{ $page.props.flash.success }}
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient / Guest</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="appointment in appointments" :key="appointment.id">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ new Date(appointment.appointment_date).toLocaleString() }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div v-if="appointment.patient" class="text-sm font-medium text-gray-900">
                                    <Link :href="`/patients/${appointment.patient_id}`" class="text-blue-600 hover:underline">
                                        {{ appointment.patient.first_name }} {{ appointment.patient.last_name }}
                                    </Link>
                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 border text-xs font-medium bg-gray-100 text-gray-800 rounded">Registered</span>
                                </div>
                                <div v-else class="text-sm font-medium text-gray-900">
                                    {{ appointment.guest_first_name }} {{ appointment.guest_last_name }}
                                    <div class="text-xs text-gray-500">{{ appointment.guest_phone }}</div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ appointment.service || 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="{
                                    'px-2 inline-flex text-xs leading-5 font-semibold rounded-full': true,
                                    'bg-yellow-100 text-yellow-800': appointment.status === 'scheduled',
                                    'bg-green-100 text-green-800': appointment.status === 'completed',
                                    'bg-red-100 text-red-800': appointment.status === 'cancelled',
                                    'bg-blue-100 text-blue-800': appointment.status === 'walk-in',
                                }">
                                    {{ appointment.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                <button @click="updateStatus(appointment.id, 'completed')" v-if="appointment.status === 'scheduled'" class="text-green-600 hover:text-green-900">Complete</button>
                                <button @click="updateStatus(appointment.id, 'cancelled')" v-if="appointment.status === 'scheduled'" class="text-red-600 hover:text-red-900">Cancel</button>
                            </td>
                        </tr>
                        <tr v-if="appointments.length === 0">
                            <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">No appointments scheduled.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
