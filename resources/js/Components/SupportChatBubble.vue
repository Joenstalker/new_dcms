<script setup>
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';

const page = usePage();
const primaryColor = computed(() => page.props.branding?.primary_color || '#0ea5e9');
const canAccessSupportChat = computed(() => {
    const permissions = page.props.auth?.user?.permissions || [];
    const roles = page.props.auth?.user?.roles || [];

    return roles.includes('Owner') || permissions.includes('access support chat');
});
const bubbleBottomOffset = computed(() => {
    const raw = Number(page.props.branding?.support_chat_bottom_offset ?? 56);
    if (!Number.isFinite(raw)) {
        return 56;
    }

    return Math.min(160, Math.max(16, Math.round(raw)));
});

const isOpen = ref(false);
const activeView = ref('list'); // 'list' | 'chat' | 'new'
const tickets = ref([]);
const activeTicket = ref(null);
const loading = ref(false);
const sending = ref(false);
const messageContent = ref('');
const attachments = ref([]);
const chatContainer = ref(null);
const fileInput = ref(null);
const unreadCount = ref(0);
const isModalOpen = ref(false);
let pollInterval = null;
let currentEchoChannel = null;
let bodyClassObserver = null;

const bubbleLayerStyle = computed(() => ({
    zIndex: isModalOpen.value ? 120 : 9999,
    pointerEvents: isModalOpen.value ? 'none' : 'auto',
}));

const syncModalState = () => {
    if (typeof document === 'undefined') {
        isModalOpen.value = false;
        return;
    }

    isModalOpen.value = document.body.classList.contains('has-open-modal');
};

watch(activeTicket, (newTicket, oldTicket) => {
    if (window.Echo) {
        // Only leave if the TICKET ID actually changed or we are closing
        if (oldTicket && (!newTicket || oldTicket.id !== newTicket.id) && currentEchoChannel) {
            window.Echo.leave('support.ticket.' + oldTicket.id);
            currentEchoChannel = null;
        }
        
        // Only join if we don't have a channel for THIS ticket yet
        if (newTicket && !currentEchoChannel) {
            currentEchoChannel = window.Echo.private('support.ticket.' + newTicket.id)
                .listen('.SupportTicketUpdated', (e) => {
                    console.log('Tenant Received Support Event:', e);
                    // Silently refresh the messages
                    fetchTicketMessages(newTicket.id);
                    fetchTickets(true); // update list implicitly
                });
        }
    }
});

const fetchTicketMessages = async (ticketId) => {
    try {
        const { data } = await axios.get(route('tenant.support.show', ticketId));
        if (data?.ticket && activeTicket.value && activeTicket.value.id === ticketId) {
            activeTicket.value.messages = data.ticket.messages;
            activeTicket.value.status = data.ticket.status;
        }
    } catch (e) {
        console.error('Failed to refresh messages', e);
    }
};

watch(() => activeTicket.value?.messages?.length, (newLen, oldLen) => {
    if (newLen > oldLen) {
        scrollToBottom();
    }
});

watch(activeView, (newView) => {
    if (newView === 'chat') {
        scrollToBottom();
    }
});

watch(isOpen, (newVal) => {
    if (newVal && activeView.value === 'chat') {
        scrollToBottom();
    }
});

// New ticket form
const newTicket = ref({
    subject: '',
    category: 'other',
    priority: 'medium',
    content: '',
});

const categories = [
    { value: 'billing', label: '💳 Billing' },
    { value: 'technical', label: '🐛 Technical / Bug' },
    { value: 'feature_request', label: '✨ Feature Request' },
    { value: 'account', label: '👤 Account' },
    { value: 'other', label: '📌 Other' },
];

const priorities = [
    { value: 'low', label: 'Low' },
    { value: 'medium', label: 'Medium' },
    { value: 'high', label: 'High' },
    { value: 'urgent', label: 'Urgent' },
];

const statusColors = {
    open: 'badge-info',
    in_progress: 'badge-warning',
    pending: 'badge-ghost',
    resolved: 'badge-success',
    closed: 'badge-neutral',
};

const toggle = () => {
    isOpen.value = !isOpen.value;
    if (isOpen.value && tickets.value.length === 0) {
        fetchTickets();
    }
};

const fetchTickets = async (silent = false) => {
    if (!silent) loading.value = true;
    try {
        const { data } = await axios.get(route('tenant.support.index'));
        tickets.value = data.tickets || [];
        unreadCount.value = tickets.value.filter(t => t.status === 'open' || t.status === 'in_progress').length;
    } catch (e) {
        console.error('Failed to fetch tickets', e);
    } finally {
        if (!silent) loading.value = false;
    }
};

const openTicket = async (ticket, silent = false) => {
    if (activeTicket.value && activeTicket.value.id !== ticket.id) {
        activeTicket.value = ticket;
    }
    activeView.value = 'chat';
    if (!silent) loading.value = true;
    try {
        const { data } = await axios.get(route('tenant.support.show', ticket.id));
        activeTicket.value = data.ticket;
    } catch (e) {
        console.error('Failed to load ticket', e);
    } finally {
        if (!silent) loading.value = false;
        scrollToBottom();
    }
};

const sendMessage = async () => {
    if (!messageContent.value.trim() && attachments.value.length === 0) return;
    sending.value = true;

    const formData = new FormData();
    formData.append('content', messageContent.value);
    attachments.value.forEach(file => formData.append('attachments[]', file));

    try {
        const { data } = await axios.post(
            route('tenant.support.message', activeTicket.value.id),
            formData,
            { headers: { 'Content-Type': 'multipart/form-data' } }
        );
        activeTicket.value.messages.push(data.message);
        messageContent.value = '';
        attachments.value = [];
        scrollToBottom();
    } catch (e) {
        console.error('Failed to send message', e);
    } finally {
        sending.value = false;
    }
};

const createTicket = async () => {
    if (!newTicket.value.subject.trim() || !newTicket.value.content.trim()) return;
    sending.value = true;

    const formData = new FormData();
    formData.append('subject', newTicket.value.subject);
    formData.append('category', newTicket.value.category);
    formData.append('priority', newTicket.value.priority);
    formData.append('content', newTicket.value.content);
    attachments.value.forEach(file => formData.append('attachments[]', file));

    try {
        const { data } = await axios.post(route('tenant.support.store'), formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        tickets.value.unshift(data.ticket);
        activeTicket.value = data.ticket;
        activeView.value = 'chat';
        resetNewTicket();
    } catch (e) {
        console.error('Failed to create ticket', e);
    } finally {
        sending.value = false;
    }
};

const resetNewTicket = () => {
    newTicket.value = { subject: '', category: 'other', priority: 'medium', content: '' };
    attachments.value = [];
};

const handleFileSelect = (e) => {
    const files = Array.from(e.target.files);
    attachments.value.push(...files);
    if (fileInput.value) fileInput.value.value = '';
};

const removeAttachment = (index) => {
    attachments.value.splice(index, 1);
};

const scrollToBottom = () => {
    nextTick(() => {
        if (chatContainer.value) {
            chatContainer.value.scrollTop = chatContainer.value.scrollHeight;
            // Fallback for slower rendering/images
            setTimeout(() => {
                if (chatContainer.value) {
                    chatContainer.value.scrollTop = chatContainer.value.scrollHeight;
                }
            }, 100);
        }
    });
};

const goBack = () => {
    activeView.value = 'list';
    activeTicket.value = null;
    fetchTickets();
};

const formatTime = (dateStr) => {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    const now = new Date();
    const diffMs = now - d;
    const diffMins = Math.floor(diffMs / 60000);
    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins}m ago`;
    const diffHours = Math.floor(diffMins / 60);
    if (diffHours < 24) return `${diffHours}h ago`;
    const diffDays = Math.floor(diffHours / 24);
    if (diffDays < 7) return `${diffDays}d ago`;
    return d.toLocaleDateString();
};

const isImage = (type) => type && type.startsWith('image/');

const getFileIcon = (type) => {
    if (type?.includes('pdf')) return '📄';
    if (type?.includes('doc')) return '📝';
    if (type?.includes('zip') || type?.includes('rar')) return '📦';
    return '📎';
};

const getMessageSenderName = (msg) => {
    if (msg?.sender_name) return msg.sender_name;
    if (msg?.sender?.name) return msg.sender.name;
    return msg?.sender_type === 'admin' ? 'Support Admin' : 'Clinic Staff';
};

const getMessageAvatar = (msg) => {
    if (msg?.sender_avatar_url) return msg.sender_avatar_url;
    if (msg?.sender?.profile_picture_url) return msg.sender.profile_picture_url;
    if (msg?.sender?.profile_photo_url) return msg.sender.profile_photo_url;

    const fallbackName = getMessageSenderName(msg);
    const bg = msg?.sender_type === 'admin' ? '334155' : '1F2937';
    return `https://ui-avatars.com/api/?name=${encodeURIComponent(fallbackName)}&color=FFFFFF&background=${bg}`;
};

onMounted(() => {
    syncModalState();

    if (typeof MutationObserver !== 'undefined' && typeof document !== 'undefined') {
        bodyClassObserver = new MutationObserver(() => {
            syncModalState();
        });

        bodyClassObserver.observe(document.body, {
            attributes: true,
            attributeFilter: ['class'],
        });
    }

    // Polling removed in favor of real-time WebSockets (Echo)
});

onUnmounted(() => {
    if (bodyClassObserver) {
        bodyClassObserver.disconnect();
        bodyClassObserver = null;
    }

    // Cleanup Echo listeners is handled by the activeTicket watcher
});
</script>

<template>
    <div v-if="canAccessSupportChat">
    <!-- Floating Chat Bubble -->
    <div class="fixed right-6" :style="{ bottom: `${bubbleBottomOffset}px`, ...bubbleLayerStyle }">
        <!-- Unread Badge -->
        <div 
            v-if="unreadCount > 0 && !isOpen"
            class="absolute -top-2 -right-1 h-5 min-w-5 px-1 rounded-full bg-error text-white text-[10px] font-black flex items-center justify-center shadow-lg animate-bounce z-10"
        >
            {{ unreadCount }}
        </div>

        <!-- Chat Bubble Button -->
        <button
            @click="toggle"
            class="h-14 w-14 rounded-full shadow-2xl flex items-center justify-center transition-all duration-300 hover:scale-110 hover:shadow-primary/40"
            :style="{ backgroundColor: primaryColor }"
        >
            <!-- Chat Icon -->
            <svg v-if="!isOpen" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="white" class="w-7 h-7">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />
            </svg>
            <!-- Close Icon -->
            <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="white" class="w-7 h-7">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>

        <!-- Chat Window -->
        <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 translate-y-4 scale-95"
            enter-to-class="opacity-100 translate-y-0 scale-100"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100 translate-y-0 scale-100"
            leave-to-class="opacity-0 translate-y-4 scale-95"
        >
            <div 
                v-if="isOpen"
                class="absolute bottom-20 right-0 w-[380px] max-h-[520px] bg-base-100 rounded-2xl shadow-2xl border border-base-300 overflow-hidden flex flex-col"
            >
                <!-- Header -->
                <div 
                    class="px-5 py-4 flex items-center justify-between shrink-0"
                    :style="{ backgroundColor: primaryColor }"
                >
                    <div class="flex items-center gap-3">
                        <button 
                            v-if="activeView !== 'list'" 
                            @click="goBack"
                            class="text-white/80 hover:text-white transition-colors"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                            </svg>
                        </button>
                        <div>
                            <h3 class="text-white font-black text-sm tracking-wide">
                                {{ activeView === 'list' ? 'Support Chat' : activeView === 'new' ? 'New Ticket' : activeTicket?.subject }}
                            </h3>
                            <p v-if="activeView === 'chat' && activeTicket" class="text-white/60 text-[10px] uppercase tracking-widest font-bold">
                                {{ activeTicket.category }} · {{ activeTicket.status?.replace('_', ' ') }}
                            </p>
                            <p v-else-if="activeView === 'list'" class="text-white/60 text-[10px] uppercase tracking-widest font-bold">
                                We're here to help
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Ticket List View -->
                <div v-if="activeView === 'list'" class="flex-1 overflow-y-auto">
                    <div v-if="loading" class="flex items-center justify-center py-12">
                        <span class="loading loading-spinner loading-md" :style="{ color: primaryColor }"></span>
                    </div>

                    <div v-else-if="tickets.length === 0" class="flex flex-col items-center justify-center py-12 px-6 text-center">
                        <div class="w-16 h-16 rounded-full bg-base-200 flex items-center justify-center mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-base-content/30">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />
                            </svg>
                        </div>
                        <p class="text-base-content/50 text-xs font-semibold">No conversations yet</p>
                        <p class="text-base-content/30 text-[10px] mt-1">Start a new ticket below</p>
                    </div>

                    <div v-else class="divide-y divide-base-200">
                        <button
                            v-for="ticket in tickets"
                            :key="ticket.id"
                            @click="openTicket(ticket)"
                            class="w-full text-left px-5 py-3.5 hover:bg-base-200/50 transition-colors group"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-bold text-base-content truncate group-hover:text-primary transition-colors">{{ ticket.subject }}</p>
                                    <p class="text-xs text-base-content/40 mt-0.5 truncate">
                                        {{ ticket.latest_message?.content || ticket.description }}
                                    </p>
                                </div>
                                <div class="flex flex-col items-end gap-1 shrink-0">
                                    <span class="text-[10px] text-base-content/30 font-medium">{{ formatTime(ticket.updated_at) }}</span>
                                    <span :class="statusColors[ticket.status]" class="badge badge-xs text-[9px] font-bold uppercase">
                                        {{ ticket.status?.replace('_', ' ') }}
                                    </span>
                                </div>
                            </div>
                        </button>
                    </div>

                    <!-- New Ticket Button -->
                    <div class="p-4 border-t border-base-200">
                        <button 
                            @click="activeView = 'new'"
                            class="btn btn-sm w-full rounded-xl font-bold text-white border-0 shadow-lg"
                            :style="{ backgroundColor: primaryColor }"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            New Ticket
                        </button>
                    </div>
                </div>

                <!-- New Ticket Form -->
                <div v-if="activeView === 'new'" class="flex-1 overflow-y-auto p-4 space-y-3">
                    <div>
                        <label class="text-[10px] font-bold uppercase tracking-widest text-base-content/40 mb-1 block">Subject</label>
                        <input v-model="newTicket.subject" type="text" placeholder="Brief description..." class="input input-sm input-bordered w-full rounded-xl text-sm" />
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-[10px] font-bold uppercase tracking-widest text-base-content/40 mb-1 block">Category</label>
                            <select v-model="newTicket.category" class="select select-sm select-bordered w-full rounded-xl text-xs">
                                <option v-for="cat in categories" :key="cat.value" :value="cat.value">{{ cat.label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-[10px] font-bold uppercase tracking-widest text-base-content/40 mb-1 block">Priority</label>
                            <select v-model="newTicket.priority" class="select select-sm select-bordered w-full rounded-xl text-xs">
                                <option v-for="p in priorities" :key="p.value" :value="p.value">{{ p.label }}</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="text-[10px] font-bold uppercase tracking-widest text-base-content/40 mb-1 block">Message</label>
                        <textarea v-model="newTicket.content" rows="4" placeholder="Describe your issue..." class="textarea textarea-sm textarea-bordered w-full rounded-xl text-sm resize-none"></textarea>
                    </div>

                    <!-- Attachment Preview -->
                    <div v-if="attachments.length > 0" class="flex flex-wrap gap-2">
                        <div v-for="(file, i) in attachments" :key="i" class="badge badge-sm gap-1 bg-base-200 border-base-300">
                            <span class="truncate max-w-[100px] text-[10px]">{{ file.name }}</span>
                            <button @click="removeAttachment(i)" class="text-error hover:text-error/80">✕</button>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <button @click="$refs.fileInputNew.click()" class="btn btn-sm btn-ghost btn-circle">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 text-base-content/40">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13" />
                            </svg>
                        </button>
                        <input ref="fileInputNew" type="file" multiple accept="image/*,.pdf,.doc,.docx,.zip" @change="handleFileSelect" class="hidden" />
                        <button 
                            @click="createTicket"
                            :disabled="!newTicket.subject.trim() || !newTicket.content.trim() || sending"
                            class="btn btn-sm flex-1 rounded-xl font-bold text-white border-0"
                            :style="{ backgroundColor: primaryColor }"
                        >
                            <span v-if="sending" class="loading loading-spinner loading-xs"></span>
                            <span v-else>Submit Ticket</span>
                        </button>
                    </div>
                </div>

                <!-- Chat View -->
                <div v-if="activeView === 'chat'" class="flex-1 flex flex-col overflow-hidden">
                    <!-- Messages -->
                    <div ref="chatContainer" class="flex-1 overflow-y-auto p-4 space-y-3">
                        <div v-if="loading" class="flex items-center justify-center py-8">
                            <span class="loading loading-spinner loading-md" :style="{ color: primaryColor }"></span>
                        </div>

                        <template v-else-if="activeTicket?.messages">
                            <div 
                                v-for="msg in activeTicket.messages" 
                                :key="msg.id"
                                :class="msg.sender_type === 'tenant' ? 'items-end' : 'items-start'"
                                class="flex flex-col"
                            >
                                <div class="flex items-end gap-2" :class="msg.sender_type === 'tenant' ? 'flex-row-reverse' : ''">
                                    <!-- Avatar -->
                                    <div class="h-8 w-8 rounded-full shrink-0 overflow-hidden border border-base-300 bg-base-200">
                                        <img :src="getMessageAvatar(msg)" :alt="getMessageSenderName(msg)" class="h-full w-full object-cover" />
                                    </div>

                                    <!-- Bubble -->
                                    <div 
                                        class="max-w-[260px] px-4 py-2.5 text-sm rounded-2xl shadow-sm"
                                        :class="msg.sender_type === 'tenant' 
                                            ? 'rounded-br-md text-white' 
                                            : 'rounded-bl-md bg-base-200 text-base-content'"
                                        :style="msg.sender_type === 'tenant' ? { backgroundColor: primaryColor } : {}"
                                    >
                                        <p class="text-[10px] font-black mb-1.5 opacity-80 tracking-wide">
                                            {{ getMessageSenderName(msg) }}
                                        </p>
                                        <p class="whitespace-pre-wrap break-words text-[13px]">{{ msg.content }}</p>

                                        <!-- Attachments -->
                                        <div v-if="msg.attachments?.length" class="mt-2 space-y-1.5">
                                            <template v-for="att in msg.attachments" :key="att.id">
                                                <a 
                                                    v-if="isImage(att.file_type)" 
                                                    :href="`/storage/${att.file_path}`" 
                                                    target="_blank"
                                                    class="block rounded-lg overflow-hidden border border-white/20"
                                                >
                                                    <img :src="`/storage/${att.file_path}`" :alt="att.file_name" class="w-full max-h-32 object-cover" />
                                                </a>
                                                <a 
                                                    v-else 
                                                    :href="`/storage/${att.file_path}`" 
                                                    target="_blank"
                                                    class="flex items-center gap-1.5 text-[11px] px-2 py-1 rounded-lg"
                                                    :class="msg.sender_type === 'tenant' ? 'text-white/80 bg-white/10 hover:bg-white/20' : 'text-base-content/60 bg-base-300 hover:bg-base-300/80'"
                                                >
                                                    <span>{{ getFileIcon(att.file_type) }}</span>
                                                    <span class="truncate max-w-[150px]">{{ att.file_name }}</span>
                                                </a>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                                <span 
                                    class="text-[9px] mt-1 px-10 font-medium"
                                    :class="msg.sender_type === 'tenant' ? 'text-right text-base-content/25' : 'text-base-content/25'"
                                >
                                    {{ formatTime(msg.created_at) }}
                                </span>
                            </div>
                        </template>
                    </div>

                    <!-- Message Input -->
                    <div class="border-t border-base-200 p-3 shrink-0 bg-base-100">
                        <!-- Attachment Preview -->
                        <div v-if="attachments.length > 0" class="flex flex-wrap gap-1.5 mb-2">
                            <div v-for="(file, i) in attachments" :key="i" class="badge badge-sm gap-1 bg-base-200 border-base-300">
                                <span class="truncate max-w-[80px] text-[10px]">{{ file.name }}</span>
                                <button @click="removeAttachment(i)" class="text-error hover:text-error/80">✕</button>
                            </div>
                        </div>

                        <div class="flex items-end gap-2">
                            <button @click="$refs.fileInputChat.click()" class="btn btn-sm btn-ghost btn-circle shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 text-base-content/40">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13" />
                                </svg>
                            </button>
                            <input ref="fileInputChat" type="file" multiple accept="image/*,.pdf,.doc,.docx,.zip" @change="handleFileSelect" class="hidden" />

                            <textarea
                                v-model="messageContent"
                                @keydown.enter.exact.prevent="sendMessage"
                                placeholder="Type a message..."
                                rows="1"
                                class="textarea textarea-sm textarea-bordered flex-1 rounded-xl text-sm resize-none min-h-[36px] max-h-[80px]"
                            ></textarea>

                            <button 
                                @click="sendMessage"
                                :disabled="(!messageContent.trim() && attachments.length === 0) || sending"
                                class="btn btn-sm btn-circle shrink-0 border-0 text-white"
                                :style="{ backgroundColor: primaryColor }"
                            >
                                <span v-if="sending" class="loading loading-spinner loading-xs"></span>
                                <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
    </div>
</template>
