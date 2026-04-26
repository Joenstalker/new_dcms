<script setup>
import { brandingState } from '@/States/brandingState';
import Modal from '@/Components/Modal.vue';
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import Swal from 'sweetalert2';

const props = defineProps({
    show: Boolean,
});

const emit = defineEmits(['close']);

const primaryColor = computed(() => brandingState.primary_color);

const form = useForm({
    name: '',
    description: '',
    is_active: true,
});

const close = () => {
    form.reset();
    form.clearErrors();
    form.is_active = true;
    emit('close');
};

const submit = () => {
    form.post(route('medical-records.store'), {
        preserveScroll: true,
        onSuccess: () => {
            close();
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Medical record item created successfully',
                showConfirmButton: false,
                timer: 2500,
            });
        },
    });
};

watch(() => props.show, (value) => {
    if (!value) {
        form.reset();
        form.clearErrors();
        form.is_active = true;
    }
});
</script>

<template>
    <Modal :show="show" @close="close">
        <div class="p-6">
            <h2 class="text-xl font-black text-base-content uppercase tracking-tight mb-6">Create Medical Record Item</h2>
            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Name</label>
                    <input v-model="form.name" type="text" class="w-full rounded-xl border-base-300 bg-base-100 focus:ring-primary" placeholder="e.g. Bleeding Disorder" required>
                    <p v-if="form.errors.name" class="mt-1 text-xs font-bold text-error">{{ form.errors.name }}</p>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Description (Optional)</label>
                    <textarea v-model="form.description" rows="3" class="w-full rounded-xl border-base-300 bg-base-100 focus:ring-primary" placeholder="Short note about why this item is clinically relevant."></textarea>
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
                        Create
                    </button>
                </div>
            </form>
        </div>
    </Modal>
</template>
