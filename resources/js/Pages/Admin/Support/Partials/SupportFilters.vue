import { usePage } from '@inertiajs/vue3';

const page = usePage();
const primaryColor = page.props.branding?.primary_color || '#0ea5e9';

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
                    ? 'shadow-lg text-white border-transparent'
                    : 'bg-base-100 text-base-content/50 border-base-300 hover:bg-base-200 hover:border-base-300 hover:text-base-content'
            ]"
            :style="activeFilter === filter.value ? { backgroundColor: primaryColor, boxShadow: `0 4px 14px 0 ${primaryColor}33` } : {}"
        >
            {{ filter.label }}
            <span 
                v-if="counts[filter.value] !== undefined"
                :class="[
                    'ml-2 badge badge-xs font-bold py-2 px-2',
                    activeFilter === filter.value
                        ? 'text-white border-none'
                        : 'bg-base-300 text-base-content/60 border-none'
                ]"
                :style="activeFilter === filter.value ? { backgroundColor: 'rgba(255,255,255,0.2)' } : {}"
            >
                {{ counts[filter.value] }}
            </span>
        </button>
    </div>
</template>
