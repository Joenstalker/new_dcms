<script setup>
import { ref, computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    show: Boolean,
});

const emit = defineEmits(['close']);

const user = computed(() => usePage().props.auth.user);

const nameForm = useForm({
    name: user.value.name,
    email: user.value.email,
});

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updateName = () => {
    nameForm.patch(route('profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            // keep modal open or close? User usually wants to see success
        },
    });
};

const updatePassword = () => {
    passwordForm.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => {
            passwordForm.reset();
        },
        onError: () => {
            if (passwordForm.errors.password) {
                passwordForm.reset('password', 'password_confirmation');
            }
            if (passwordForm.errors.current_password) {
                passwordForm.reset('current_password');
            }
        },
    });
};

const close = () => {
    nameForm.reset();
    passwordForm.reset();
    emit('close');
};
</script>

<template>
    <Modal :show="show" @close="close" max-width="lg">
        <div class="p-6 overflow-y-auto max-h-[85vh] custom-scrollbar">
            <h2 class="text-xl font-black text-base-content uppercase tracking-widest mb-6 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6 mr-3 text-primary">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                </svg>
                Account Settings
            </h2>

            <div class="space-y-8">
                <!-- Profile Information -->
                <section class="bg-base-200/50 p-5 rounded-2xl border border-base-300/50">
                    <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-base-content/30 mb-4">Basic Information</h3>
                    <form @submit.prevent="updateName" class="space-y-4">
                        <div>
                            <InputLabel for="name" value="Full Name" class="text-[10px] uppercase font-black opacity-40 ml-1 mb-1" />
                            <TextInput
                                id="name"
                                type="text"
                                class="mt-1 block w-full rounded-xl border-base-300 focus:ring-primary h-11"
                                v-model="nameForm.name"
                                required
                                autofocus
                                autocomplete="name"
                            />
                            <InputError class="mt-2" :message="nameForm.errors.name" />
                        </div>

                        <div>
                            <InputLabel for="email" value="Email Address (Read-only)" class="text-[10px] uppercase font-black opacity-40 ml-1 mb-1" />
                            <TextInput
                                id="email"
                                type="email"
                                class="mt-1 block w-full rounded-xl bg-base-300/50 border-base-300 h-11 cursor-not-allowed opacity-50"
                                v-model="nameForm.email"
                                disabled
                                readonly
                            />
                        </div>

                        <div class="flex items-center gap-4 pt-2">
                            <PrimaryButton :disabled="nameForm.processing" class="rounded-xl px-8 h-10">
                                Save Name
                            </PrimaryButton>
                            <Transition
                                enter-active-class="transition ease-in-out"
                                enter-from-class="opacity-0"
                                leave-active-class="transition ease-in-out"
                                leave-to-class="opacity-0"
                            >
                                <p v-if="nameForm.recentlySuccessful" class="text-[10px] font-bold uppercase tracking-widest text-success">Saved.</p>
                            </Transition>
                        </div>
                    </form>
                </section>

                <!-- Update Password -->
                <section class="bg-base-200/50 p-5 rounded-2xl border border-base-300/50">
                    <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-base-content/30 mb-4">Security</h3>
                    <form @submit.prevent="updatePassword" class="space-y-4">
                        <div>
                            <InputLabel for="current_password" value="Current Password" class="text-[10px] uppercase font-black opacity-40 ml-1 mb-1" />
                            <TextInput
                                id="current_password"
                                ref="currentPasswordInput"
                                v-model="passwordForm.current_password"
                                type="password"
                                class="mt-1 block w-full rounded-xl border-base-300 focus:ring-primary h-11"
                                autocomplete="current-password"
                            />
                            <InputError :message="passwordForm.errors.current_password" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="password" value="New Password" class="text-[10px] uppercase font-black opacity-40 ml-1 mb-1" />
                            <TextInput
                                id="password"
                                ref="passwordInput"
                                v-model="passwordForm.password"
                                type="password"
                                class="mt-1 block w-full rounded-xl border-base-300 focus:ring-primary h-11"
                                autocomplete="new-password"
                            />
                            <InputError :message="passwordForm.errors.password" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="password_confirmation" value="Confirm New Password" class="text-[10px] uppercase font-black opacity-40 ml-1 mb-1" />
                            <TextInput
                                id="password_confirmation"
                                v-model="passwordForm.password_confirmation"
                                type="password"
                                class="mt-1 block w-full rounded-xl border-base-300 focus:ring-primary h-11"
                                autocomplete="new-password"
                            />
                            <InputError :message="passwordForm.errors.password_confirmation" class="mt-2" />
                        </div>

                        <div class="flex items-center gap-4 pt-2">
                            <PrimaryButton :disabled="passwordForm.processing" class="rounded-xl px-8 h-10">
                                Update Password
                            </PrimaryButton>
                            <Transition
                                enter-active-class="transition ease-in-out"
                                enter-from-class="opacity-0"
                                leave-active-class="transition ease-in-out"
                                leave-to-class="opacity-0"
                            >
                                <p v-if="passwordForm.recentlySuccessful" class="text-[10px] font-bold uppercase tracking-widest text-success">Updated.</p>
                            </Transition>
                        </div>
                    </form>
                </section>
            </div>

            <div class="mt-8 flex justify-end">
                <SecondaryButton @click="close" class="rounded-xl px-8 border-transparent hover:bg-base-300">
                    Done
                </SecondaryButton>
            </div>
        </div>
    </Modal>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    @apply bg-base-300 rounded-full;
}
</style>
