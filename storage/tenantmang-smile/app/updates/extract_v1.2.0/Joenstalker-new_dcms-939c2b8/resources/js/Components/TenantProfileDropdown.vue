<script setup>
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Dropdown from '@/Components/Dropdown.vue';
import ProfilePictureModal from '@/Components/ProfilePictureModal.vue';
import AccountSettingsModal from '@/Components/AccountSettingsModal.vue';
import { brandingState } from '@/States/brandingState';

const page = usePage();
const user = computed(() => page.props.auth.user);

const primaryColor = computed(() => brandingState.primary_color);
const primaryTextColor = computed(() => brandingState.contrast_color);

const showPictureModal = ref(false);
const showSettingsModal = ref(false);

const openPictureModal = () => {
    showPictureModal.value = true;
};

const openSettingsModal = () => {
    showSettingsModal.value = true;
};
</script>

<template>
    <div class="relative">
        <Dropdown align="right" width="48">
            <template #trigger>
                <button
                    type="button"
                    class="flex items-center space-x-2 p-1.5 rounded-xl hover:bg-base-200 transition-all duration-200 border border-transparent hover:border-base-300 group"
                >
                    <div
                        class="h-8 w-8 rounded-lg flex items-center justify-center font-black text-xs shadow-sm overflow-hidden"
                        :style="{ backgroundColor: primaryColor, color: primaryTextColor }"
                    >
                        <img
                            v-if="user?.profile_picture_url && !user.profile_picture_url.includes('ui-avatars')"
                            :src="user.profile_picture_url"
                            class="h-full w-full object-cover"
                            :alt="user?.name"
                        />
                        <span v-else>{{ user?.name?.charAt(0) || '?' }}</span>
                    </div>
                    <span class="hidden sm:inline text-sm font-bold text-base-content/70 group-hover:text-base-content transition-colors">
                        {{ user?.name || 'User' }}
                    </span>
                    <svg
                        class="ms-1 h-4 w-4 fill-current text-base-content/30 group-hover:text-base-content/70 transition-colors"
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 20 20"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                            clip-rule="evenodd"
                        />
                    </svg>
                </button>
            </template>

            <template #content>
                <div class="p-2 space-y-1">
                    <button
                        @click="openPictureModal"
                        class="w-full flex items-center px-4 py-2 text-sm text-left font-semibold text-base-content/70 hover:text-base-content hover:bg-base-200 rounded-lg transition-all"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM18.75 10.5h.008v.008h-.008V10.5Z" />
                        </svg>
                        Profile Picture
                    </button>

                    <button
                        @click="openSettingsModal"
                        class="w-full flex items-center px-4 py-2 text-sm text-left font-semibold text-base-content/70 hover:text-base-content hover:bg-base-200 rounded-lg transition-all"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                        </svg>
                        Account Settings
                    </button>
                </div>
            </template>
        </Dropdown>

        <ProfilePictureModal
            :show="showPictureModal"
            @close="showPictureModal = false"
        />

        <AccountSettingsModal
            :show="showSettingsModal"
            @close="showSettingsModal = false"
        />
    </div>
</template>