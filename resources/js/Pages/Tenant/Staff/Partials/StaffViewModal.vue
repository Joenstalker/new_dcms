<script setup>
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

defineProps({
    show: {
        type: Boolean,
        default: false
    },
    staff: {
        type: Object,
        default: null
    }
});

const emit = defineEmits(['close']);

const handleClose = () => {
    emit('close');
};
</script>

<template>
    <!-- View Staff Modal -->
    <Modal :show="show" @close="handleClose" maxWidth="md">
        <div class="p-6">
            <div class="flex justify-between items-start mb-6">
                <h3 class="text-lg font-bold text-gray-900">Staff Details</h3>
                <button @click="handleClose" class="text-gray-400 hover:text-gray-500">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="space-y-4">
                <div class="grid grid-cols-3 gap-4">
                    <span class="text-sm font-semibold text-gray-500">Name:</span>
                    <span class="col-span-2 text-sm text-gray-900">{{ staff?.name }}</span>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <span class="text-sm font-semibold text-gray-500">Email:</span>
                    <span class="col-span-2 text-sm text-gray-900">{{ staff?.email }}</span>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <span class="text-sm font-semibold text-gray-500">Role:</span>
                    <span class="col-span-2 text-sm">
                        <span class="px-2 py-0.5 rounded-full bg-blue-100 text-blue-800 text-xs font-semibold">
                            {{ staff?.roles?.[0]?.name || 'Staff' }}
                        </span>
                    </span>
                </div>
            </div>
            <div class="mt-8 flex justify-end">
                <SecondaryButton @click="handleClose">Close</SecondaryButton>
            </div>
        </div>
    </Modal>
</template>
