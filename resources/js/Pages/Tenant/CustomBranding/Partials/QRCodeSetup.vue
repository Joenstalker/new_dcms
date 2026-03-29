<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    qrCode: {
        type: String,
        required: true
    },
    bookingUrl: {
        type: String,
        required: true
    },
    form: {
        type: Object,
        required: true
    }
});

const copied = ref(false);

const copyUrl = () => {
    navigator.clipboard.writeText(props.bookingUrl);
    copied.value = true;
    setTimeout(() => { copied.value = false; }, 2000);
};

const downloadSvg = () => {
    const blob = new Blob([props.qrCode], { type: 'image/svg+xml' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'clinic-booking-qr.svg';
    a.click();
    URL.revokeObjectURL(url);
};

const isEnabled = computed(() => props.form.online_booking_enabled);
</script>

<template>
    <div class="space-y-8 animate-fade-in">
        <!-- Master Toggle -->
        <section class="space-y-6">
            <h4 class="text-xs font-black text-primary uppercase tracking-[0.2em] flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-primary"></span>
                Online Booking Control
            </h4>

            <div 
                class="p-6 rounded-3xl border-2 transition-all"
                :class="isEnabled 
                    ? 'bg-emerald-50/50 border-emerald-200 shadow-lg shadow-emerald-100/50' 
                    : 'bg-red-50/50 border-red-200 shadow-lg shadow-red-100/50'"
            >
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div 
                            class="w-14 h-14 rounded-2xl flex items-center justify-center transition-colors"
                            :class="isEnabled ? 'bg-emerald-500' : 'bg-red-400'"
                        >
                            <svg v-if="isEnabled" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-7 h-7 text-white">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-7 h-7 text-white">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-black text-base uppercase tracking-wider" :class="isEnabled ? 'text-emerald-800' : 'text-red-700'">
                                {{ isEnabled ? 'Online Booking Active' : 'Online Booking Disabled' }}
                            </h3>
                            <p class="text-xs mt-0.5" :class="isEnabled ? 'text-emerald-600' : 'text-red-500'">
                                {{ isEnabled 
                                    ? 'Patients can book appointments via QR code and your booking URL.' 
                                    : 'No one can book appointments online. QR codes will show an unavailable page.' }}
                            </p>
                        </div>
                    </div>
                    <input 
                        type="checkbox" 
                        v-model="form.online_booking_enabled" 
                        class="toggle toggle-lg"
                        :class="isEnabled ? 'toggle-success' : 'toggle-error'"
                    >
                </div>

                <!-- Warning Banner when disabled -->
                <div v-if="!isEnabled" class="mt-4 p-4 bg-red-100/50 rounded-2xl border border-red-200 flex items-start gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-red-500 shrink-0 mt-0.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                    <div>
                        <p class="text-xs font-bold text-red-700">Booking is currently offline</p>
                        <p class="text-[10px] text-red-600 mt-0.5">Patients scanning your QR code will see a "Booking Unavailable" page. Toggle this switch ON to re-enable online booking.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- QR Code & Details (only shown when enabled) -->
        <section class="space-y-6" :class="{ 'opacity-40 pointer-events-none grayscale': !isEnabled }">
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
                            <span class="text-xs font-mono truncate text-primary flex-1">{{ bookingUrl }}</span>
                            <button 
                                @click="copyUrl" 
                                type="button"
                                class="btn btn-xs btn-ghost btn-circle ml-auto shrink-0 transition-colors"
                                :class="copied ? 'text-emerald-500' : ''"
                            >
                                <svg v-if="!copied" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0 0 13.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 0 1-.75.75H9.75a.75.75 0 0 1-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 0 1 1.927-.184" />
                                </svg>
                                <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                            </button>
                        </div>
                        <p v-if="copied" class="text-[10px] text-emerald-500 font-bold animate-fade-in">URL copied to clipboard!</p>
                    </div>

                    <button 
                        type="button"
                        @click="downloadSvg" 
                        class="btn btn-primary btn-md rounded-2xl font-black uppercase tracking-widest text-[10px]"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 mr-2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                        </svg>
                        Download SVG for Print
                    </button>
                </div>
            </div>
        </section>
    </div>
</template>
