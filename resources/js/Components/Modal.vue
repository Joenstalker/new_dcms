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
});

const emit = defineEmits(['close']);
const showSlot = ref(props.show);

watch(
    () => props.show,
    (show) => {
        if (show) {
            document.body.style.overflow = 'hidden';
            showSlot.value = true;
        } else {
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
    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="show" class="fixed inset-0 z-[150] overflow-y-auto bg-neutral/60 backdrop-blur-sm flex items-center justify-center p-4">
                <div class="fixed inset-0" @click="close"></div>
                
                <Transition
                    enter-active-class="transition ease-out duration-300"
                    enter-from-class="opacity-0 translate-y-4 scale-95"
                    enter-to-class="opacity-100 translate-y-0 scale-100"
                >
                    <div
                        v-if="show"
                        class="relative z-[160] w-full bg-white rounded-2xl shadow-2xl overflow-hidden border border-gray-200"
                        :class="maxWidthClass"
                    >
                        <slot v-if="showSlot" />
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
