<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    featuresByCategory: {
        type: Object,
        required: true,
    },
    categoryLabels: {
        type: Object,
        required: true,
    },
    plans: {
        type: Array,
        default: () => [],
    },
    getTypeLabel: {
        type: Function,
        required: true,
    },
});

const emit = defineEmits(['view', 'edit', 'delete', 'toggle']);
</script>

<template>
    <div class="space-y-8">
        <div v-for="(features, category) in featuresByCategory" :key="category">
            <h3 class="text-lg font-bold text-base-content mb-4 flex items-center">
                <span class="badge badge-ghost badge-lg mr-3 py-3 font-bold">
                    {{ categoryLabels[category] || category }}
                </span>
                <span class="text-base-content/50 font-normal text-sm">{{ features.length }} features</span>
            </h3>
            
            <div class="bg-base-100 shadow-sm ring-1 ring-base-300 sm:rounded-xl overflow-hidden border border-base-300">
                <ul role="list" class="divide-y divide-base-300">
                    <li v-for="feature in features" :key="feature.id" class="px-6 py-5 hover:bg-base-200/50 transition-colors">
                        <div class="flex items-center justify-between gap-x-6">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-x-3">
                                    <p class="text-sm font-bold leading-6 text-base-content">
                                        {{ feature.name }}
                                    </p>
                                    <span class="badge badge-outline badge-sm font-medium text-base-content/70">
                                        {{ feature.key }}
                                    </span>
                                    <span 
                                        class="badge badge-sm font-bold"
                                        :class="{
                                            'badge-info bg-info/10 text-info border-info/20': feature.type === 'boolean',
                                            'badge-success bg-success/10 text-success border-success/20': feature.type === 'numeric',
                                            'badge-secondary bg-secondary/10 text-secondary border-secondary/20': feature.type === 'tiered',
                                        }"
                                    >
                                        {{ getTypeLabel(feature.type) }}
                                    </span>
                                    <span 
                                        v-if="!feature.is_active" 
                                        class="badge badge-error badge-sm font-bold bg-error/10 text-error border-error/20"
                                    >
                                        Inactive
                                    </span>
                                </div>
                                <div class="mt-1 flex items-center gap-x-2 text-sm leading-5 text-base-content/50">
                                    <p v-if="feature.description" class="truncate">{{ feature.description }}</p>
                                    <svg v-if="feature.description && feature.options?.length" viewBox="0 0 2 2" class="h-0.5 w-0.5 fill-current"><circle cx="1" cy="1" r="1" /></svg>
                                    <p v-if="feature.options && feature.options.length">Options: {{ feature.options.join(', ') }}</p>
                                </div>
                            </div>
                            <div class="flex flex-none items-center gap-x-2">
                                <button
                                    @click="emit('toggle', feature)"
                                    class="btn btn-xs font-bold"
                                    :class="feature.is_active ? 'btn-ghost text-error' : 'btn-ghost text-success'"
                                    :title="feature.is_active ? 'Deactivate' : 'Activate'"
                                >
                                    {{ feature.is_active ? 'Deactivate' : 'Activate' }}
                                </button>
                                <button
                                    @click="emit('view', feature)"
                                    class="btn btn-ghost btn-xs font-bold"
                                >
                                    View
                                </button>
                                <button
                                    @click="emit('edit', feature)"
                                    class="btn btn-primary btn-xs font-bold"
                                >
                                    Edit
                                </button>
                                <button
                                    @click="emit('delete', feature)"
                                    class="btn btn-error btn-outline btn-xs font-bold"
                                >
                                    Delete
                                </button>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>
