<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    patient: Object
});

const confirmDelete = () => {
    router.delete(`/patients/${props.patient.id}`);
};
</script>

<template>
    <Head title="Delete Patient" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Confirm Deletion
            </h2>
        </template>

        <div class="max-w-2xl mx-auto p-8 bg-white rounded-lg shadow-md mt-6 border-t-4 border-red-500">
            <div class="text-center">
                <div class="w-16 h-16 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-8 h-8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                </div>
                
                <h3 class="text-2xl font-bold text-gray-900 mb-2">Are you absolutely sure?</h3>
                <p class="text-gray-600 mb-6 font-medium">
                    You are about to permanently delete the patient record for 
                    <span class="text-gray-900 font-bold">{{ patient.first_name }} {{ patient.last_name }}</span>.
                    This action cannot be undone and will remove all associated history.
                </p>

                <div class="flex flex-col sm:flex-row justify-center gap-4">
                    <Link 
                        :href="`/patients/${patient.id}`" 
                        class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 font-medium hover:bg-gray-50 flex-1 sm:flex-none"
                    >
                        No, Keep Record
                    </Link>
                    <button 
                        @click="confirmDelete" 
                        class="px-6 py-2 bg-red-600 text-white rounded-md font-medium hover:bg-red-700 flex-1 sm:flex-none shadow-sm shadow-red-200"
                    >
                        Yes, Delete Permanently
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
