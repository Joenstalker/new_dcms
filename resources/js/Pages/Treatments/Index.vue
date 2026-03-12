<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    treatments: Array
});
</script>

<template>
    <Head title="Service & Pricing" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Service & Pricing Management
            </h2>
        </template>

        <div class="p-6 bg-white rounded-lg shadow-md mt-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-medium text-gray-800">Treatment Records</h3>
                <button class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition">
                    Record Treatment
                </button>
            </div>

            <div v-if="$page.props.flash && $page.props.flash.success" class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                {{ $page.props.flash.success }}
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Diagnosis</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dentist</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cost</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="treatment in treatments" :key="treatment.id">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ new Date(treatment.created_at).toLocaleDateString() }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <Link v-if="treatment.patient" :href="`/patients/${treatment.patient_id}`" class="text-sm font-medium text-blue-600 hover:text-blue-900">
                                    {{ treatment.patient.first_name }} {{ treatment.patient.last_name }}
                                </Link>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <div class="font-medium">{{ treatment.diagnosis }}</div>
                                <div class="text-gray-500 text-xs">{{ treatment.procedure }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ treatment.dentist ? treatment.dentist.name : 'Unknown' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                ${{ Number(treatment.cost).toFixed(2) }}
                            </td>
                        </tr>
                        <tr v-if="treatments.length === 0">
                            <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">No treatments recorded.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
