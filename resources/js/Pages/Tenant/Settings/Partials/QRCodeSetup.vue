<script setup>
const props = defineProps({
    qrCode: {
        type: String,
        required: true
    },
    bookingUrl: {
        type: String,
        required: true
    }
});
</script>

<template>
    <div class="space-y-8 animate-fade-in">
        <section class="space-y-6">
            <h4 class="text-xs font-black text-primary uppercase tracking-[0.2em] flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-primary"></span>
                Patient Recruitment QR
            </h4>

            <div class="flex flex-col lg:flex-row gap-10 items-center">
                <!-- QR Code Display -->
                <div class="bg-white p-6 rounded-[2.5rem] shadow-xl border border-base-300 flex flex-col items-center shrink-0">
                    <div v-html="qrCode" class="p-4 bg-white rounded-2xl border border-base-100 shadow-inner"></div>
                    <div class="mt-6 text-center">
                         <p class="text-[10px] font-black uppercase tracking-[0.2em] text-primary mb-1">Clinic Booking</p>
                         <p class="text-sm font-bold opacity-70">Scan to Book</p>
                    </div>
                </div>

                <!-- Info & Links -->
                <div class="space-y-6 max-w-lg text-center lg:text-left">
                    <h3 class="text-2xl font-black text-gray-900 leading-tight">Grow your clinic with seamless mobile bookings.</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">
                        Download this QR code and print it at your clinic reception, business cards, or marketing flyers. Patients can instantly view your availability and book appointments without calling.
                    </p>
                    
                    <div class="flex flex-col gap-3">
                        <label class="text-[10px] font-black uppercase tracking-widest opacity-40">Your Public Booking URL</label>
                        <div class="flex items-center gap-2 bg-base-200 p-3 rounded-2xl border border-base-300">
                            <span class="text-xs font-mono truncate text-primary">{{ bookingUrl }}</span>
                            <button @click="copy(bookingUrl)" class="btn btn-xs btn-ghost btn-circle ml-auto">📋</button>
                        </div>
                    </div>

                    <button class="btn btn-primary btn-md rounded-2xl font-black uppercase tracking-widest text-[10px]">
                        Download SVG for Print
                    </button>
                </div>
            </div>
        </section>
    </div>
</template>

<script>
export default {
    methods: {
        copy(text) {
            navigator.clipboard.writeText(text);
            // You could add a toast here
        }
    }
}
</script>
