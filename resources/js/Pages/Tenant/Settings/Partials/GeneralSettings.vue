<script setup>
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    form: {
        type: Object,
        required: true
    }
});
</script>

<template>
    <!-- General Settings -->
    <div class="space-y-4">
        <h4 class="text-md font-medium text-gray-900 border-b pb-2">General Information</h4>
        
        <div>
            <label class="block text-sm font-medium text-gray-700">Clinic Name</label>
            <input type="text" v-model="form.clinic_name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Contact Email</label>
            <input type="email" v-model="form.email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Phone Number</label>
            <input type="text" v-model="form.phone" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Business Address</label>
            <textarea v-model="form.address" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
        </div>
    </div>
</template>
