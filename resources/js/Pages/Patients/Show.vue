<template>
    <div class="p-6 max-w-7xl mx-auto space-y-6">
        <div class="flex justify-between items-center bg-white p-6 rounded-lg shadow">
            <div>
                <h2 class="text-3xl font-bold text-gray-800">{{ patient.first_name }} {{ patient.last_name }}</h2>
                <p class="text-gray-500 mt-1">Patient ID: #{{ patient.id }}</p>
            </div>
            <Link href="/patients" class="text-blue-600 hover:underline">← Back to Directory</Link>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Profile Info -->
            <div class="bg-white p-6 rounded-lg shadow md:col-span-1 border-t-4 border-blue-500">
                <h3 class="text-lg font-semibold border-b pb-2 mb-4">Patient Details</h3>
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-gray-500 font-medium">Phone</dt>
                        <dd class="text-gray-900">{{ patient.phone || 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 font-medium">Email</dt>
                        <dd class="text-gray-900">{{ patient.email || 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 font-medium">Date of Birth</dt>
                        <dd class="text-gray-900">{{ patient.date_of_birth || 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 font-medium">Gender</dt>
                        <dd class="text-gray-900">{{ patient.gender || 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 font-medium">Address</dt>
                        <dd class="text-gray-900">{{ patient.address || 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 font-medium">Medical History</dt>
                        <dd class="text-gray-900 mt-1 bg-gray-50 p-2 rounded text-xs">{{ patient.medical_history || 'None recorded.' }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Main Content Area -->
            <div class="md:col-span-2 space-y-6">
                <!-- Treatment History -->
                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="flex justify-between items-center border-b pb-2 mb-4">
                        <h3 class="text-lg font-semibold">Treatment History</h3>
                        <Link href="/treatments/create" class="text-sm bg-blue-50 text-blue-600 px-3 py-1 rounded hover:bg-blue-100">Add Record</Link>
                    </div>
                    
                    <div v-if="patient.treatments.length === 0" class="text-gray-500 text-sm text-center py-4">
                        No treatments recorded yet.
                    </div>
                    
                    <div class="space-y-4">
                        <div v-for="treatment in patient.treatments" :key="treatment.id" class="p-4 bg-gray-50 rounded border">
                            <div class="flex justify-between font-medium">
                                <span>{{ treatment.diagnosis }}</span>
                                <span class="text-gray-500 text-sm">{{ new Date(treatment.created_at).toLocaleDateString() }}</span>
                            </div>
                            <p class="text-sm text-gray-700 mt-2">{{ treatment.procedure }}</p>
                        </div>
                    </div>
                </div>

                <!-- Recent Appointments -->
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-lg font-semibold border-b pb-2 mb-4">Appointments</h3>
                    <ul class="divide-y text-sm">
                        <li v-for="apt in patient.appointments" :key="apt.id" class="py-3 flex justify-between items-center">
                            <div>
                                <div class="font-medium text-gray-800">{{ new Date(apt.appointment_date).toLocaleString() }}</div>
                                <div class="text-gray-500">{{ apt.service || 'General' }}</div>
                            </div>
                            <span :class="{
                                'px-2 py-1 rounded text-xs font-medium': true,
                                'bg-yellow-100 text-yellow-800': apt.status === 'scheduled',
                                'bg-green-100 text-green-800': apt.status === 'completed',
                                'bg-red-100 text-red-800': apt.status === 'cancelled',
                                'bg-blue-100 text-blue-800': apt.status === 'walk-in',
                            }">{{ apt.status.toUpperCase() }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    patient: Object
});
</script>
