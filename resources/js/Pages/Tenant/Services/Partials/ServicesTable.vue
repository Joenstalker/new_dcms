<script setup>
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    services: {
        type: Array,
        required: true
    },
    canManage: {
        type: Boolean,
        default: false
    },
    selectedIds: {
        type: Array,
        default: () => []
    },
    selectAll: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['edit', 'delete', 'toggle-selection', 'update:selectAll']);
const page = usePage();

const handleEdit = (service) => {
    emit('edit', service);
};

const handleDelete = (service) => {
    emit('delete', service);
};

const toggleSelection = (id) => {
    emit('toggle-selection', id);
};

const toggleSelectAll = () => {
    emit('update:selectAll', !props.selectAll);
};
</script>

<template>
    <div class="overflow-x-auto">
        <table class="table w-full">
            <thead>
                <tr class="bg-base-200/50">
                    <th class="px-6 py-4 text-center w-10">
                        <div
                            class="w-5 h-5 rounded border border-base-300 flex items-center justify-center cursor-pointer transition-all mx-auto"
                            :class="selectAll ? 'bg-primary border-primary' : 'bg-base-100'"
                            @click="toggleSelectAll"
                        >
                            <svg v-if="selectAll" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-white" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </th>
                    <th class="text-[10px] font-black uppercase tracking-widest text-base-content/40 px-6 py-4 text-center">ID</th>
                    <th class="text-[10px] font-black uppercase tracking-widest text-base-content/40 px-6 py-4">Service Name</th>
                    <th class="text-[10px] font-black uppercase tracking-widest text-base-content/40 px-6 py-4">Price</th>
                    <th class="text-[10px] font-black uppercase tracking-widest text-base-content/40 px-6 py-4 text-center">Created By</th>
                    <th class="text-[10px] font-black uppercase tracking-widest text-base-content/40 px-6 py-4 text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-base-200">
                <tr v-for="service in services" :key="service.id" class="hover:bg-base-200/30 transition-colors" :class="{ 'bg-primary/5': selectedIds?.includes(service.id) }">
                    <td class="px-6 py-4 text-center">
                        <div
                            class="w-5 h-5 rounded border border-base-300 flex items-center justify-center cursor-pointer transition-all mx-auto"
                            :class="selectedIds?.includes(service.id) ? 'bg-primary border-primary' : 'bg-base-100'"
                            @click="toggleSelection(service.id)"
                        >
                            <svg v-if="selectedIds?.includes(service.id)" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-white" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-center text-[10px] font-black text-base-content/30 tracking-widest">SRV-{{ service.id }}</td>
                    <td class="px-6 py-4">
                        <div class="flex flex-col">
                            <span class="text-sm font-black text-base-content">{{ service.name }}</span>
                            <span class="text-xs text-base-content/50 max-w-xs truncate">{{ service.description }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-sm font-black text-primary">₱{{ Number(service.price).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <div class="avatar placeholder">
                                <div class="bg-neutral text-neutral-content rounded-full w-6">
                                    <span class="text-[10px] font-black">{{ service.creator?.name.charAt(0) }}</span>
                                </div>
                            </div>
                            <span class="text-[10px] font-black text-base-content/60 uppercase tracking-wider">{{ service.creator?.name }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <button
                                @click="handleEdit(service)"
                                class="btn btn-sm btn-square btn-ghost hover:bg-info/10 hover:text-info transition-colors"
                                title="Edit"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
                <tr v-if="services.length === 0">
                    <td colspan="6" class="px-6 py-16 text-center">
                        <h3 class="text-sm font-black text-base-content/40 uppercase tracking-widest">No services found.</h3>
                        <p class="text-xs text-base-content/30 mt-1">Manage services and pricing offered by your clinic.</p>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
