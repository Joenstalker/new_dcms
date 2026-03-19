<script setup>
const props = defineProps({
    form: {
        type: Object,
        required: true
    },
    qrCode: {
        type: String,
        default: ''
    },
    bookingUrl: {
        type: String,
        default: ''
    }
});
</script>

<template>
    <!-- Branding Settings -->
    <div class="space-y-4">
        <h4 class="text-md font-medium text-gray-900 border-b pb-2">Custom Branding & QR Booking</h4>
        
        <!-- QR Code Section -->
        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 flex flex-col items-center">
            <div v-html="qrCode" class="bg-white p-2 rounded shadow-sm"></div>
            <p class="mt-3 text-sm font-semibold text-gray-700">Clinic Booking QR Code</p>
            <p class="text-xs text-center text-gray-500 mt-1">Scan this to book an appointment online.</p>
            <a :href="bookingUrl" target="_blank" class="mt-2 text-xs text-blue-600 hover:underline">
                {{ bookingUrl }}
            </a>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mt-4">Primary Brand Color</label>
            <div class="mt-1 flex items-center space-x-3">
                <input type="color" v-model="form.branding_color" class="h-10 w-10 border border-gray-300 rounded-md cursor-pointer p-0 shadow-sm">
                <input type="text" v-model="form.branding_color" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="#000000">
            </div>
            <p class="text-xs text-gray-500 mt-1">This color will be applied to your public QR booking page.</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Clinic Logo</label>
            <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                <div class="space-y-1 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48"><path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /></svg>
                    <div class="flex text-sm text-gray-600 justify-center">
                        <label class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                            <span>Upload a file</span>
                            <input type="file" class="sr-only">
                        </label>
                    </div>
                    <p class="text-xs text-gray-500">PNG, JPG, GIF up to 2MB</p>
                </div>
            </div>
        </div>
    </div>
</template>
