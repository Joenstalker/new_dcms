<script setup>
import { brandingState } from '@/States/brandingState';
import Modal from '@/Components/Modal.vue';
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import Swal from 'sweetalert2';

const props = defineProps({
    show: Boolean,
    medicalRecord: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close']);

const primaryColor = computed(() => brandingState.primary_color);

const form = useForm({
    name: '',
    description: '',
    is_active: true,
});

watch(() => props.medicalRecord, (record) => {
    if (!record) {
        return;
    }

    form.name = record.name || '';
    form.description = record.description || '';
    form.is_active = Boolean(record.is_active);
}, { immediate: true });

const close = () => {
    form.clearErrors();
    emit('close');
};

const submit = () => {
    if (!props.medicalRecord) {
        return;
    }

    form.put(route('medical-records.update', props.medicalRecord.id), {
        preserveScroll: true,
        onSuccess: () => {
            close();
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Medical record item updated successfully',
                showConfirmButton: false,
                timer: 2500,
            });
        },
    });
};
</script>

<template>
    <Modal :show="show" @close="close">
        <div class="p-6" v-if="medicalRecord">
            <h2 class="text-xl font-black text-base-content uppercase tracking-tight mb-6">Edit Medical Record Item</h2>
            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Name</label>
                    <input v-model="form.name" type="text" class="w-full rounded-xl border-base-300 bg-base-100 focus:ring-primary" required>
                    <p v-if="form.errors.name" class="mt-1 text-xs font-bold text-error">{{ form.errors.name }}</p>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Description (Optional)</label>
                    <textarea v-model="form.description" rows="3" class="w-full rounded-xl border-base-300 bg-base-100 focus:ring-primary"></textarea>
                    <p v-if="form.errors.description" class="mt-1 text-xs font-bold text-error">{{ form.errors.description }}</p>
                </div>
                <div>
                    <label class="flex items-center gap-2 mt-1">
                        <input v-model="form.is_active" type="checkbox" class="checkbox checkbox-sm">
                        <span class="text-sm font-bold text-base-content/70">Active</span>
                    </label>
                </div>
                <div class="flex justify-end gap-3 mt-8">
                    <button type="button" @click="close" class="btn rounded-xl px-6">Cancel</button>
                    <button type="submit" class="btn rounded-xl px-8 border-0 text-white font-black text-xs uppercase tracking-widest" :style="{ backgroundColor: primaryColor }" :disabled="form.processing">
                        Update
                    </button>
                </div>
            </form>
        </div>
    </Modal>
</template>
