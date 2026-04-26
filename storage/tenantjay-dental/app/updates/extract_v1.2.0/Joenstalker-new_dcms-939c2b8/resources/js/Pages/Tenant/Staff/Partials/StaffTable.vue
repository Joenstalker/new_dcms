<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    staff: {
        type: Array,
        required: true
    }
});

const emit = defineEmits(['view', 'edit', 'delete']);

const handleView = (member) => {
    emit('view', member);
};

const handleEdit = (member) => {
    emit('edit', member);
};

const handleDelete = (id) => {
    emit('delete', id);
};

const can = (permission) => {
    // This would typically come from props or a helper
    return true;
};
</script>

<template>
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-100">
        <div class="overflow-x-auto">
            <table class="min-w-full md:min-w-[720px] divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="member in staff" :key="member.id" class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ member.name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ member.email }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <span class="px-2.5 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                {{ member.roles?.[0]?.name || 'Staff' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-3">
                            <button @click="handleView(member)" class="text-blue-600 hover:text-blue-900">View</button>
                            <button @click="handleEdit(member)" class="text-indigo-600 hover:text-indigo-900 font-semibold">Edit</button>
                            <button @click="handleDelete(member.id)" class="text-red-600 hover:text-red-900">Remove</button>
                        </td>
                    </tr>
                    <tr v-if="staff.length === 0">
                        <td colspan="4" class="px-6 py-10 text-center text-gray-500 italic bg-gray-50/50">
                            No staff members found. Add your first team member to get started!
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
