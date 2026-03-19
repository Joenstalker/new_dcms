<script setup>
const props = defineProps({
    form: {
        type: Object,
        required: true
    }
});
</script>

<template>
    <!-- Landing Page Settings -->
    <div class="space-y-4">
        <h4 class="text-md font-medium text-gray-900 border-b pb-2">Landing Page Customization</h4>
        
        <div>
            <label class="block text-sm font-medium text-gray-700">Hero Title</label>
            <input type="text" v-model="form.hero_title" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="e.g., Modern Care for Your Smile">
            <p class="text-xs text-gray-500 mt-1">The main headline at the top of your landing page.</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Hero Subtitle</label>
            <input type="text" v-model="form.hero_subtitle" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="e.g., Expert dental services with a gentle touch.">
            <p class="text-xs text-gray-500 mt-1">A short description below the hero title.</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">About Us Description</label>
            <textarea v-model="form.about_us_description" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Describe your clinic's mission and history..."></textarea>
            <p class="text-xs text-gray-500 mt-1">Tell your patients more about your clinic and values.</p>
        </div>
    </div>
</template>
