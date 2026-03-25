<script setup>
defineProps({
    show: {
        type: Boolean,
        default: true
    }
});
</script>

<template>
    <!-- Upgrade CTA -->
    <div v-if="show" class="mt-8 bg-gray-50 rounded-lg border border-gray-200 p-6">
        <div class="flex items-center justify-between">
            <div>
                <h4 class="text-lg font-medium text-gray-900">Need more features?</h4>
                <p class="text-sm text-gray-600 mt-1">
                    Upgrade your plan to unlock more features and increase your limits.
                </p>
            </div>
            <a
                href="#"
                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
                View Plans
            </a>
        </div>
    </div>
</template>
