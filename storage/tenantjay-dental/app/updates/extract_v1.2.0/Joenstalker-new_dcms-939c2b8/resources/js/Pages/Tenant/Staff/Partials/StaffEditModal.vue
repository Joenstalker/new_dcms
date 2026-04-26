<script setup>
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

defineProps({
    show: {
        type: Boolean,
        default: false
    },
    form: {
        type: Object,
        required: true
    }
});

const emit = defineEmits(['close', 'submit']);

const handleClose = () => {
    emit('close');
};

const handleSubmit = () => {
    emit('submit');
};
</script>

<template>
    <!-- Edit Staff Modal -->
    <Modal :show="show" @close="handleClose" maxWidth="md">
        <div class="p-6">
            <div class="flex justify-between items-start mb-6">
                <h3 class="text-lg font-bold text-gray-900">Edit Staff Member</h3>
                <button @click="handleClose" class="text-gray-400 hover:text-gray-500">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <form @submit.prevent="handleSubmit">
                <div>
                    <InputLabel for="edit_name" value="Name" />
                    <TextInput
                        id="edit_name"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.name"
                        required
                        autofocus
                    />
                    <InputError class="mt-2" :message="form.errors.name" />
                </div>

                <div class="mt-4">
                    <InputLabel for="edit_email" value="Email" />
                    <TextInput
                        id="edit_email"
                        type="email"
                        class="mt-1 block w-full"
                        v-model="form.email"
                        required
                    />
                    <InputError class="mt-2" :message="form.errors.email" />
                </div>

                <div class="mt-4">
                    <InputLabel for="edit_role" value="Role" />
                    <select 
                        id="edit_role"
                        v-model="form.role"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        required
                    >
                        <option value="Dentist">Dentist</option>
                        <option value="Assistant">Assistant</option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.role" />
                </div>

                <div class="mt-8 flex justify-end space-x-3">
                    <SecondaryButton @click="handleClose">Cancel</SecondaryButton>
                    <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        Update Staff
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>
</template>
