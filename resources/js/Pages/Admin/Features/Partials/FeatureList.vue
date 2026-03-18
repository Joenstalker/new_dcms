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

const emit = defineEmits(['view', 'edit', 'delete']);
</script>

<template>
    <div class="space-y-8">
        <div v-for="(features, category) in featuresByCategory" :key="category">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800 mr-3">
                    {{ categoryLabels[category] || category }}
                </span>
                <span class="text-gray-500 font-normal text-sm">{{ features.length }} features</span>
            </h3>
            
            <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl overflow-hidden">
                <ul role="list" class="divide-y divide-gray-100">
                    <li v-for="feature in features" :key="feature.id" class="px-6 py-5 hover:bg-gray-50 transition-colors">
                        <div class="flex items-center justify-between gap-x-6">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-x-3">
                                    <p class="text-sm font-semibold leading-6 text-gray-900">
                                        {{ feature.name }}
                                    </p>
                                    <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">
                                        {{ feature.key }}
                                    </span>
                                    <span 
                                        class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset"
                                        :class="{
                                            'bg-blue-50 text-blue-700 ring-blue-700/10': feature.type === 'boolean',
                                            'bg-emerald-50 text-emerald-700 ring-emerald-600/10': feature.type === 'numeric',
                                            'bg-purple-50 text-purple-700 ring-purple-700/10': feature.type === 'tiered',
                                        }"
                                    >
                                        {{ getTypeLabel(feature.type) }}
                                    </span>
                                    <span 
                                        v-if="!feature.is_active" 
                                        class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/10"
                                    >
                                        Inactive
                                    </span>
                                </div>
                                <div class="mt-1 flex items-center gap-x-2 text-sm leading-5 text-gray-500">
                                    <p v-if="feature.description" class="truncate">{{ feature.description }}</p>
                                    <svg v-if="feature.description && feature.options?.length" viewBox="0 0 2 2" class="h-0.5 w-0.5 fill-current"><circle cx="1" cy="1" r="1" /></svg>
                                    <p v-if="feature.options && feature.options.length">Options: {{ feature.options.join(', ') }}</p>
                                </div>
                            </div>
                            <div class="flex flex-none items-center gap-x-2">
                                <button
                                    @click="emit('view', feature)"
                                    class="rounded-md bg-white px-2.5 py-1.5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 transition-colors"
                                >
                                    View
                                </button>
                                <button
                                    @click="emit('edit', feature)"
                                    class="rounded-md bg-white px-2.5 py-1.5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 transition-colors"
                                >
                                    Edit
                                </button>
                                <button
                                    @click="emit('delete', feature)"
                                    class="rounded-md bg-white px-2.5 py-1.5 text-sm font-semibold text-red-600 shadow-sm ring-1 ring-inset ring-red-300 hover:bg-red-50 transition-colors"
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
