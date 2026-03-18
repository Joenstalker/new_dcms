<script setup>
import { ref, watch } from 'vue';

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
                <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-hidden">
                    <!-- Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <h2 class="text-lg font-semibold text-gray-900">Message Details</h2>
                        <button @click="handleClose" class="text-gray-400 hover:text-gray-600 transition-colors">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Content -->
                    <div v-if="message" class="overflow-y-auto max-h-[calc(90vh-200px)]">
                        <!-- Sender Info -->
                        <div class="px-6 py-4 border-b border-gray-100">
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0 h-12 w-12 rounded-full bg-teal-100 flex items-center justify-center">
                                    <span class="text-lg font-semibold text-teal-700">
                                        {{ message.name.charAt(0).toUpperCase() }}
                                    </span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-semibold text-gray-900">{{ message.name }}</p>
                                            <p class="text-xs text-gray-500">{{ message.email }}</p>
                                            <p v-if="message.phone" class="text-xs text-gray-500">{{ message.phone }}</p>
                                        </div>
                                        <div class="text-xs text-gray-400">
                                            {{ formatDate(message.created_at) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Subject & Message -->
                        <div class="px-6 py-4 border-b border-gray-100">
                            <h3 class="text-sm font-semibold text-gray-700 mb-2">Subject: {{ message.subject }}</h3>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ message.message }}</p>
                            </div>
                        </div>

                        <!-- Attachments -->
                        <div v-if="message.attachments && message.attachments.length > 0" class="px-6 py-4 border-b border-gray-100">
                            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Attachments</h4>
                            <div class="flex flex-wrap gap-2">
                                <a 
                                    v-for="(attachment, index) in message.attachments" 
                                    :key="index"
                                    :href="attachment.url"
                                    target="_blank"
                                    class="inline-flex items-center gap-2 px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm text-gray-700 hover:bg-gray-50 transition-colors"
                                >
                                    <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                    </svg>
                                    <span class="truncate max-w-[150px]">{{ attachment.name }}</span>
                                    <span class="text-xs text-gray-400">({{ formatFileSize(attachment.size) }}MB)</span>
                                </a>
                            </div>
                        </div>

                        <!-- Reply Form -->
                        <div class="px-6 py-4">
                            <h4 class="text-sm font-semibold text-gray-700 mb-3">Send Reply</h4>
                            <textarea
                                v-model="replyText"
                                rows="4"
                                class="w-full px-4 py-3 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent resize-none"
                                placeholder="Type your reply here..."
                            ></textarea>

                            <!-- File Attachments -->
                            <div class="mt-3">
                                <label class="inline-flex items-center gap-2 px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm text-gray-600 hover:bg-gray-50 cursor-pointer transition-colors">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                    </svg>
                                    Attach Files
                                    <input 
                                        type="file" 
                                        multiple 
                                        class="hidden" 
                                        @change="handleFileUpload"
                                        accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.txt"
                                    >
                                </label>

                                <!-- Selected Files -->
                                <div v-if="selectedFiles.length > 0" class="mt-3 space-y-2">
                                    <div 
                                        v-for="(file, index) in selectedFiles" 
                                        :key="index"
                                        class="flex items-center justify-between px-3 py-2 bg-gray-50 rounded-lg"
                                    >
                                        <div class="flex items-center gap-2 truncate">
                                            <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            <span class="truncate text-sm">{{ file.name }}</span>
                                            <span class="text-xs text-gray-400">({{ formatFileSize(file.size) }}MB)</span>
                                        </div>
                                        <button @click="removeAttachment(index)" class="text-red-400 hover:text-red-600 ml-2" title="Remove attachment">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <p v-if="attachmentError" class="text-xs text-red-500 mt-1">{{ attachmentError }}</p>
                            </div>

                            <div class="flex justify-end mt-3 border-t border-gray-200/50 pt-3">
                                <button
                                    @click="handleSendReply"
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
                        <div class="flex gap-2 justify-end px-6 pb-4 pt-2" :class="{'border-t border-gray-200': message.status !== 'replied'}">
                            <button
                                v-if="message.status !== 'replied'"
                                @click="handleStatusUpdate('replied')"
                                class="px-3 py-1.5 text-xs font-semibold bg-white border border-gray-300 text-gray-700 rounded hover:bg-gray-50 transition"
                                title="Mark as replied without sending an email"
                            >
                                Mark as Replied (Manual)
                            </button>
                            <button
                                v-if="message.status !== 'archived'"
                                @click="handleStatusUpdate('archived')"
                                class="px-3 py-1.5 text-xs font-semibold bg-white border border-yellow-400 text-yellow-600 rounded hover:bg-yellow-50 transition"
                            >
                                Archive
                            </button>
                            <button
                                @click="handleDelete"
                                class="px-3 py-1.5 text-xs font-semibold bg-white border border-red-400 text-red-600 rounded hover:bg-red-50 transition"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>
