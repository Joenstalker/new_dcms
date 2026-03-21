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
    <div class="flex flex-wrap gap-3 mb-8">
        <button
            v-for="filter in filters"
            :key="filter.value"
            @click="handleFilterChange(filter.value)"
            :class="[
                'btn btn-sm font-black transition-all duration-300 rounded-xl px-5 uppercase tracking-widest text-[10px]',
                activeFilter === filter.value
                    ? 'btn-primary shadow-lg shadow-primary/20'
                    : 'bg-base-100 text-base-content/50 border-base-300 hover:bg-base-200 hover:border-base-300 hover:text-base-content'
            ]"
        >
            {{ filter.label }}
            <span 
                v-if="counts[filter.value] !== undefined"
                :class="[
                    'ml-2 badge badge-xs font-bold py-2 px-2',
                    activeFilter === filter.value
                        ? 'bg-primary-focus/30 text-primary-content border-none'
                        : 'bg-base-300 text-base-content/60 border-none'
                ]"
            >
                {{ counts[filter.value] }}
            </span>
        </button>
    </div>
</template>
