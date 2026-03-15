<script setup>
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Swal from 'sweetalert2';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    messages: Object,
    unreadCount: Number,
    currentFilter: String,
});

const page = usePage();

// View message detail
const selectedMessage = ref(null);
const showDetail = ref(false);

const filters = [
    { label: 'All', value: 'all' },
    { label: 'Unread', value: 'unread' },
    { label: 'Read', value: 'read' },
    { label: 'Replied', value: 'replied' },
    { label: 'Archived', value: 'archived' },
];

const setFilter = (filter) => {
    router.get(route('admin.support.index'), { filter }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const viewMessage = async (msg) => {
    try {
        const response = await fetch(`/admin/support/${msg.id}`);
        const data = await response.json();
        if (data.success) {
            selectedMessage.value = data.message;
            showDetail.value = true;
            // Refresh the list to reflect read status
            router.reload({ only: ['messages', 'unreadCount'] });
        }
    } catch (error) {
        console.error('Failed to load message:', error);
    }
};

const replyText = ref('');
const isSendingReply = ref(false);
const attachments = ref([]);
const attachmentError = ref('');

const closeDetail = () => {
    showDetail.value = false;
    selectedMessage.value = null;
    replyText.value = '';
    attachments.value = [];
    attachmentError.value = '';
};

const handleFileSelect = (event) => {
    attachmentError.value = '';
    const files = Array.from(event.target.files);
    
    if (attachments.value.length + files.length > 5) {
        attachmentError.value = 'You can only attach up to 5 files.';
        return;
    }

    const validTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'image/jpeg', 'image/png', 'image/gif'];
    
    for (const file of files) {
        if (!validTypes.includes(file.type) && !file.name.match(/\.(pdf|doc|docx|xls|xlsx|jpg|jpeg|png|gif)$/i)) {
            attachmentError.value = `File type not supported: ${file.name}`;
            return;
        }
        if (file.size > 5 * 1024 * 1024) {
            attachmentError.value = `File too large (Max 5MB): ${file.name}`;
            return;
        }
    }

    attachments.value = [...attachments.value, ...files];
    event.target.value = ''; // Reset input
};

const removeAttachment = (index) => {
    attachments.value.splice(index, 1);
};

const sendReply = (msg) => {
    if (!replyText.value.trim() || isSendingReply.value) return;

    isSendingReply.value = true;
    
    router.post(route('admin.support.reply', msg.id), {
        reply: replyText.value.trim(),
        attachments: attachments.value
    }, {
        preserveScroll: true,
        onSuccess: () => {
            if (selectedMessage.value?.id === msg.id) {
                selectedMessage.value.status = 'replied';
            }
            replyText.value = '';
            attachments.value = [];
            closeDetail();
        },
        onFinish: () => {
            isSendingReply.value = false;
        }
    });
};

const updateStatus = (msg, status) => {
    router.put(route('admin.support.updateStatus', msg.id), { status }, {
        preserveScroll: true,
        onSuccess: () => {
            if (selectedMessage.value?.id === msg.id) {
                selectedMessage.value.status = status;
            }
        }
    });
};

const deleteMessage = (msg) => {
    Swal.fire({
        title: 'Delete this message?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Delete',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('admin.support.destroy', msg.id), {
                preserveScroll: true,
                onSuccess: () => {
                    closeDetail();
                }
            });
        }
    });
};

const formatDate = (dateStr) => {
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', {
        month: 'short', day: 'numeric', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
};

const statusBadge = (status) => {
    const map = {
        unread: 'bg-blue-100 text-blue-700',
        read: 'bg-gray-100 text-gray-600',
        replied: 'bg-green-100 text-green-700',
        archived: 'bg-yellow-100 text-yellow-700',
    };
    return map[status] || 'bg-gray-100 text-gray-600';
};
</script>

<template>
    <div>
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-xl font-bold text-gray-900">Support & Tickets</h1>
        </div>

        <div class="space-y-6">
            <!-- Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl border border-gray-200 p-5 flex items-center gap-4">
                    <div class="h-12 w-12 bg-blue-100 rounded-xl flex items-center justify-center">
                        <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900">{{ messages.total || 0 }}</p>
                        <p class="text-xs text-gray-500">Total Messages</p>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-5 flex items-center gap-4">
                    <div class="h-12 w-12 bg-red-100 rounded-xl flex items-center justify-center">
                        <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-red-600">{{ unreadCount }}</p>
                        <p class="text-xs text-gray-500">Unread</p>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex gap-2 flex-wrap">
                <button
                    v-for="f in filters"
                    :key="f.value"
                    @click="setFilter(f.value)"
                    :class="[
                        'px-4 py-2 rounded-lg text-sm font-medium transition-colors',
                        currentFilter === f.value
                            ? 'bg-teal-600 text-white'
                            : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50'
                    ]"
                >
                    {{ f.label }}
                </button>
            </div>

            <!-- Messages List -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div v-if="messages.data && messages.data.length > 0">
                    <div
                        v-for="msg in messages.data"
                        :key="msg.id"
                        @click="viewMessage(msg)"
                        :class="[
                            'flex items-start gap-4 p-5 border-b border-gray-100 cursor-pointer transition-colors hover:bg-gray-50',
                            msg.status === 'unread' ? 'bg-blue-50/50' : ''
                        ]"
                    >
                        <!-- Avatar -->
                        <div class="h-10 w-10 rounded-full bg-gradient-to-br from-teal-500 to-emerald-400 flex items-center justify-center text-white font-bold flex-shrink-0">
                            {{ msg.name?.charAt(0)?.toUpperCase() || '?' }}
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span :class="['text-sm', msg.status === 'unread' ? 'font-bold text-gray-900' : 'font-medium text-gray-700']">
                                    {{ msg.name }}
                                </span>
                                <span :class="['text-[10px] px-2 py-0.5 rounded-full font-semibold uppercase tracking-wider', statusBadge(msg.status)]">
                                    {{ msg.status }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-500 truncate">{{ msg.email }}</p>
                            <p class="text-sm text-gray-400 truncate mt-1">{{ msg.message }}</p>
                        </div>

                        <!-- Date -->
                        <div class="text-xs text-gray-400 whitespace-nowrap flex-shrink-0">
                            {{ formatDate(msg.created_at) }}
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="p-16 text-center">
                    <svg class="mx-auto h-16 w-16 text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-500">No messages found</h3>
                    <p class="text-sm text-gray-400 mt-1">Contact messages from the landing page will appear here.</p>
                </div>

                <!-- Pagination -->
                <div v-if="messages.links && messages.last_page > 1" class="flex justify-center gap-1 p-4 border-t border-gray-100">
                    <button
                        v-for="link in messages.links"
                        :key="link.label"
                        @click="link.url && router.get(link.url, {}, { preserveState: true })"
                        :disabled="!link.url"
                        :class="[
                            'px-3 py-1.5 text-sm rounded-md transition-colors',
                            link.active ? 'bg-teal-600 text-white' : link.url ? 'bg-gray-100 hover:bg-gray-200 text-gray-600' : 'text-gray-300 cursor-not-allowed'
                        ]"
                        v-html="link.label"
                    ></button>
                </div>
            </div>
        </div>

        <!-- Message Detail Modal -->
        <Teleport to="body">
            <div v-if="showDetail && selectedMessage" class="fixed inset-0 z-50 flex items-center justify-center">
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeDetail"></div>
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden">
                    <!-- Header -->
                    <div class="bg-gradient-to-r from-teal-600 to-emerald-500 p-6 text-white">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="text-lg font-bold">{{ selectedMessage.name }}</h3>
                                <p class="text-teal-100 text-sm mt-1">{{ selectedMessage.email }}</p>
                            </div>
                            <button @click="closeDetail" class="text-white/70 hover:text-white transition">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        <div class="flex gap-2 mt-3">
                            <span :class="['text-xs px-2.5 py-1 rounded-full font-semibold uppercase', statusBadge(selectedMessage.status)]">
                                {{ selectedMessage.status }}
                            </span>
                            <span v-if="selectedMessage.recaptcha_score" class="text-xs px-2.5 py-1 rounded-full bg-white/20 text-white">
                                reCAPTCHA: {{ selectedMessage.recaptcha_score }}
                            </span>
                        </div>
                    </div>

                    <!-- Message Body -->
                    <div class="p-6">
                        <p class="text-xs text-gray-400 mb-2">{{ formatDate(selectedMessage.created_at) }}</p>
                        <div class="bg-gray-50 rounded-lg p-4 text-sm text-gray-700 whitespace-pre-wrap leading-relaxed">
                            {{ selectedMessage.message }}
                        </div>

                        <p v-if="selectedMessage.ip_address" class="text-xs text-gray-400 mt-3">
                            IP: {{ selectedMessage.ip_address }}
                        </p>
                    </div>

                    <!-- Reply Form & Actions -->
                    <div class="border-t border-gray-100 bg-gray-50/50 p-4">
                        <div v-if="selectedMessage.status !== 'replied'" class="mb-4">
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Reply via Email</label>
                            <textarea
                                v-model="replyText"
                                rows="3"
                                placeholder="Write your reply to the user..."
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500/40 focus:border-teal-500 transition-colors text-sm resize-none outline-none"
                            ></textarea>
                            
                            <!-- Attachments -->
                            <div class="mt-2">
                                <label class="flex items-center gap-2 cursor-pointer text-sm text-teal-600 hover:text-teal-700 font-medium transition w-fit">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13" />
                                    </svg>
                                    Attach Files (Max 5MB)
                                    <input type="file" multiple class="hidden" @change="handleFileSelect" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif">
                                </label>
                                
                                <div v-if="attachments.length > 0" class="mt-2 space-y-1">
                                    <div v-for="(file, index) in attachments" :key="index" class="flex items-center justify-between bg-white px-3 py-1.5 rounded border border-gray-200 text-xs text-gray-600">
                                        <div class="flex items-center gap-2 overflow-hidden">
                                            <svg class="h-3 w-3 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                            </svg>
                                            <span class="truncate">{{ file.name }}</span>
                                            <span class="text-gray-400">({{(file.size / 1024 / 1024).toFixed(2)}}MB)</span>
                                        </div>
                                        <button @click="removeAttachment(index)" class="text-red-400 hover:text-red-600 ml-2" title="Remove attachment">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                        </button>
                                    </div>
                                </div>
                                <p v-if="attachmentError" class="text-xs text-red-500 mt-1">{{ attachmentError }}</p>
                            </div>

                            <div class="flex justify-end mt-3 border-t border-gray-200/50 pt-3">
                                <button
                                    @click="sendReply(selectedMessage)"
                                    :disabled="!replyText.trim() || isSendingReply"
                                    class="px-4 py-2 text-sm font-bold bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition disabled:bg-gray-300 disabled:cursor-not-allowed flex items-center gap-2 shadow-sm"
                                >
                                    <svg v-if="isSendingReply" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.125A59.769 59.769 0 0121.485 12 59.768 59.768 0 013.27 20.875L5.999 12Zm0 0h7.5" />
                                    </svg>
                                    {{ isSendingReply ? 'Sending...' : 'Send Reply' }}
                                </button>
                            </div>
                        </div>

                        <!-- Secondary Actions -->
                        <div class="flex gap-2 justify-end pt-2" :class="{'border-t border-gray-200': selectedMessage.status !== 'replied'}">
                            <button
                                v-if="selectedMessage.status !== 'replied'"
                                @click="updateStatus(selectedMessage, 'replied')"
                                class="px-3 py-1.5 text-xs font-semibold bg-white border border-gray-300 text-gray-700 rounded hover:bg-gray-50 transition"
                                title="Mark as replied without sending an email"
                            >
                                Mark as Replied (Manual)
                            </button>
                            <button
                                v-if="selectedMessage.status !== 'archived'"
                                @click="updateStatus(selectedMessage, 'archived')"
                                class="px-3 py-1.5 text-xs font-semibold bg-white border border-yellow-400 text-yellow-600 rounded hover:bg-yellow-50 transition"
                            >
                                Archive
                            </button>
                            <button
                                @click="deleteMessage(selectedMessage)"
                                class="px-3 py-1.5 text-xs font-semibold bg-white border border-red-400 text-red-600 rounded hover:bg-red-50 transition"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
