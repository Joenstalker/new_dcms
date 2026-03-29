<script setup>
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    tenant: Object,
});

const brandingColor = computed(() => props.tenant?.branding_color || '#3b82f6');
</script>

<template>
    <Head :title="`${tenant?.name || 'Clinic'} - Booking Unavailable`" />

    <div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 flex items-center justify-center p-6">
        <div class="max-w-lg w-full text-center">
            <!-- Clinic Identity -->
            <div class="mb-8">
                <div
                    class="w-20 h-20 rounded-3xl mx-auto mb-6 flex items-center justify-center shadow-xl"
                    :style="{ backgroundColor: brandingColor }"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-10 h-10 text-white">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                    </svg>
                </div>
                <h1 class="text-3xl font-black text-gray-900 mb-2">Online Booking Unavailable</h1>
                <p class="text-gray-500 text-lg">
                    <span class="font-bold" :style="{ color: brandingColor }">{{ tenant?.name || 'This clinic' }}</span>
                    is not currently accepting online bookings.
                </p>
            </div>

            <!-- Info Card -->
            <div class="bg-white rounded-3xl p-8 shadow-lg border border-gray-100 mb-8">
                <div class="flex items-start gap-4 text-left">
                    <div class="w-12 h-12 rounded-2xl bg-amber-50 flex items-center justify-center flex-shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-amber-500">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 mb-1">How to Book</h3>
                        <p class="text-sm text-gray-500 leading-relaxed">
                            Please contact the clinic directly to schedule an appointment.
                            You can call or visit during operating hours.
                        </p>
                    </div>
                </div>

                <!-- Contact Info -->
                <div class="mt-6 pt-6 border-t border-gray-100 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div v-if="tenant?.phone" class="flex items-center gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-gray-400">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                        </svg>
                        <a :href="`tel:${tenant.phone}`" class="text-sm font-bold hover:underline" :style="{ color: brandingColor }">{{ tenant.phone }}</a>
                    </div>
                    <div v-if="tenant?.email" class="flex items-center gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-gray-400">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                        </svg>
                        <a :href="`mailto:${tenant.email}`" class="text-sm font-bold hover:underline" :style="{ color: brandingColor }">{{ tenant.email }}</a>
                    </div>
                </div>
            </div>

            <!-- Back to Landing -->
            <a
                :href="'/'"
                class="inline-flex items-center px-6 py-3 rounded-2xl text-white font-bold text-sm shadow-lg hover:shadow-xl transition-all hover:scale-105 active:scale-95"
                :style="{ backgroundColor: brandingColor }"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 mr-2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
                Back to Clinic Homepage
            </a>
        </div>
    </div>
</template>
