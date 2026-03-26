<script setup>
import { ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const primaryColor = page.props.branding?.primary_color || '#0ea5e9';

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    message: {
        type: Object,
        default: null
    },
    isSendingReply: {
        type: Boolean,
        default: false
    },
    attachmentError: {
        type: String,
        default: ''
    }
});

const emit = defineEmits(['close', 'send-reply', 'update-status', 'delete', 'remove-attachment']);

const replyText = ref('');
const selectedFiles = ref([]);

// Reset reply text when message changes
watch(() => props.message, () => {
    replyText.value = '';
    selectedFiles.value = [];
});

const formatDate = (date) => {
    if (!date) return '';
    const d = new Date(date);
    return d.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const handleFileUpload = (event) => {
    const files = Array.from(event.target.files);
    const validFiles = files.filter(file => {
        const maxSize = 10 * 1024 * 1024; // 10MB
        if (file.size > maxSize) {
            emit('attachment-error', `File ${file.name} is too large. Maximum size is 10MB.`);
            return false;
        }
        return true;
    });
    selectedFiles.value = [...selectedFiles.value, ...validFiles];
    event.target.value = '';
};

const removeAttachment = (index) => {
    selectedFiles.value.splice(index, 1);
};

const handleSendReply = () => {
    if (!replyText.value.trim()) return;
    emit('send-reply', { text: replyText.value, files: selectedFiles.value });
    replyText.value = '';
    selectedFiles.value = [];
};

const handleStatusUpdate = (status) => {
    emit('update-status', status);
};

const handleDelete = () => {
    emit('delete');
};

const handleClose = () => {
    replyText.value = '';
    selectedFiles.value = [];
    emit('close');
};

const formatFileSize = (bytes) => {
    return (bytes / 1024 / 1024).toFixed(2);
};
</script>

<template>
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-black/50 transition-opacity" @click="handleClose"></div>

            <!-- Modal -->
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative bg-base-100 rounded-3xl shadow-2xl w-full max-w-3xl max-h-[90vh] flex flex-col overflow-hidden border border-base-300">
                    <!-- Header -->
                    <div class="flex items-center justify-between px-8 py-5 border-b border-base-300 bg-base-200/50">
                        <h2 class="text-xl font-black text-base-content tracking-tight">Message Details</h2>
                        <button @click="handleClose" class="btn btn-ghost btn-circle btn-sm text-base-content/40 hover:text-base-content hover:bg-base-300 transition-colors">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Content -->
                    <div v-if="message" class="overflow-y-auto flex-1 custom-scrollbar">
                        <!-- Sender Info -->
                        <div class="px-8 py-6 border-b border-base-300/50 bg-base-100">
                            <div class="flex items-start gap-5">
                                <div class="avatar placeholder">
                                    <div class="text-white border border-transparent rounded-2xl w-14 h-14 shadow-sm flex items-center justify-center" :style="{ backgroundColor: primaryColor }">
                                        <span class="text-xl font-black">{{ message.name.charAt(0).toUpperCase() }}</span>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0 flex flex-col justify-center">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-base font-black text-base-content tracking-tight">{{ message.name }}</p>
                                            <div class="flex items-center gap-3 mt-1">
                                                <p class="text-xs text-base-content/60 font-bold lowercase">{{ message.email }}</p>
                                                <span v-if="message.phone" class="w-1 h-1 rounded-full bg-base-content/20"></span>
                                                <p v-if="message.phone" class="text-xs text-base-content/60 font-medium">{{ message.phone }}</p>
                                            </div>
                                        </div>
                                        <div class="text-[10px] font-black uppercase tracking-widest text-base-content/30 bg-base-200 px-3 py-1.5 rounded-lg">
                                            {{ formatDate(message.created_at) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Subject & Message -->
                        <div class="px-8 py-6 border-b border-base-300/50">
                            <h3 class="text-sm font-black text-base-content tracking-tight mb-3 flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full" :style="{ backgroundColor: primaryColor }"></span>
                                {{ message.subject }}
                            </h3>
                            <div class="bg-base-200/50 rounded-2xl p-5 border border-base-300/50 shadow-inner">
                                <p class="text-sm text-base-content/80 whitespace-pre-wrap leading-relaxed font-medium">{{ message.message }}</p>
                            </div>
                        </div>

                        <!-- Attachments -->
                        <div v-if="message.attachments && message.attachments.length > 0" class="px-8 py-6 border-b border-base-300/50">
                            <h4 class="text-[10px] font-black text-base-content/40 uppercase tracking-widest mb-4 flex items-center gap-2">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                </svg>
                                Attachments ({{ message.attachments.length }})
                            </h4>
                            <div class="flex flex-wrap gap-3">
                                <a 
                                    v-for="(attachment, index) in message.attachments" 
                                    :key="index"
                                    :href="attachment.url"
                                    target="_blank"
                                    class="inline-flex items-center gap-3 px-4 py-2 bg-base-100 border border-base-300 rounded-xl text-sm font-bold text-base-content/70 hover:bg-base-200 hover:text-base-content hover:border-primary/30 transition-all duration-300 group shadow-sm"
                                >
                                    <div class="w-8 h-8 rounded-lg bg-base-200 flex items-center justify-center group-hover:bg-primary/10 group-hover:text-primary transition-colors">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                        </svg>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="truncate max-w-[150px]">{{ attachment.name }}</span>
                                        <span class="text-[10px] text-base-content/40 font-black uppercase tracking-wider">{{ formatFileSize(attachment.size) }} MB</span>
                                    </div>
                                </a>
                            </div>
                        </div>

                        <!-- Reply Form -->
                        <div class="px-8 py-6 bg-base-200/20">
                            <h4 class="text-sm font-black text-base-content mb-4 tracking-tight flex items-center gap-2">
                                <svg class="w-4 h-4 text-base-content/50" :style="{ color: primaryColor }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                </svg>
                                Compose Reply
                            </h4>
                            <div class="relative">
                                <textarea
                                    v-model="replyText"
                                    rows="5"
                                    class="textarea textarea-bordered w-full bg-base-100 text-base-content focus:outline-none focus:border-primary resize-none placeholder:text-base-content/30 font-medium leading-relaxed rounded-2xl p-4 shadow-sm"
                                    placeholder="Type your response here... Pressing 'Send Reply' will email the user directly."
                                ></textarea>
                            </div>

                            <!-- File Attachments -->
                            <div class="mt-4">
                                <label class="btn btn-ghost border border-base-300 bg-base-100 hover:bg-base-200 hover:border-base-300 rounded-xl text-xs font-black uppercase tracking-widest px-4 h-10 inline-flex items-center gap-2 cursor-pointer shadow-sm transition-all duration-300">
                                    <svg class="w-4 h-4 text-base-content/50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                    </svg>
                                    Add Attachments
                                    <input 
                                        type="file" 
                                        multiple 
                                        class="hidden" 
                                        @change="handleFileUpload"
                                        accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.txt"
                                    >
                                </label>

                                <!-- Selected Files -->
                                <div v-if="selectedFiles.length > 0" class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div 
                                        v-for="(file, index) in selectedFiles" 
                                        :key="index"
                                        class="flex items-center justify-between px-3 py-2 bg-base-100 border border-base-300 rounded-xl shadow-sm"
                                    >
                                        <div class="flex items-center gap-3 truncate">
                                            <div class="w-8 h-8 rounded-lg bg-base-200 flex items-center justify-center flex-shrink-0">
                                                <svg class="w-4 h-4 text-base-content/40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                            </div>
                                            <div class="flex flex-col min-w-0">
                                                <span class="truncate text-xs font-bold text-base-content">{{ file.name }}</span>
                                                <span class="text-[9px] font-black uppercase tracking-wider text-base-content/40">{{ formatFileSize(file.size) }} MB</span>
                                            </div>
                                        </div>
                                        <button @click="removeAttachment(index)" class="btn btn-ghost btn-circle btn-xs text-error/70 hover:text-error hover:bg-error/10 ml-2" title="Remove attachment">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <p v-if="attachmentError" class="text-xs font-bold text-error mt-2 flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    {{ attachmentError }}
                                </p>
                            </div>

                            <!-- Primary Action -->
                            <div class="flex justify-end mt-6">
                                <button
                                    @click="handleSendReply"
                                    :disabled="!replyText.trim() || isSendingReply"
                                    class="btn font-black shadow-sm min-w-[140px] rounded-xl text-white border-transparent hover:brightness-110 disabled:opacity-50"
                                    :style="{ backgroundColor: primaryColor }"
                                >
                                    <span v-if="isSendingReply" class="loading loading-spinner loading-xs mr-2"></span>
                                    <svg v-else class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.125A59.769 59.769 0 0121.485 12 59.768 59.768 0 013.27 20.875L5.999 12Zm0 0h7.5" />
                                    </svg>
                                    {{ isSendingReply ? 'Sending...' : 'Send Reply' }}
                                </button>
                            </div>
                        </div>

                        <!-- Secondary Actions Footer -->
                        <div class="flex items-center justify-between px-8 py-4 border-t border-base-300 bg-base-200/50">
                            <button
                                @click="handleDelete"
                                class="btn btn-ghost btn-sm text-error hover:bg-error hover:text-error-content rounded-lg font-black text-xs uppercase tracking-widest transition-colors duration-300"
                            >
                                Delete Ticket
                            </button>
                            <div class="flex gap-2">
                                <button
                                    v-if="message.status !== 'replied'"
                                    @click="handleStatusUpdate('replied')"
                                    class="btn btn-ghost btn-sm bg-base-100 border border-base-300 hover:bg-base-200 text-base-content/70 rounded-lg font-black text-[10px] uppercase tracking-widest transition-all duration-300 shadow-sm"
                                    title="Mark as replied without sending an email"
                                >
                                    Mark Replied (Manual)
                                </button>
                                <button
                                    v-if="message.status !== 'archived'"
                                    @click="handleStatusUpdate('archived')"
                                    class="btn btn-ghost btn-sm bg-base-100 border border-warning/30 text-warning hover:bg-warning hover:text-warning-content rounded-lg font-black text-[10px] uppercase tracking-widest transition-all duration-300 shadow-sm"
                                >
                                    Archive Ticket
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>
