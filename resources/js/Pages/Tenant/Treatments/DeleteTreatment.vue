<script setup>
import { brandingState } from '@/States/brandingState';
import { useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    show: Boolean,
    treatment: Object,
});

const emit = defineEmits(['close']);

const primaryColor = computed(() => brandingState.primary_color);

const form = useForm({});

const submit = () => {
    form.delete(route('treatments.destroy', props.treatment.id), {
        preserveScroll: true,
        onSuccess: () => {
            emit('close');
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Treatment record deleted successfully',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
            });
        },
    });
};

const close = () => {
    emit('close');
};
</script>

<template>
    <Modal :show="show" @close="close" maxWidth="md">
        <div class="p-6">
            <h2 class="text-lg font-black text-base-content uppercase tracking-tight">
                Delete Treatment Record
            </h2>

            <p class="mt-4 text-sm text-base-content/60 font-medium">
                Are you sure you want to delete this treatment record for <span class="font-bold text-base-content">{{ treatment?.patient?.first_name }} {{ treatment?.patient?.last_name }}</span>? 
            </p>
            <p class="mt-2 text-xs text-error font-bold uppercase tracking-widest">
                This action cannot be undone.
            </p>

            <div class="mt-8 flex justify-end space-x-3">
                <SecondaryButton @click="close" class="rounded-xl px-6 h-11">
                    Cancel
                </SecondaryButton>

                <DangerButton
                    @click="submit"
                    class="rounded-xl px-6 h-11 shadow-lg shadow-error/20 font-black text-xs uppercase tracking-widest"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Delete Record
                </DangerButton>
            </div>
        </div>
    </Modal>
</template>
