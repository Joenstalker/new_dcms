<script setup>
import Modal from '@/Components/Modal.vue';

defineProps({
    show: Boolean,
    medicalRecord: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close']);

const close = () => {
    emit('close');
};
</script>

<template>
    <Modal :show="show" @close="close">
        <div class="p-6" v-if="medicalRecord">
            <h2 class="text-xl font-black text-base-content uppercase tracking-tight mb-6">Medical Record Details</h2>
            <div class="space-y-4">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-widest text-base-content/40">Name</p>
                    <p class="text-sm font-black text-base-content">{{ medicalRecord.name }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-black uppercase tracking-widest text-base-content/40">Description</p>
                    <p class="text-sm text-base-content/70">{{ medicalRecord.description || 'No description provided.' }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-black uppercase tracking-widest text-base-content/40">Status</p>
                    <p class="text-sm font-bold" :class="medicalRecord.is_active ? 'text-green-700' : 'text-gray-500'">
                        {{ medicalRecord.is_active ? 'Active' : 'Inactive' }}
                    </p>
                </div>
            </div>
            <div class="flex justify-end mt-8">
                <button type="button" @click="close" class="btn rounded-xl px-8">Close</button>
            </div>
        </div>
    </Modal>
</template>
