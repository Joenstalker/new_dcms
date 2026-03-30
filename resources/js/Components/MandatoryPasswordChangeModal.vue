<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['success']);

const page = usePage();
const requiresPasswordChange = computed(() => page.props.auth?.user?.requires_password_change || false);

const showModal = computed(() => props.show || requiresPasswordChange.value);

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

// Password strength calculation
const strengthScore = computed(() => {
    let score = 0;
    const pwd = passwordForm.password;
    if (!pwd) return score;
    
    if (pwd.length >= 10) score += 1; // Length > 10
    if (/[A-Z]/.test(pwd)) score += 1; // Uppercase
    if (/[a-z]/.test(pwd)) score += 1; // Lowercase
    if (/\d/.test(pwd)) score += 1; // Numbers
    if (/[^A-Za-z0-9]/.test(pwd)) score += 1; // Special char
    
    return score;
});

const strengthColor = computed(() => {
    switch (strengthScore.value) {
        case 0:
        case 1: return 'bg-error text-error-content w-1/5';
        case 2: return 'bg-warning text-warning-content w-2/5';
        case 3: return 'bg-warning text-warning-content w-3/5';
        case 4: return 'bg-info text-info-content w-4/5';
        case 5: return 'bg-success text-success-content w-full';
        default: return 'bg-base-300 w-0';
    }
});

const strengthText = computed(() => {
    switch (strengthScore.value) {
        case 0:
        case 1: return 'Very Weak';
        case 2: return 'Weak';
        case 3: return 'Fair';
        case 4: return 'Good';
        case 5: return 'Strong';
        default: return '';
    }
});

const updatePassword = () => {
    passwordForm.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => {
            passwordForm.reset();
            // Wait for Inertia to finish and update props, then reload to landing page
            window.location.reload(); 
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
</script>

<template>
    <Modal :show="showModal" @close="() => {}" max-width="md" :closeable="false">
        <div class="p-6 overflow-y-auto custom-scrollbar">
            <div class="text-center mb-6">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-warning/20 text-warning mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                    </svg>
                </div>
                <h2 class="text-2xl font-black text-base-content uppercase tracking-widest">
                    Mandatory Password Change
                </h2>
                <p class="text-sm text-base-content/70 mt-2">
                    For your security, please update your password before accessing the system.
                </p>
            </div>

            <form @submit.prevent="updatePassword" class="space-y-6">
                <!-- Current Password -->
                <div>
                    <InputLabel for="current_password" value="Current Password" class="text-xs uppercase font-black opacity-60 ml-1 mb-1" />
                    <TextInput
                        id="current_password"
                        v-model="passwordForm.current_password"
                        type="password"
                        class="mt-1 block w-full rounded-xl border-base-300 focus:ring-primary h-12"
                        autocomplete="current-password"
                        placeholder="Enter your current/temporary password"
                        required
                    />
                    <InputError :message="passwordForm.errors.current_password" class="mt-2" />
                </div>

                <!-- New Password -->
                <div>
                    <InputLabel for="password" value="New Password" class="text-xs uppercase font-black opacity-60 ml-1 mb-1" />
                    <TextInput
                        id="password"
                        v-model="passwordForm.password"
                        type="password"
                        class="mt-1 block w-full rounded-xl border-base-300 focus:ring-primary h-12"
                        autocomplete="new-password"
                        placeholder="Minimum 10 characters"
                        required
                    />
                    <InputError :message="passwordForm.errors.password" class="mt-2" />
                    
                    <!-- Strength Indicator -->
                    <div class="mt-3" v-if="passwordForm.password">
                        <div class="h-1.5 w-full bg-base-300 rounded-full overflow-hidden transition-all duration-300">
                            <div class="h-full transition-all duration-500 rounded-full" :class="strengthColor"></div>
                        </div>
                        <div class="flex justify-between items-center mt-1">
                            <span class="text-[10px] font-bold uppercase tracking-wider opacity-60">Strength</span>
                            <span class="text-[10px] font-bold uppercase tracking-wider" :class="strengthScore >= 4 ? 'text-success' : 'text-warning'">{{ strengthText }}</span>
                        </div>
                        <ul class="text-[10px] text-base-content/50 mt-2 space-y-1 ml-1">
                            <li :class="{ 'text-success': passwordForm.password.length >= 10 }">✓ At least 10 characters</li>
                            <li :class="{ 'text-success': /[A-Z]/.test(passwordForm.password) && /[a-z]/.test(passwordForm.password) }">✓ Uppercase & lowercase letters</li>
                            <li :class="{ 'text-success': /\d/.test(passwordForm.password) }">✓ Numbers</li>
                            <li :class="{ 'text-success': /[^A-Za-z0-9]/.test(passwordForm.password) }">✓ Special characters (e.g., !@#$%^&*)</li>
                        </ul>
                    </div>
                </div>

                <!-- Confirm Password -->
                <div>
                    <InputLabel for="password_confirmation" value="Confirm New Password" class="text-xs uppercase font-black opacity-60 ml-1 mb-1" />
                    <TextInput
                        id="password_confirmation"
                        v-model="passwordForm.password_confirmation"
                        type="password"
                        class="mt-1 block w-full rounded-xl border-base-300 focus:ring-primary h-12"
                        autocomplete="new-password"
                        placeholder="Re-enter your new password"
                        required
                    />
                    <InputError :message="passwordForm.errors.password_confirmation" class="mt-2" />
                </div>

                <div class="pt-4">
                    <PrimaryButton :disabled="passwordForm.processing || strengthScore < 4" class="w-full justify-center rounded-xl h-12 text-sm uppercase tracking-widest font-black transition-all">
                        Update Password
                    </PrimaryButton>
                    <p v-if="strengthScore > 0 && strengthScore < 4" class="text-[10px] text-warning text-center mt-3 uppercase tracking-widest font-bold">
                        Password is not strong enough.
                    </p>
                </div>
            </form>
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
