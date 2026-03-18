<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Swal from 'sweetalert2';

import SupportStats from './Partials/SupportStats.vue';
import SupportFilters from './Partials/SupportFilters.vue';
import MessagesList from './Partials/MessagesList.vue';
import MessageDetailModal from './Partials/MessageDetailModal.vue';

const props = defineProps({
    messages: Object,
    unreadCount: Number,
    currentFilter: String,
    stats: Object,
    counts: Object,
});

// View message detail
const selectedMessage = ref(null);
const showDetail = ref(false);
const isSendingReply = ref(false);
const attachmentError = ref('');

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

const handleSendReply = ({ text, files }) => {
    if (!text.trim() || isSendingReply.value) return;

    isSendingReply.value = true;
    
    router.post(route('admin.support.reply', selectedMessage.value.id), {
        reply: text.trim(),
        attachments: files
    }, {
        preserveScroll: true,
        onSuccess: () => {
            if (selectedMessage.value) {
                selectedMessage.value.status = 'replied';
            }
            closeDetail();
        },
        onFinish: () => {
            isSendingReply.value = false;
        }
    });
};

const handleStatusUpdate = (status) => {
    router.put(route('admin.support.updateStatus', selectedMessage.value.id), { status }, {
        preserveScroll: true,
        onSuccess: () => {
            if (selectedMessage.value) {
                selectedMessage.value.status = status;
            }
        }
    });
};

const handleDelete = () => {
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
            router.delete(route('admin.support.destroy', selectedMessage.value.id), {
                preserveScroll: true,
                onSuccess: () => {
                    closeDetail();
                }
            });
        }
    });
};

const closeDetail = () => {
    showDetail.value = false;
    selectedMessage.value = null;
    attachmentError.value = '';
};

const handleAttachmentError = (error) => {
    attachmentError.value = error;
};
</script>

<template>
    <AdminLayout>
        <template #header>
            <h1 class="text-xl font-bold text-gray-900 line-clamp-1">Support & Tickets</h1>
        </template>

        <div class="max-w-7xl mx-auto space-y-6">
            <!-- Stats Cards -->
            <SupportStats :stats="stats" />

            <!-- Filters -->
            <SupportFilters 
                :active-filter="currentFilter" 
                :counts="counts"
                @filter-change="setFilter"
            />

            <!-- Messages List -->
            <MessagesList 
                :messages="messages"
                @select-message="viewMessage"
            />
        </div>

        <!-- Message Detail Modal -->
        <MessageDetailModal
            :show="showDetail"
            :message="selectedMessage"
            :is-sending-reply="isSendingReply"
            :attachment-error="attachmentError"
            @close="closeDetail"
            @send-reply="handleSendReply"
            @update-status="handleStatusUpdate"
            @delete="handleDelete"
            @remove-attachment="(index) => {}"
        />
    </AdminLayout>
</template>
