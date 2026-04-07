<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Swal from 'sweetalert2';
import axios from 'axios';

const props = defineProps({
    tickets: Object,
    currentStatus: String,
    currentCategory: String,
    stats: Object,
});

const selectedTicket = ref(null);
const showDetail = ref(false);
const isSendingReply = ref(false);
const replyContent = ref('');
const replyAttachments = ref([]);

const statusColors = {
    open: 'badge-info',
    in_progress: 'badge-warning',
    pending: 'badge-ghost',
    resolved: 'badge-success',
    closed: 'badge-neutral',
};

const categoryIcons = {
    billing: '💳',
    technical: '🐛',
    feature_request: '✨',
    account: '👤',
    other: '📌',
};

const priorityColors = {
    low: 'text-success',
    medium: 'text-info',
    high: 'text-warning',
    urgent: 'text-error',
};

const setFilter = (key, value) => {
    const params = {};
    if (key === 'status') params.status = value;
    if (key === 'category') params.category = value;
    router.get(route('admin.support.index'), params, {
        preserveState: true,
        preserveScroll: true,
    });
};

const viewTicket = async (ticket) => {
    try {
        const { data } = await axios.get(route('admin.support.show', ticket.id));
        if (data.success) {
            selectedTicket.value = data.ticket;
            showDetail.value = true;
        }
    } catch (e) {
        console.error('Failed to load ticket:', e);
    }
};

const sendReply = () => {
    if (!replyContent.value.trim() || isSendingReply.value) return;
    isSendingReply.value = true;

    const formData = new FormData();
    formData.append('content', replyContent.value.trim());
    replyAttachments.value.forEach(f => formData.append('attachments[]', f));

    axios.post(route('admin.support.reply', selectedTicket.value.id), formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
    }).then(({ data }) => {
        replyContent.value = '';
        replyAttachments.value = [];
        viewTicket(selectedTicket.value); // Refresh
        router.reload({ only: ['tickets', 'stats'] });
    }).catch(e => {
        console.error('Failed to send reply:', e);
    }).finally(() => {
        isSendingReply.value = false;
    });
};

const updateStatus = (status) => {
    router.put(route('admin.support.updateStatus', selectedTicket.value.id), { status }, {
        preserveScroll: true,
        onSuccess: () => {
            if (selectedTicket.value) selectedTicket.value.status = status;
            router.reload({ only: ['tickets', 'stats'] });
        }
    });
};

const deleteTicket = () => {
    Swal.fire({
        title: 'Delete this ticket?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Delete',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('admin.support.destroy', selectedTicket.value.id), {
                preserveScroll: true,
                onSuccess: () => closeDetail(),
            });
        }
    });
};

const closeDetail = () => {
    showDetail.value = false;
    selectedTicket.value = null;
    replyContent.value = '';
    replyAttachments.value = [];
};

const handleFileSelect = (e) => {
    replyAttachments.value.push(...Array.from(e.target.files));
    e.target.value = '';
};

const removeAttachment = (i) => replyAttachments.value.splice(i, 1);

const formatTime = (d) => {
    if (!d) return '';
    return new Date(d).toLocaleString();
};

const isImage = (type) => type?.startsWith('image/');
</script>

<template>
    <AdminLayout>
        <template #header>
            <h1 class="text-xl font-bold text-base-content leading-tight line-clamp-1">Support & Tickets</h1>
        </template>

        <div class="max-w-7xl mx-auto space-y-6">
            <!-- Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-base-100 rounded-2xl p-5 border border-base-300 shadow-sm">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-base-content/40">Total</p>
                    <p class="text-2xl font-black text-base-content mt-1">{{ stats.total }}</p>
                </div>
                <div class="bg-base-100 rounded-2xl p-5 border border-base-300 shadow-sm">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-info">Open</p>
                    <p class="text-2xl font-black text-info mt-1">{{ stats.open }}</p>
                </div>
                <div class="bg-base-100 rounded-2xl p-5 border border-base-300 shadow-sm">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-warning">In Progress</p>
                    <p class="text-2xl font-black text-warning mt-1">{{ stats.in_progress }}</p>
                </div>
                <div class="bg-base-100 rounded-2xl p-5 border border-base-300 shadow-sm">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-success">Resolved</p>
                    <p class="text-2xl font-black text-success mt-1">{{ stats.resolved }}</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-2">
                <button v-for="s in ['all','open','in_progress','pending','resolved','closed']" :key="s"
                    @click="setFilter('status', s)"
                    :class="currentStatus === s ? 'btn-primary text-white' : 'btn-ghost'"
                    class="btn btn-sm rounded-xl font-bold text-xs uppercase tracking-wider"
                >
                    {{ s.replace('_', ' ') }}
                </button>
            </div>

            <!-- Tickets Table -->
            <div class="bg-base-100 rounded-2xl border border-base-300 overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="table table-sm">
                        <thead>
                            <tr class="text-[10px] uppercase tracking-widest text-base-content/40">
                                <th>ID</th>
                                <th>Tenant</th>
                                <th>Subject</th>
                                <th>Category</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Last Update</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="ticket in tickets.data" :key="ticket.id" class="hover">
                                <td class="font-mono text-xs text-base-content/50">#{{ ticket.id }}</td>
                                <td class="text-xs font-semibold">{{ ticket.tenant?.name || `Tenant ${ticket.tenant_id}` }}</td>
                                <td>
                                    <p class="text-sm font-bold truncate max-w-[200px]">{{ ticket.subject }}</p>
                                    <p class="text-xs text-base-content/40 truncate max-w-[200px]">{{ ticket.latest_message?.content || ticket.description }}</p>
                                </td>
                                <td>
                                    <span class="text-xs">{{ categoryIcons[ticket.category] }} {{ ticket.category }}</span>
                                </td>
                                <td>
                                    <span class="text-xs font-bold uppercase" :class="priorityColors[ticket.priority]">{{ ticket.priority }}</span>
                                </td>
                                <td>
                                    <span :class="statusColors[ticket.status]" class="badge badge-sm text-[10px] font-bold uppercase">
                                        {{ ticket.status?.replace('_', ' ') }}
                                    </span>
                                </td>
                                <td class="text-xs text-base-content/40">{{ formatTime(ticket.updated_at) }}</td>
                                <td>
                                    <button @click="viewTicket(ticket)" class="btn btn-ghost btn-xs btn-circle">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="!tickets.data?.length">
                                <td colspan="8" class="text-center py-12 text-base-content/30 text-sm font-medium">
                                    No tickets found
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Ticket Detail Drawer -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition-all duration-300"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-all duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showDetail" class="fixed inset-0 bg-black/50 z-[200]" @click.self="closeDetail">
                    <div class="absolute right-0 top-0 h-full w-full max-w-lg bg-base-100 shadow-2xl flex flex-col animate-slide-in-right">
                        <!-- Header -->
                        <div class="flex items-center justify-between px-6 py-4 border-b border-base-300 shrink-0">
                            <div>
                                <h2 class="text-lg font-black text-base-content">{{ selectedTicket?.subject }}</h2>
                                <div class="flex items-center gap-2 mt-1">
                                    <span :class="statusColors[selectedTicket?.status]" class="badge badge-sm text-[10px] font-bold uppercase">
                                        {{ selectedTicket?.status?.replace('_', ' ') }}
                                    </span>
                                    <span class="text-[10px] text-base-content/40 font-bold uppercase">{{ selectedTicket?.category }}</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <!-- Status Actions -->
                                <div class="dropdown dropdown-end">
                                    <button tabindex="0" class="btn btn-ghost btn-xs">Status ▼</button>
                                    <ul tabindex="0" class="dropdown-content menu p-2 shadow-lg bg-base-100 rounded-xl w-48 z-[300] border border-base-300">
                                        <li v-for="s in ['open','in_progress','pending','resolved','closed']" :key="s">
                                            <a @click="updateStatus(s)" class="text-xs font-bold uppercase">{{ s.replace('_', ' ') }}</a>
                                        </li>
                                    </ul>
                                </div>
                                <button @click="deleteTicket" class="btn btn-ghost btn-xs text-error">🗑</button>
                                <button @click="closeDetail" class="btn btn-ghost btn-xs btn-circle">✕</button>
                            </div>
                        </div>

                        <!-- Messages -->
                        <div class="flex-1 overflow-y-auto p-4 space-y-3">
                            <div 
                                v-for="msg in selectedTicket?.messages" 
                                :key="msg.id"
                                :class="msg.sender_type === 'admin' ? 'items-end' : 'items-start'"
                                class="flex flex-col"
                            >
                                <div class="flex items-end gap-2" :class="msg.sender_type === 'admin' ? 'flex-row-reverse' : ''">
                                    <div 
                                        class="h-8 w-8 rounded-full flex items-center justify-center text-xs font-black shrink-0"
                                        :class="msg.sender_type === 'admin' ? 'bg-primary text-primary-content' : 'bg-base-300 text-base-content/50'"
                                    >
                                        {{ msg.sender_type === 'admin' ? '🛡️' : (msg.sender?.name?.charAt(0) || 'T') }}
                                    </div>
                                    <div 
                                        class="max-w-[320px] px-4 py-2.5 text-sm rounded-2xl"
                                        :class="msg.sender_type === 'admin' 
                                            ? 'bg-primary text-primary-content rounded-br-md' 
                                            : 'bg-base-200 text-base-content rounded-bl-md'"
                                    >
                                        <p class="text-xs font-bold mb-0.5 opacity-60">{{ msg.sender_type === 'admin' ? 'Admin' : (msg.sender?.name || 'Tenant') }}</p>
                                        <p class="whitespace-pre-wrap break-words">{{ msg.content }}</p>

                                        <div v-if="msg.attachments?.length" class="mt-2 space-y-1">
                                            <template v-for="att in msg.attachments" :key="att.id">
                                                <a v-if="isImage(att.file_type)" :href="`/storage/${att.file_path}`" target="_blank" class="block rounded-lg overflow-hidden border border-white/10">
                                                    <img :src="`/storage/${att.file_path}`" :alt="att.file_name" class="w-full max-h-40 object-cover" />
                                                </a>
                                                <a v-else :href="`/storage/${att.file_path}`" target="_blank" class="flex items-center gap-1 text-[11px] opacity-70 hover:opacity-100">
                                                    📎 {{ att.file_name }}
                                                </a>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                                <span class="text-[9px] mt-1 px-10 text-base-content/25">{{ formatTime(msg.created_at) }}</span>
                            </div>
                        </div>

                        <!-- Reply Input -->
                        <div class="border-t border-base-300 p-4 shrink-0 space-y-2">
                            <div v-if="replyAttachments.length > 0" class="flex flex-wrap gap-1.5">
                                <div v-for="(file, i) in replyAttachments" :key="i" class="badge badge-sm gap-1 bg-base-200 border-base-300">
                                    <span class="truncate max-w-[100px] text-[10px]">{{ file.name }}</span>
                                    <button @click="removeAttachment(i)" class="text-error">✕</button>
                                </div>
                            </div>
                            <div class="flex items-end gap-2">
                                <button @click="$refs.adminFileInput.click()" class="btn btn-sm btn-ghost btn-circle shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 text-base-content/40">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13" />
                                    </svg>
                                </button>
                                <input ref="adminFileInput" type="file" multiple accept="image/*,.pdf,.doc,.docx,.zip" @change="handleFileSelect" class="hidden" />
                                <textarea
                                    v-model="replyContent"
                                    @keydown.enter.exact.prevent="sendReply"
                                    placeholder="Type your reply..."
                                    rows="2"
                                    class="textarea textarea-sm textarea-bordered flex-1 rounded-xl text-sm resize-none"
                                ></textarea>
                                <button
                                    @click="sendReply"
                                    :disabled="!replyContent.trim() || isSendingReply"
                                    class="btn btn-sm btn-primary rounded-xl shrink-0"
                                >
                                    <span v-if="isSendingReply" class="loading loading-spinner loading-xs"></span>
                                    <span v-else>Send</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AdminLayout>
</template>

<style scoped>
@keyframes slide-in-right {
    from { transform: translateX(100%); }
    to { transform: translateX(0); }
}
.animate-slide-in-right {
    animation: slide-in-right 0.3s ease-out;
}
</style>
