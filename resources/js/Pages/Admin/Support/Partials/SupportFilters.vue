<script setup>
defineProps({
    activeFilter: {
        type: String,
        required: true
    },
    counts: {
        type: Object,
        default: () => ({})
    }
});

const emit = defineEmits(['filter-change']);

const filters = [
    { value: 'all', label: 'All' },
    { value: 'unread', label: 'Unread' },
    { value: 'read', label: 'Read' },
    { value: 'replied', label: 'Replied' },
    { value: 'archived', label: 'Archived' }
];

const handleFilterChange = (filter) => {
    emit('filter-change', filter);
};
</script>

<template>
    <!-- Filters -->
    <div class="flex flex-wrap gap-2 mb-6">
        <button
            v-for="filter in filters"
            :key="filter.value"
            @click="handleFilterChange(filter.value)"
            :class="[
                'px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200',
                activeFilter === filter.value
                    ? 'bg-teal-600 text-white shadow-md'
                    : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50 hover:border-gray-300'
            ]"
        >
            {{ filter.label }}
            <span 
                v-if="counts[filter.value] !== undefined"
                :class="[
                    'ml-2 px-2 py-0.5 rounded-full text-xs',
                    activeFilter === filter.value
                        ? 'bg-teal-700 text-teal-100'
                        : 'bg-gray-100 text-gray-600'
                ]"
            >
                {{ counts[filter.value] }}
            </span>
        </button>
    </div>
</template>
