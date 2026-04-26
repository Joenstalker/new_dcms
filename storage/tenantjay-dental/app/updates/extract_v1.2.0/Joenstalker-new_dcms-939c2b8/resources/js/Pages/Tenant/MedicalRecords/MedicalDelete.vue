<script setup>
import Modal from '@/Components/Modal.vue';
import { useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

const props = defineProps({
    show: Boolean,
    medicalRecord: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close']);

const form = useForm({});

const close = () => {
    emit('close');
};

const submit = () => {
    if (!props.medicalRecord) {
        return;
    }

    form.delete(route('medical-records.destroy', props.medicalRecord.id), {
        preserveScroll: true,
        onSuccess: () => {
            close();
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Medical record item deleted successfully',
                showConfirmButton: false,
                timer: 2500,
            });
        },
    });
};
</script>

<template>
    <Modal :show="show" @close="close" maxWidth="md">
        <div class="p-6" v-if="medicalRecord">
            <h2 class="text-lg font-black text-base-content uppercase tracking-tight">Delete Medical Record Item</h2>
            <p class="mt-4 text-sm text-base-content/60 font-medium">
                Are you sure you want to delete <span class="font-bold text-base-content">{{ medicalRecord.name }}</span>?
            </p>
            <p class="mt-2 text-xs text-error font-bold uppercase tracking-widest">This action cannot be undone.</p>
            <div class="mt-8 flex justify-end gap-3">
                <button type="button" @click="close" class="btn rounded-xl px-6">Cancel</button>
                <button
                    type="button"
                    @click="submit"
                    class="btn btn-error rounded-xl px-6 text-white font-black text-xs uppercase tracking-widest"
                    :disabled="form.processing"
                >
                    Delete
                </button>
            </div>
        </div>
    </Modal>
</template>
