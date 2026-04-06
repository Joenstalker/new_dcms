<script setup>
defineProps({
    services: {
        type: Array,
        required: true
    },
    isOwnerOrDentist: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['edit', 'delete', 'approve', 'reject']);

const getStatusBadge = (status) => {
    const classes = {
        approved: 'badge-success text-white',
        pending: 'badge-warning text-white',
        rejected: 'badge-error text-white'
    };
    return classes[status] || 'badge-ghost';
};

const handleEdit = (service) => {
    emit('edit', service);
};

const handleDelete = (id) => {
    emit('delete', id);
};

const handleApprove = (id) => {
    emit('approve', id);
};

const handleReject = (id) => {
    emit('reject', id);
};
</script>

<template>
    <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-gray-100">
        <div class="overflow-x-auto">
            <table class="table table-lg w-full">
                <thead class="bg-slate-50">
                    <tr class="text-slate-500">
                        <th>Service Name</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Created By</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="service in services" :key="service.id" class="hover:bg-slate-50/50 transition-colors">
                        <td>
                            <div class="flex flex-col">
                                <span class="font-bold text-slate-800">{{ service.name }}</span>
                                <span class="text-xs text-slate-500 max-w-xs truncate">{{ service.description }}</span>
                            </div>
                        </td>
                        <td>
                            <span class="font-mono font-semibold text-primary">₱{{ Number(service.price).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</span>
                        </td>
                        <td>
                            <div :class="['badge badge-md uppercase font-bold text-[10px] tracking-widest px-3 py-3 border-none', getStatusBadge(service.status)]">
                                {{ service.status }}
                            </div>
                        </td>
                        <td>
                            <div class="flex items-center gap-2">
                                <div class="avatar placeholder">
                                    <div class="bg-neutral text-neutral-content rounded-full w-8">
                                        <span class="text-xs">{{ service.creator?.name.charAt(0) }}</span>
                                    </div>
                                </div>
                                <span class="text-sm font-medium text-slate-600">{{ service.creator?.name }}</span>
                            </div>
                        </td>
                        <td class="text-right space-x-2">
                            <!-- Approval Actions -->
                            <template v-if="isOwnerOrDentist && service.status === 'pending'">
                                <button @click="handleApprove(service.id)" class="btn btn-sm btn-success text-white">Approve</button>
                                <button @click="handleReject(service.id)" class="btn btn-sm btn-outline btn-error">Reject</button>
                            </template>
                            
                            <!-- Regular Actions -->
                            <button v-if="can('edit services') || isOwnerOrDentist" @click="handleEdit(service)" class="btn btn-sm btn-ghost hover:bg-indigo-50 text-indigo-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                </svg>
                            </button>
                            <button v-if="can('delete services') || isOwnerOrDentist" @click="handleDelete(service.id)" class="btn btn-sm btn-ghost hover:bg-red-50 text-red-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </td>
                    </tr>
                    <tr v-if="services.length === 0">
                        <td colspan="5" class="py-20 text-center">
                            <div class="flex flex-col items-center gap-2 opacity-40">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                </svg>
                                <p class="text-xl font-bold">No services found in this category.</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
