<script setup>
defineProps({
    tabs: {
        type: Array,
        required: true,
    },
    activeTab: {
        type: String,
        required: true,
    },
});

const emit = defineEmits(['update:activeTab']);

const setTab = (tabId) => {
    emit('update:activeTab', tabId);
};
</script>

<template>
    <div class="mb-6 border-b border-base-300">
        <nav class="-mb-px flex space-x-8">
            <button
                v-for="tab in tabs"
                :key="tab.id"
                @click="setTab(tab.id)"
                :class="[
                    activeTab === tab.id
                        ? 'border-primary text-primary'
                        : 'border-transparent text-base-content/60 hover:text-base-content hover:border-base-300',
                    'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors'
                ]"
            >
                {{ tab.name }}
            </button>
        </nav>
    </div>
</template>
