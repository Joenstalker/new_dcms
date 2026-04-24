<script setup>
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

const props = defineProps({
    isOpen: Boolean,
    branch: Object,
});

const emit = defineEmits(['close']);

const form = useForm({
    name: '',
    address: '',
    phone: '',
    email: '',
    is_primary: false,
    is_active: true,
});

watch(() => props.isOpen, (newVal) => {
    if (newVal) {
        if (props.branch) {
            form.name = props.branch.name;
            form.address = props.branch.address || '';
            form.phone = props.branch.phone || '';
            form.email = props.branch.email || '';
            form.is_primary = props.branch.is_primary;
            form.is_active = props.branch.is_active;
        } else {
            form.reset();
        }
        form.clearErrors();
    }
});

const submit = () => {
    if (props.branch) {
        form.put(route('branches.update', props.branch.id), {
            onSuccess: () => {
                emit('close');
            }
        });
    } else {
        form.post(route('branches.store'), {
            onSuccess: () => {
                emit('close');
            }
        });
    }
};
</script>

<template>
    <dialog class="modal" :class="{ 'modal-open': isOpen }">
        <div class="modal-box max-w-md rounded-2xl">
            <h3 class="font-black text-lg text-base-content mb-6">
                {{ branch ? 'Edit Branch' : 'Add New Branch' }}
            </h3>

            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label class="label"><span class="label-text font-bold uppercase tracking-wider text-[10px]">Branch Name</span></label>
                    <input type="text" v-model="form.name" class="input input-sm input-bordered w-full" placeholder="e.g. Main Clinic" required />
                    <div v-if="form.errors.name" class="text-error text-xs mt-1">{{ form.errors.name }}</div>
                </div>

                <div>
                    <label class="label"><span class="label-text font-bold uppercase tracking-wider text-[10px]">Address</span></label>
                    <textarea v-model="form.address" class="textarea textarea-sm textarea-bordered w-full" rows="2" placeholder="Complete address"></textarea>
                    <div v-if="form.errors.address" class="text-error text-xs mt-1">{{ form.errors.address }}</div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label"><span class="label-text font-bold uppercase tracking-wider text-[10px]">Phone Number</span></label>
                        <input type="text" v-model="form.phone" class="input input-sm input-bordered w-full" placeholder="(555) 123-4567" />
                        <div v-if="form.errors.phone" class="text-error text-xs mt-1">{{ form.errors.phone }}</div>
                    </div>
                    <div>
                        <label class="label"><span class="label-text font-bold uppercase tracking-wider text-[10px]">Email Address</span></label>
                        <input type="email" v-model="form.email" class="input input-sm input-bordered w-full" placeholder="branch@example.com" />
                        <div v-if="form.errors.email" class="text-error text-xs mt-1">{{ form.errors.email }}</div>
                    </div>
                </div>

                <div class="bg-base-50 p-4 border border-base-200 rounded-xl mt-6 space-y-3">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" class="toggle toggle-primary toggle-sm" v-model="form.is_primary" />
                        <div>
                            <div class="text-sm font-bold text-base-content">Primary Branch</div>
                            <div class="text-xs text-base-content/50">Set this as the main clinic location.</div>
                        </div>
                    </label>

                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" class="toggle toggle-success toggle-sm" v-model="form.is_active" />
                        <div>
                            <div class="text-sm font-bold text-base-content">Active Branch</div>
                            <div class="text-xs text-base-content/50">Allow bookings and staff assignments.</div>
                        </div>
                    </label>
                </div>

                <div class="modal-action mt-6">
                    <button type="button" class="btn btn-ghost btn-sm" @click="$emit('close')">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Save Branch' }}
                    </button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop lg:bg-base-300/50" @click="$emit('close')">
            <button>close</button>
        </form>
    </dialog>
</template>
