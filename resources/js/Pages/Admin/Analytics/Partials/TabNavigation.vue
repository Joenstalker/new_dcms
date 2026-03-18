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
    <div class="mb-6 border-b border-gray-200">
        <nav class="-mb-px flex space-x-8">
            <button
                v-for="tab in tabs"
                :key="tab.id"
                @click="setTab(tab.id)"
                :class="[
                    activeTab === tab.id
                        ? 'border-indigo-500 text-indigo-600'
                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                    'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
                ]"
            >
                {{ tab.name }}
            </button>
        </nav>
    </div>
</template>
