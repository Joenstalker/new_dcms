<script setup>
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    editingService: {
        type: Object,
        default: null
    },
    isOwnerOrDentist: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['close', 'submit']);

const form = useForm({
    name: '',
    description: '',
    price: '',
});

// Watch for show changes to update form
watch(() => props.show, (newVal) => {
    if (newVal) {
        if (props.editingService) {
            form.name = props.editingService.name;
            form.description = props.editingService.description;
            form.price = props.editingService.price;
        } else {
            form.reset();
        }
    }
});

const handleSubmit = () => {
    emit('submit', { form: form, editingService: props.editingService });
};

const handleClose = () => {
    form.reset();
    emit('close');
};
</script>

<template>
    <!-- Create/Edit Modal using daisyUI -->
    <div class="modal" :class="{'modal-open': show}">
        <div class="modal-box rounded-3xl max-w-lg">
            <h3 class="font-bold text-2xl mb-6">{{ editingService ? 'Edit Service' : 'New Service' }}</h3>
            <form @submit.prevent="handleSubmit" class="space-y-6">
                <!-- Service Name -->
                <div class="form-control w-full">
                    <label class="label">
                        <span class="label-text font-bold text-slate-600">Service Name</span>
                    </label>
                    <input v-model="form.name" type="text" placeholder="Enter service name" 
                        class="input input-bordered w-full rounded-xl focus:border-primary transition-all"
                        :class="{ 'input-error': form.errors.name }" />
                    <label v-if="form.errors.name" class="label">
                        <span class="label-text-alt text-error font-medium">{{ form.errors.name }}</span>
                    </label>
                </div>

                <div class="form-control w-full">
                    <label class="label font-semibold text-slate-600">Price (₱)</label>
                    <input v-model="form.price" type="number" step="0.01" placeholder="0.00" class="input input-bordered w-full rounded-xl focus:ring-2 focus:ring-primary shadow-sm" required />
                    <label v-if="form.errors.price" class="label text-error text-xs">{{ form.errors.price }}</label>
                </div>

                <div class="form-control w-full">
                    <label class="label font-semibold text-slate-600">Description</label>
                    <textarea v-model="form.description" class="textarea textarea-bordered h-24 rounded-xl focus:ring-2 focus:ring-primary shadow-sm" placeholder="Details about the service..."></textarea>
                    <label v-if="form.errors.description" class="label text-error text-xs">{{ form.errors.description }}</label>
                </div>

                <div class="modal-action mt-8 flex justify-end gap-2">
                    <button type="button" @click="handleClose" class="btn btn-ghost rounded-xl">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-xl px-8 shadow-md" :disabled="form.processing">
                        {{ editingService ? 'UPDATE' : 'Create Service' }}
                    </button>
                </div>
            </form>
        </div>
        <div class="modal-backdrop bg-slate-900/40" @click="handleClose"></div>
    </div>
</template>
