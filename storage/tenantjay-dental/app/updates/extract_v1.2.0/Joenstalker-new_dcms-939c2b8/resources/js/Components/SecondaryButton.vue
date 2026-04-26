<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button
        :type="type"
        class="btn btn-outline btn-sm uppercase tracking-widest font-black"
    >
        <slot />
    </button>
</template>
