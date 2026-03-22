<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
    type: String,
    required: true,
});

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        class="text-gray-900 bg-white placeholder-gray-400 rounded-md border-gray-300 shadow-sm focus:border-[#2B7CB3] focus:ring-[#2B7CB3]"
        v-model="model"
        ref="input"
    />
</template>
