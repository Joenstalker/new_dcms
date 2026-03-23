<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({
    patients: Array
});
</script>

<template>
    <Head title="Patients" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Patient Records
            </h2>
        </template>

        <div class="p-6 bg-white rounded-lg shadow-md mt-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-medium text-gray-800">All Patients</h3>
                <Link href="/patients/create" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition">
                    Add Patient
                </Link>
            </div>

            <div v-if="$page.props.flash && $page.props.flash.success" class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                {{ $page.props.flash.success }}
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="patient in patients" :key="patient.id">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="h-10 w-10 rounded-xl overflow-hidden bg-gray-100 border border-gray-200">
                                        <img :src="patient.photo_url" class="h-full w-full object-cover">
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900 border-b border-gray-100 mb-0.5">#{{ patient.id }}</div>
                                        <div class="text-sm font-black text-gray-900 uppercase tracking-tight">{{ patient.first_name }} {{ patient.last_name }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ patient.phone || 'N/A' }}</div>
                                <div class="text-sm text-gray-500">{{ patient.email || '' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-3">
                                <Link :href="`/patients/${patient.id}`" class="text-blue-600 hover:text-blue-900">View</Link>
                                <Link :href="`/patients/${patient.id}/edit`" class="text-yellow-600 hover:text-yellow-900">Edit</Link>
                                <Link :href="`/patients/${patient.id}/delete`" class="text-red-600 hover:text-red-900">Delete</Link>
                            </td>
                        </tr>
                        <tr v-if="patients.length === 0">
                            <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">No patients found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
