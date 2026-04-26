<script setup>
import { computed } from 'vue';
import Modal from '@/Components/Modal.vue';
import { useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

const props = defineProps({
    show: Boolean,
    service: {
        type: Object,
        default: null,
    },
    selectedIds: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close', 'deleted']);

const form = useForm({
    ids: [],
    confirmation: '',
});

const isConfirmed = computed(() => form.confirmation.toLowerCase() === 'delete');

const close = () => {
    form.confirmation = '';
    emit('close');
};

const submit = () => {
    if (props.selectedIds.length > 0) {
        form.ids = props.selectedIds;
        form.delete(route('services.bulk-destroy'), {
            preserveScroll: true,
            onSuccess: () => {
                form.confirmation = '';
                emit('deleted');
                close();
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: `${props.selectedIds.length} services deleted successfully`,
                    showConfirmButton: false,
                    timer: 2500,
                });
            },
        });
        return;
    }

    if (!props.service) {
        return;
    }

    form.delete(route('services.destroy', props.service.id), {
        preserveScroll: true,
        onSuccess: () => {
            form.confirmation = '';
            close();
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Service deleted successfully',
                showConfirmButton: false,
                timer: 2500,
            });
        },
    });
};
</script>

<template>
    <Modal :show="show" @close="close" maxWidth="md">
        <div class="p-6" v-if="service || selectedIds.length > 0">
            <h2 class="text-lg font-black text-base-content uppercase tracking-tight">
                {{ selectedIds.length > 0 ? `Delete ${selectedIds.length} Services` : 'Delete Service' }}
            </h2>
            <p class="mt-4 text-sm text-base-content/60 font-medium">
                <template v-if="selectedIds.length > 0">
                    Are you sure you want to delete these <span class="font-bold text-base-content">{{ selectedIds.length }}</span> services?
                </template>
                <template v-else>
                    Are you sure you want to delete <span class="font-bold text-base-content">{{ service.name }}</span>?
                </template>
            </p>
            <p class="mt-2 text-xs text-error font-bold uppercase tracking-widest">This action cannot be undone.</p>

            <div class="mt-6">
                <label class="block text-xs font-black text-base-content/40 uppercase tracking-widest mb-2">
                    Type <span class="text-error">Delete</span> to confirm
                </label>
                <input
                    v-model="form.confirmation"
                    type="text"
                    class="w-full rounded-xl border-base-300 bg-base-100 focus:ring-error focus:border-error"
                    placeholder="Type Delete here..."
                />
            </div>

            <div class="mt-8 flex justify-end gap-3">
                <button type="button" @click="close" class="btn rounded-xl px-6">Cancel</button>
                <button
                    type="button"
                    @click="submit"
                    class="btn btn-error rounded-xl px-6 text-white font-black text-xs uppercase tracking-widest"
                    :disabled="form.processing || !isConfirmed"
                >
                    Delete
                </button>
            </div>
        </div>
    </Modal>
</template>
