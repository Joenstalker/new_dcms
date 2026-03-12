<template>
    <Head title="Add Staff" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Add Staff Member
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 max-w-2xl">
                    <form @submit.prevent="submit">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Name</label>
                            <input type="text" v-model="form.name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                            <div v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</div>
                        </div>

                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700">Email</label>
                            <input type="email" v-model="form.email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                            <div v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</div>
                        </div>

                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700">Initial Password</label>
                            <input type="password" v-model="form.password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                            <div v-if="form.errors.password" class="text-red-500 text-xs mt-1">{{ form.errors.password }}</div>
                        </div>

                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700">Role</label>
                            <select v-model="form.role" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                                <option value="" disabled>Select a role</option>
                                <option v-for="role in roles" :key="role.id" :value="role.name">{{ role.name }}</option>
                            </select>
                            <div v-if="form.errors.role" class="text-red-500 text-xs mt-1">{{ form.errors.role }}</div>
                        </div>

                        <div class="mt-6 flex items-center justify-end">
                            <Link :href="route('staff.index')" class="text-sm text-gray-600 hover:text-gray-900 mr-4">Cancel</Link>
                            <button type="submit" :disabled="form.processing" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50">
                                Save Staff Member
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

const props = defineProps({
    roles: Array
});

const form = useForm({
    name: '',
    email: '',
    password: '',
    role: ''
});

const submit = () => {
    Swal.fire({
        title: 'Saving Staff Member...',
        didOpen: () => {
            Swal.showLoading();
        },
        allowOutsideClick: false,
        showConfirmButton: false,
    });

    form.post(route('staff.store'), {
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: 'Staff member added successfully.',
                timer: 2000,
                showConfirmButton: false,
            });
            form.reset();
        },
        onError: () => {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Something went wrong. Please check the form.',
            });
        }
    });
};
</script>
