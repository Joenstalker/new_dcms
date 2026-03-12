<template>
    <div class="p-6 bg-white rounded-lg shadow-md max-w-3xl mx-auto">
        <h2 class="text-2xl font-semibold text-gray-800 mb-6">Add New Patient</h2>

        <form @submit.prevent="submit" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">First Name</label>
                    <input type="text" v-model="form.first_name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <div v-if="form.errors.first_name" class="text-red-500 text-sm mt-1">{{ form.errors.first_name }}</div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Last Name</label>
                    <input type="text" v-model="form.last_name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <div v-if="form.errors.last_name" class="text-red-500 text-sm mt-1">{{ form.errors.last_name }}</div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Phone Number</label>
                    <input type="text" v-model="form.phone" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <div v-if="form.errors.phone" class="text-red-500 text-sm mt-1">{{ form.errors.phone }}</div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Email Address</label>
                    <input type="email" v-model="form.email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <div v-if="form.errors.email" class="text-red-500 text-sm mt-1">{{ form.errors.email }}</div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Date of Birth</label>
                    <input type="date" v-model="form.date_of_birth" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Gender</label>
                    <select v-model="form.gender" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Select</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Address</label>
                <textarea v-model="form.address" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Medical History / Notes</label>
                <textarea v-model="form.medical_history" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <Link href="/patients" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    Cancel
                </Link>
                <button type="submit" :disabled="form.processing" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50">
                    Save Patient
                </button>
            </div>
        </form>
    </div>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3';

const form = useForm({
    first_name: '',
    last_name: '',
    phone: '',
    email: '',
    date_of_birth: '',
    gender: '',
    address: '',
    medical_history: '',
});

const submit = () => {
    form.post('/patients');
};
</script>
