<script setup>
defineProps({
    activeTab: {
        type: String,
        required: true
    },
    counts: {
        type: Object,
        default: () => ({})
    }
});

const emit = defineEmits(['update:activeTab']);

const tabs = [
    { id: 'all', label: 'All Services', icon: 'list' },
    { id: 'approved', label: 'Approved', icon: 'check', color: 'text-success' },
    { id: 'pending', label: 'Pending', icon: 'clock', color: 'text-warning' },
    { id: 'rejected', label: 'Rejected', icon: 'x', color: 'text-error' },
];

const handleTabChange = (tabId) => {
    emit('update:activeTab', tabId);
};
</script>

<template>
    <!-- Tabs Navigation -->
    <div class="tabs tabs-boxed mb-8 bg-white p-2 shadow-sm rounded-2xl border border-gray-100 max-w-2xl">
        <a 
            v-for="tab in tabs" 
            :key="tab.id"
            @click="handleTabChange(tab.id)"
            class="tab tab-lg transition-all duration-300 rounded-xl"
            :class="[activeTab === tab.id ? 'tab-active bg-primary text-white shadow-md' : 'text-gray-500 hover:bg-gray-50']"
        >
            <span class="flex items-center gap-2">
                <span :class="activeTab === tab.id ? 'text-white' : tab.color">{{ tab.label }}</span>
            </span>
        </a>
    </div>
</template>
