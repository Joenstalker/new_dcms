<script setup>
import { computed } from 'vue';

const props = defineProps({
    form: {
        type: Object,
        required: true
    }
});

const days = [
    { key: 'monday', label: 'Monday', short: 'Mon' },
    { key: 'tuesday', label: 'Tuesday', short: 'Tue' },
    { key: 'wednesday', label: 'Wednesday', short: 'Wed' },
    { key: 'thursday', label: 'Thursday', short: 'Thu' },
    { key: 'friday', label: 'Friday', short: 'Fri' },
    { key: 'saturday', label: 'Saturday', short: 'Sat' },
    { key: 'sunday', label: 'Sunday', short: 'Sun' },
];

// Ensure operating_hours data structure exists
const initHours = () => {
    if (!props.form.operating_hours) {
        props.form.operating_hours = {};
    }
    days.forEach(day => {
        if (!props.form.operating_hours[day.key]) {
            props.form.operating_hours[day.key] = {
                enabled: !['saturday', 'sunday'].includes(day.key),
                open: '08:00',
                close: '17:00'
            };
        }
    });
};
initHours();

const copyToAll = (sourceKey) => {
    const source = props.form.operating_hours[sourceKey];
    days.forEach(day => {
        if (day.key !== sourceKey) {
            props.form.operating_hours[day.key] = {
                enabled: source.enabled,
                open: source.open,
                close: source.close,
            };
        }
    });
};

const todayKey = computed(() => {
    const dayNames = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
    return dayNames[new Date().getDay()];
});

const todaySchedule = computed(() => {
    const schedule = props.form.operating_hours?.[todayKey.value];
    if (!schedule || !schedule.enabled) return null;
    return schedule;
});

const isCurrentlyOpen = computed(() => {
    if (!todaySchedule.value) return false;
    const now = new Date();
    const currentTime = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;
    return currentTime >= todaySchedule.value.open && currentTime <= todaySchedule.value.close;
});

const formatTime = (time) => {
    if (!time) return '';
    const [h, m] = time.split(':');
    const hour = parseInt(h);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const h12 = hour % 12 || 12;
    return `${h12}:${m} ${ampm}`;
};

const openDaysCount = computed(() => {
    return days.filter(d => props.form.operating_hours?.[d.key]?.enabled).length;
});
</script>

<template>
    <div class="space-y-8 animate-fade-in">
        <!-- Status Overview -->
        <section class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h4 class="text-xs font-black text-primary uppercase tracking-[0.2em] flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-primary"></span>
                    Clinic Operating Hours
                </h4>
                <p class="text-sm text-base-content/60 mt-1">Configure your clinic's weekly schedule. This will be displayed on your landing page.</p>
            </div>

            <!-- Today's Status Badge -->
            <div class="flex items-center gap-3 bg-base-100 border border-base-300 rounded-2xl px-5 py-3 shadow-sm">
                <div 
                    class="w-3 h-3 rounded-full shrink-0"
                    :class="isCurrentlyOpen ? 'bg-emerald-500 animate-pulse' : 'bg-red-400'"
                ></div>
                <div>
                    <p class="text-[10px] font-black uppercase tracking-widest opacity-40">Today's Status</p>
                    <p class="text-sm font-bold" :class="isCurrentlyOpen ? 'text-emerald-600' : 'text-red-500'">
                        {{ isCurrentlyOpen ? 'Open Now' : (todaySchedule ? 'Closed Now' : 'Closed Today') }}
                    </p>
                    <p v-if="todaySchedule" class="text-[10px] text-base-content/50 font-mono">
                        {{ formatTime(todaySchedule.open) }} – {{ formatTime(todaySchedule.close) }}
                    </p>
                </div>
            </div>
        </section>

        <!-- Summary Stat -->
        <div class="flex items-center gap-6 bg-base-200/50 rounded-2xl px-6 py-4 border border-base-300">
            <div class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-primary opacity-60">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                </svg>
                <span class="text-xs font-bold text-base-content/60">
                    <span class="text-primary font-black text-sm">{{ openDaysCount }}</span> / 7 days open
                </span>
            </div>
        </div>

        <!-- Schedule Grid -->
        <section class="space-y-3">
            <div 
                v-for="day in days" 
                :key="day.key"
                class="bg-base-100 rounded-2xl border border-base-300 p-5 transition-all"
                :class="form.operating_hours[day.key].enabled ? 'shadow-sm' : 'opacity-60'"
            >
                <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                    <!-- Day Name & Toggle -->
                    <div class="flex items-center justify-between sm:w-48 shrink-0">
                        <div class="flex items-center gap-3">
                            <div 
                                class="w-10 h-10 rounded-xl flex items-center justify-center text-xs font-black transition-colors"
                                :class="form.operating_hours[day.key].enabled 
                                    ? 'bg-primary/10 text-primary' 
                                    : 'bg-base-200 text-base-content/30'"
                            >
                                {{ day.short }}
                            </div>
                            <span class="font-bold text-sm hidden sm:block" :class="form.operating_hours[day.key].enabled ? 'text-base-content' : 'text-base-content/40'">{{ day.label }}</span>
                        </div>
                        <input 
                            type="checkbox" 
                            v-model="form.operating_hours[day.key].enabled" 
                            class="toggle toggle-primary toggle-sm"
                        >
                    </div>

                    <!-- Time Inputs -->
                    <div 
                        v-if="form.operating_hours[day.key].enabled" 
                        class="flex items-center gap-3 flex-1"
                    >
                        <div class="form-control flex-1">
                            <label class="label py-0"><span class="label-text text-[9px] font-black uppercase tracking-widest opacity-40">Opens</span></label>
                            <input 
                                type="time" 
                                v-model="form.operating_hours[day.key].open"
                                class="input input-bordered input-sm w-full rounded-xl bg-base-100 focus:border-primary border-base-300 font-mono text-sm"
                            >
                        </div>
                        
                        <span class="text-base-content/20 font-bold mt-4">–</span>
                        
                        <div class="form-control flex-1">
                            <label class="label py-0"><span class="label-text text-[9px] font-black uppercase tracking-widest opacity-40">Closes</span></label>
                            <input 
                                type="time" 
                                v-model="form.operating_hours[day.key].close"
                                class="input input-bordered input-sm w-full rounded-xl bg-base-100 focus:border-primary border-base-300 font-mono text-sm"
                            >
                        </div>

                        <!-- Copy to All -->
                        <button 
                            type="button"
                            @click="copyToAll(day.key)"
                            class="btn btn-ghost btn-xs mt-4 text-[8px] font-black uppercase tracking-widest text-primary hover:bg-primary/10 rounded-lg whitespace-nowrap"
                            title="Copy this schedule to all days"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.5a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75" />
                            </svg>
                            Apply to All
                        </button>
                    </div>

                    <!-- Closed indicator -->
                    <div v-else class="flex-1 flex items-center justify-center sm:justify-start">
                        <span class="text-[10px] font-black uppercase tracking-widest text-red-400/60 bg-red-50 px-3 py-1 rounded-lg">Closed</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Tip -->
        <div class="bg-info/10 p-4 rounded-2xl border border-info/20 flex items-start gap-4">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-blue-500 shrink-0 mt-0.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 0 0 1.5-.189m-1.5.189a6.01 6.01 0 0 1-1.5-.189m3.75 7.478a12.06 12.06 0 0 1-4.5 0m3.75 2.383a14.406 14.406 0 0 1-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 1 0-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />
            </svg>
            <div>
                <p class="text-xs font-bold text-info">Quick Tip</p>
                <p class="text-[10px] text-info/80 mt-1">Set your hours for one day, then click "Apply to All" to copy the same schedule to every day. You can then adjust individual days as needed.</p>
            </div>
        </div>
    </div>
</template>
