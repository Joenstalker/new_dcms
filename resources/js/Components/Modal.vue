<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    maxWidth: {
        type: String,
        default: '2xl',
    },
    closeable: {
        type: Boolean,
        default: true,
    },
    teleportToBody: {
        type: Boolean,
        default: true,
    },
    zIndex: {
        type: Number,
        default: 150,
    },
});

const emit = defineEmits(['close']);
const showSlot = ref(props.show);
const hasRegisteredOpenModal = ref(false);

const setGlobalModalState = (isOpen) => {
    if (typeof document === 'undefined') {
        return;
    }

    const currentCount = Number(document.body.dataset.modalOpenCount || '0');

    if (isOpen) {
        if (!hasRegisteredOpenModal.value) {
            const nextCount = currentCount + 1;
            document.body.dataset.modalOpenCount = String(nextCount);
            hasRegisteredOpenModal.value = true;
        }
    } else if (hasRegisteredOpenModal.value) {
        const nextCount = Math.max(0, currentCount - 1);

        if (nextCount === 0) {
            delete document.body.dataset.modalOpenCount;
            document.body.classList.remove('has-open-modal');
        } else {
            document.body.dataset.modalOpenCount = String(nextCount);
        }

        hasRegisteredOpenModal.value = false;
    }

    if (Number(document.body.dataset.modalOpenCount || '0') > 0) {
        document.body.classList.add('has-open-modal');
    }
};

watch(
    () => props.show,
    (show) => {
        if (show) {
            setGlobalModalState(true);
            document.body.style.overflow = 'hidden';
            showSlot.value = true;
        } else {
            setGlobalModalState(false);
            document.body.style.overflow = '';
            
            setTimeout(() => {
                showSlot.value = false;
            }, 200);
        }
    },
    { immediate: true },
);

const close = () => {
    if (props.closeable) {
        emit('close');
    }
};

const closeOnEscape = (e) => {
    if (e.key === 'Escape') {
        e.preventDefault();

        if (props.show) {
            close();
        }
    }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));

onUnmounted(() => {
    setGlobalModalState(false);
    document.removeEventListener('keydown', closeOnEscape);

    document.body.style.overflow = '';
});

const maxWidthClass = computed(() => {
    return {
        sm: 'sm:max-w-sm',
        md: 'sm:max-w-md',
        lg: 'sm:max-w-lg',
        xl: 'sm:max-w-xl',
        '2xl': 'sm:max-w-2xl',
        '3xl': 'sm:max-w-3xl',
        '4xl': 'sm:max-w-4xl',
    }[props.maxWidth];
});
</script>

<template>
    <Teleport v-if="props.teleportToBody" to="body">
        <Transition
            enter-active-class="ease-out duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="ease-in duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="show"
                class="fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0"
                :style="{ zIndex: props.zIndex }"
                scroll-region
            >
                <div
                    class="fixed inset-0 transform transition-all"
                    @click="close"
                >
                    <div
                        class="absolute inset-0 bg-neutral/40 backdrop-blur-sm"
                    />
                </div>

                <Transition
                    enter-active-class="ease-out duration-300"
                    enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-active-class="ease-in duration-200"
                    leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                >
                    <div
                        v-if="show"
                        class="mb-6 transform overflow-y-auto overflow-x-hidden max-h-[calc(100vh-2rem)] rounded-lg bg-base-100 shadow-xl transition-all w-full sm:mx-auto sm:w-full border border-base-300"
                        :class="maxWidthClass"
                    >
                        <slot v-if="showSlot" />
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>

    <Transition
        v-else
        enter-active-class="ease-out duration-300"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="ease-in duration-200"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="show"
            class="fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0"
            :style="{ zIndex: props.zIndex }"
            scroll-region
        >
            <div
                class="fixed inset-0 transform transition-all"
                @click="close"
            >
                <div
                    class="absolute inset-0 bg-neutral/40 backdrop-blur-sm"
                />
            </div>

            <Transition
                enter-active-class="ease-out duration-300"
                enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                leave-active-class="ease-in duration-200"
                leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            >
                <div
                    v-if="show"
                    class="mb-6 transform overflow-y-auto overflow-x-hidden max-h-[calc(100vh-2rem)] rounded-lg bg-base-100 shadow-xl transition-all w-full sm:mx-auto sm:w-full border border-base-300"
                    :class="maxWidthClass"
                >
                    <slot v-if="showSlot" />
                </div>
            </Transition>
        </div>
    </Transition>
</template>
