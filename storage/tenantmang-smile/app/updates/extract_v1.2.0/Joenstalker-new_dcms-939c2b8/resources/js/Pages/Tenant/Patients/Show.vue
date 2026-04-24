<template>
    <div class="p-6 max-w-7xl mx-auto space-y-6">
        <div class="flex justify-between items-center bg-white p-6 rounded-lg shadow">
            <div class="flex items-center gap-6">
                <div class="h-24 w-24 rounded-3xl overflow-hidden border-4 border-white shadow-xl flex-shrink-0 bg-blue-50">
                    <img :src="patient.photo_url" class="h-full w-full object-cover">
                </div>
                <div>
                    <h2 class="text-3xl font-black text-gray-900 uppercase tracking-tight">{{ patient.first_name }} {{ patient.last_name }}</h2>
                    <p class="text-blue-500 font-bold text-xs uppercase tracking-widest mt-1">Patient ID: #{{ patient.id }}</p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <Link :href="`/patients/${patient.id}/edit`" class="bg-yellow-600 text-white px-4 py-2 rounded-md hover:bg-yellow-700 transition text-sm font-medium">
                    Edit Profile
                </Link>
                <Link :href="`/patients/${patient.id}/delete`" class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 transition text-sm font-medium">
                    Delete Patient
                </Link>
                <Link href="/patients" class="text-blue-600 hover:underline text-sm ml-4">← Back</Link>
            </div>
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
                        <dt class="text-gray-500 font-medium">Last Visit</dt>
                        <dd class="text-gray-900">{{ patient.last_visit_time ? new Date(patient.last_visit_time).toLocaleString() : 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 font-medium">Balance</dt>
                        <dd class="text-lg font-bold text-red-600">₱{{ patient.balance || '0.00' }}</dd>
                    </div>
                    <div class="mt-4">
                        <dt class="text-gray-500 font-medium">Medical History</dt>
                        <dd class="text-gray-900 mt-1 bg-blue-50 p-2 rounded text-xs leading-relaxed border border-blue-100">
                            {{ patient.medical_history || 'None recorded.' }}
                        </dd>
                    </div>
                    <div class="mt-4">
                        <dt class="text-gray-500 font-medium">Operation History</dt>
                        <dd class="text-gray-900 mt-1 bg-gray-50 p-2 rounded text-xs leading-relaxed border border-gray-200">
                            {{ patient.operation_history || 'No past operations recorded.' }}
                        </dd>
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
