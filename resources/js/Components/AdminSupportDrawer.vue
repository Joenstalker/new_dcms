<script setup>
import { ref, onMounted, onUnmounted, nextTick, computed, watch } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import { supportState } from '@/States/supportState';
import axios from 'axios';
import Swal from 'sweetalert2';

const page = usePage();
const branding = computed(() => page.props.branding || {});
const primaryColor = computed(() => branding.value.primary_color || '#0ea5e9');

const adminChatContainer = ref(null);
const replyContent = ref(null);
const isSendingReply = ref(false);
const replyAttachments = ref([]);
const failedAvatarMessages = ref(new Set());

const statusColors = {
    open: 'badge-info',
    in_progress: 'badge-warning',
    pending: 'badge-ghost',
    resolved: 'badge-success',
    closed: 'badge-neutral',
};

watch(() => supportState.selectedTicket?.id, () => {
    scrollToBottom();
});

watch(() => supportState.selectedTicket?.messages?.length, () => {
    scrollToBottom();
});

watch(() => supportState.isOpen, (newVal) => {
    if (newVal) {
        scrollToBottom();
    }
});

const scrollToBottom = () => {
    nextTick(() => {
        if (adminChatContainer.value) {
            adminChatContainer.value.scrollTop = adminChatContainer.value.scrollHeight;
            setTimeout(() => {
                if (adminChatContainer.value) {
                    adminChatContainer.value.scrollTop = adminChatContainer.value.scrollHeight;
                }
            }, 100);
        }
    });
};

onMounted(() => {
    if (window.Echo) {
        window.Echo.private('admin.support.tickets')
            .listen('.SupportTicketUpdated', (e) => {
                console.log('Admin Received Support Event:', e);
                // If it's already open and it's the SAME ticket, refresh SILENTLY
                const isViewingThisTicket = supportState.isOpen && supportState.selectedTicket?.id === e.ticket.id;
                
                // Only open/fetch loudly if it's a DIFFERENT ticket or if the drawer is closed
                supportState.viewTicket(e.ticket.id, isViewingThisTicket);

                // Also reload page data if on the support index
                if (route().current('admin.support.index')) {
                    router.reload({ only: ['tickets', 'stats'] });
                }
            });
    }
});

onUnmounted(() => {
    if (window.Echo) {
        window.Echo.leave('admin.support.tickets');
    }
});

const sendReply = () => {
    const text = replyContent.value?.value || '';
    if (!text.trim() || isSendingReply.value) return;
    isSendingReply.value = true;

    const formData = new FormData();
    formData.append('content', text.trim());
    replyAttachments.value.forEach(f => formData.append('attachments[]', f));

    axios.post(route('admin.support.reply', supportState.selectedTicket.id), formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
    }).then(({ data }) => {
        if (replyContent.value) replyContent.value.value = '';
        replyAttachments.value = [];
        // Refresh silently after reply to avoid spinner
        supportState.viewTicket(supportState.selectedTicket.id, true);
    }).catch(e => {
        console.error('Failed to send reply:', e);
    }).finally(() => {
        isSendingReply.value = false;
    });
};

const updateStatus = (status) => {
    axios.put(route('admin.support.updateStatus', supportState.selectedTicket.id), { status })
        .then(() => {
            supportState.selectedTicket.status = status;
            if (route().current('admin.support.index')) {
                router.reload({ only: ['tickets'] });
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
            router.delete(route('admin.support.destroy', supportState.selectedTicket.id), {
                onSuccess: () => supportState.close(),
            });
        }
    });
};

const formatTime = (d) => d ? new Date(d).toLocaleString() : '';
const isImage = (type) => type?.startsWith('image/');
const getSenderName = (msg) => {
    if (msg?.sender_name) return msg.sender_name;
    if (msg?.sender?.name) return msg.sender.name;

    if (msg?.sender_type === 'tenant') {
        return supportState.selectedTicket?.tenant?.name || 'Tenant Clinic';
    }

    return 'Support Admin';
};

const getSenderAvatar = (msg) => {
    const messageKey = String(msg?.id || '');

    if (messageKey && failedAvatarMessages.value.has(messageKey)) {
        const name = getSenderName(msg);
        const bg = msg?.sender_type === 'admin' ? '334155' : '1F2937';
        return `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&color=FFFFFF&background=${bg}`;
    }

    if (msg?.sender_avatar_url) return msg.sender_avatar_url;
    if (msg?.sender?.profile_picture_url) return msg.sender.profile_picture_url;
    if (msg?.sender?.profile_photo_url) return msg.sender.profile_photo_url;

    const name = getSenderName(msg);
    const bg = msg?.sender_type === 'admin' ? '334155' : '1F2937';
    return `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&color=FFFFFF&background=${bg}`;
};

const handleAvatarError = (msg) => {
    const messageKey = String(msg?.id || '');
    if (!messageKey) return;

    const updated = new Set(failedAvatarMessages.value);
    updated.add(messageKey);
    failedAvatarMessages.value = updated;
};

const handleFileSelect = (e) => {
    replyAttachments.value.push(...Array.from(e.target.files));
    e.target.value = '';
};
const removeAttachment = (i) => replyAttachments.value.splice(i, 1);
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-all duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-all duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="supportState.isOpen" class="fixed inset-0 bg-black/50 z-[9999]" @click.self="supportState.close()">
                <div class="absolute right-0 top-0 h-full w-full max-w-lg bg-base-100 shadow-2xl flex flex-col animate-slide-in-right">
                    <!-- Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-base-300 shrink-0">
                        <div v-if="supportState.selectedTicket">
                            <h2 class="text-lg font-black text-base-content">{{ supportState.selectedTicket.subject }}</h2>
                            <div class="flex items-center gap-2 mt-1">
                                <span :class="statusColors[supportState.selectedTicket.status]" class="badge badge-sm text-[10px] font-bold uppercase">
                                    {{ supportState.selectedTicket.status?.replace('_', ' ') }}
                                </span>
                                <span class="text-[10px] text-base-content/40 font-bold uppercase">{{ supportState.selectedTicket.category }}</span>
                            </div>
                        </div>
                        <div v-else class="h-10 flex items-center">
                            <span class="loading loading-dots loading-sm"></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div v-if="supportState.selectedTicket" class="dropdown dropdown-end">
                                <button tabindex="0" class="btn btn-ghost btn-xs">Status ▼</button>
                                <ul tabindex="0" class="dropdown-content menu p-2 shadow-lg bg-base-100 rounded-xl w-48 z-[300] border border-base-300">
                                    <li v-for="s in ['open','in_progress','pending','resolved','closed']" :key="s">
                                        <a @click="updateStatus(s)" class="text-xs font-bold uppercase">{{ s.replace('_', ' ') }}</a>
                                    </li>
                                </ul>
                            </div>
                            <button @click="deleteTicket" class="btn btn-ghost btn-xs text-error">🗑</button>
                            <button @click="supportState.close()" class="btn btn-ghost btn-xs btn-circle">✕</button>
                        </div>
                    </div>

                    <!-- Messages -->
                    <div ref="adminChatContainer" class="flex-1 overflow-y-auto p-4 space-y-3">
                        <div v-if="supportState.loading" class="flex justify-center py-10">
                            <span class="loading loading-spinner loading-md"></span>
                        </div>
                        <template v-else-if="supportState.selectedTicket?.messages">
                            <div 
                                v-for="msg in supportState.selectedTicket.messages" 
                                :key="msg.id"
                                :class="msg.sender_type === 'admin' ? 'items-end' : 'items-start'"
                                class="flex flex-col"
                            >
                                <div class="flex items-end gap-2" :class="msg.sender_type === 'admin' ? 'flex-row-reverse' : ''">
                                    <div class="h-8 w-8 rounded-full shrink-0 overflow-hidden border border-base-300 bg-base-200">
                                        <img :src="getSenderAvatar(msg)" :alt="getSenderName(msg)" class="h-full w-full object-cover" @error="handleAvatarError(msg)" />
                                    </div>
                                    <div 
                                        class="max-w-[320px] px-4 py-2.5 text-sm rounded-2xl"
                                        :class="msg.sender_type === 'admin' ? 'bg-primary text-primary-content rounded-br-md' : 'bg-base-200 text-base-content rounded-bl-md'"
                                        :style="msg.sender_type === 'admin' ? { backgroundColor: primaryColor } : {}"
                                    >
                                        <p class="text-[10px] font-black mb-1.5 opacity-80 tracking-wide">
                                            {{ getSenderName(msg) }}
                                        </p>
                                        <p class="whitespace-pre-wrap break-words">{{ msg.content }}</p>
                                        <div v-if="msg.attachments?.length" class="mt-2 space-y-1">
                                            <template v-for="att in msg.attachments" :key="att.id">
                                                <a v-if="isImage(att.file_type)" :href="`/storage/${att.file_path}`" target="_blank" class="block rounded-lg overflow-hidden border border-white/10">
                                                    <img :src="`/storage/${att.file_path}`" class="w-full max-h-40 object-cover" />
                                                </a>
                                                <a v-else :href="`/storage/${att.file_path}`" target="_blank" class="flex items-center gap-1 text-[11px] opacity-70">📎 {{ att.file_name }}</a>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                                <span class="text-[9px] mt-1 px-10 text-base-content/25">{{ formatTime(msg.created_at) }}</span>
                            </div>
                        </template>
                    </div>

                    <!-- Input -->
                    <div class="border-t border-base-300 p-4 shrink-0 space-y-2">
                        <div v-if="replyAttachments.length > 0" class="flex flex-wrap gap-1.5">
                            <div v-for="(file, i) in replyAttachments" :key="i" class="badge badge-sm gap-1 bg-base-200">
                                <span class="truncate max-w-[100px] text-[10px]">{{ file.name }}</span>
                                <button @click="removeAttachment(i)" class="text-error">✕</button>
                            </div>
                        </div>
                        <div class="flex items-end gap-2">
                            <button @click="$refs.globalAdminFileInput.click()" class="btn btn-sm btn-ghost btn-circle shrink-0">📎</button>
                            <input ref="globalAdminFileInput" type="file" multiple class="hidden" @change="handleFileSelect" />
                            <textarea ref="replyContent" @keydown.enter.exact.prevent="sendReply" placeholder="Type your reply..." rows="2" class="textarea textarea-sm textarea-bordered flex-1 rounded-xl text-sm resize-none"></textarea>
                            <button @click="sendReply" :disabled="isSendingReply" class="btn btn-sm btn-primary rounded-xl" :style="{ backgroundColor: primaryColor }">Send</button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
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
