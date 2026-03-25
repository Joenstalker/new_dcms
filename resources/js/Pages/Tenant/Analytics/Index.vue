<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    revenue_data: { type: Array, default: () => [] },
    patient_data: { type: Array, default: () => [] },
    appointment_data: { type: Array, default: () => [] },
    service_data: { type: Array, default: () => [] },
    dentist_data: { type: Array, default: () => [] },
    summary: { type: Object, default: () => ({}) },
});

const activeTab = ref('revenue');
const tabs = [
    { key: 'revenue', label: 'Revenue', icon: '💰' },
    { key: 'patients', label: 'Patients', icon: '👥' },
    { key: 'appointments', label: 'Appointments', icon: '📅' },
    { key: 'services', label: 'Services', icon: '🦷' },
];

const maxRevenue = computed(() => Math.max(...props.revenue_data.map(d => d.revenue), 1));
const maxNewPatients = computed(() => Math.max(...props.patient_data.map(d => d.new), 1));
const maxAppointments = computed(() => Math.max(...props.appointment_data.map(d => d.total), 1));
</script>

<template>
    <Head title="Advanced Analytics" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Advanced Analytics</h2>
                <span class="px-3 py-1 text-[10px] font-black uppercase tracking-widest rounded-full bg-gradient-to-r from-amber-400 to-orange-500 text-white shadow">
                    Ultimate
                </span>
            </div>
        </template>

        <div class="space-y-8 mt-6">
            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-base-100 rounded-2xl border border-base-200 p-5">
                    <div class="text-[10px] font-black uppercase tracking-widest text-base-content/40">Monthly Revenue</div>
                    <div class="text-2xl font-black text-base-content mt-1">₱{{ Number(summary.revenue).toLocaleString(undefined, {minimumFractionDigits: 2}) }}</div>
                    <div class="text-xs font-bold mt-1" :class="summary.revenue_change >= 0 ? 'text-green-500' : 'text-red-500'">
                        {{ summary.revenue_change >= 0 ? '↑' : '↓' }} {{ Math.abs(summary.revenue_change) }}% vs last month
                    </div>
                </div>
                <div class="bg-base-100 rounded-2xl border border-base-200 p-5">
                    <div class="text-[10px] font-black uppercase tracking-widest text-base-content/40">New Patients</div>
                    <div class="text-2xl font-black text-base-content mt-1">{{ summary.new_patients }}</div>
                    <div class="text-xs font-bold mt-1" :class="summary.patient_change >= 0 ? 'text-green-500' : 'text-red-500'">
                        {{ summary.patient_change >= 0 ? '↑' : '↓' }} {{ Math.abs(summary.patient_change) }}% vs last month
                    </div>
                </div>
                <div class="bg-base-100 rounded-2xl border border-base-200 p-5">
                    <div class="text-[10px] font-black uppercase tracking-widest text-base-content/40">Appointments</div>
                    <div class="text-2xl font-black text-base-content mt-1">{{ summary.appointments }}</div>
                    <div class="text-xs font-bold text-base-content/50 mt-1">{{ summary.total_patients }} total patients</div>
                </div>
                <div class="bg-base-100 rounded-2xl border border-base-200 p-5">
                    <div class="text-[10px] font-black uppercase tracking-widest text-base-content/40">No-Show Rate</div>
                    <div class="text-2xl font-black text-base-content mt-1">{{ summary.no_show_rate }}%</div>
                    <div class="text-xs font-bold text-base-content/50 mt-1">cancellations this month</div>
                </div>
            </div>

            <!-- Tab Navigation -->
            <div class="flex gap-2 bg-base-100 rounded-2xl p-2 border border-base-200 shadow-sm">
                <button 
                    v-for="tab in tabs" :key="tab.key"
                    @click="activeTab = tab.key"
                    :class="[
                        'flex-1 py-3 px-4 rounded-xl text-xs font-black uppercase tracking-wider transition-all',
                        activeTab === tab.key 
                            ? 'bg-gradient-to-r from-amber-400 to-orange-500 text-white shadow-lg' 
                            : 'text-base-content/40 hover:text-base-content/70 hover:bg-base-200'
                    ]"
                >
                    <span class="mr-1">{{ tab.icon }}</span> {{ tab.label }}
                </button>
            </div>

            <!-- Revenue Tab -->
            <div v-if="activeTab === 'revenue'" class="bg-base-100 rounded-2xl border border-base-200 p-6">
                <h3 class="text-sm font-black uppercase tracking-wider text-base-content/50 mb-6">Revenue (12 Months)</h3>
                <div class="flex items-end gap-2 h-56">
                    <div v-for="d in revenue_data" :key="d.month + d.year" class="flex-1 flex flex-col items-center gap-1">
                        <span class="text-[9px] font-bold text-base-content/50">₱{{ Number(d.revenue).toLocaleString() }}</span>
                        <div 
                            class="w-full rounded-t-lg bg-gradient-to-t from-amber-500 to-amber-400 transition-all duration-500"
                            :style="{ height: Math.max((d.revenue / maxRevenue) * 100, 3) + '%' }"
                        ></div>
                        <span class="text-[9px] font-bold text-base-content/30">{{ d.month }}</span>
                    </div>
                </div>
            </div>

            <!-- Patients Tab -->
            <div v-if="activeTab === 'patients'" class="bg-base-100 rounded-2xl border border-base-200 p-6">
                <h3 class="text-sm font-black uppercase tracking-wider text-base-content/50 mb-6">Patient Acquisition (12 Months)</h3>
                <div class="flex items-end gap-2 h-56">
                    <div v-for="d in patient_data" :key="d.month" class="flex-1 flex flex-col items-center gap-1">
                        <span class="text-[9px] font-bold text-base-content/50">{{ d.new }}</span>
                        <div 
                            class="w-full rounded-t-lg bg-gradient-to-t from-cyan-500 to-cyan-400 transition-all duration-500"
                            :style="{ height: Math.max((d.new / maxNewPatients) * 100, 3) + '%' }"
                        ></div>
                        <span class="text-[9px] font-bold text-base-content/30">{{ d.month }}</span>
                    </div>
                </div>
            </div>

            <!-- Appointments Tab -->
            <div v-if="activeTab === 'appointments'" class="bg-base-100 rounded-2xl border border-base-200 p-6">
                <h3 class="text-sm font-black uppercase tracking-wider text-base-content/50 mb-6">Appointment Fill Rate (12 Months)</h3>
                <div class="flex items-end gap-2 h-56">
                    <div v-for="d in appointment_data" :key="d.month" class="flex-1 flex flex-col items-center gap-1">
                        <span class="text-[9px] font-bold text-base-content/50">{{ d.fill_rate }}%</span>
                        <div 
                            class="w-full rounded-t-lg transition-all duration-500"
                            :class="d.fill_rate >= 70 ? 'bg-gradient-to-t from-green-500 to-green-400' : d.fill_rate >= 40 ? 'bg-gradient-to-t from-yellow-500 to-yellow-400' : 'bg-gradient-to-t from-red-500 to-red-400'"
                            :style="{ height: Math.max((d.total / maxAppointments) * 100, 3) + '%' }"
                        ></div>
                        <span class="text-[9px] font-bold text-base-content/30">{{ d.month }}</span>
                    </div>
                </div>
            </div>

            <!-- Services Tab -->
            <div v-if="activeTab === 'services'" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-base-100 rounded-2xl border border-base-200 p-6">
                    <h3 class="text-sm font-black uppercase tracking-wider text-base-content/50 mb-6">Top Services (Last 3 Months)</h3>
                    <div class="space-y-3">
                        <div v-for="(s, i) in service_data" :key="s.name" class="flex items-center gap-3">
                            <span class="text-xs font-black text-base-content/20 w-5">{{ i + 1 }}</span>
                            <span class="text-sm font-bold text-base-content flex-1 truncate">{{ s.name }}</span>
                            <div class="w-28 bg-base-200 rounded-full h-2.5 overflow-hidden">
                                <div class="h-full rounded-full bg-gradient-to-r from-amber-400 to-orange-500 transition-all" 
                                     :style="{ width: Math.max((s.count / (service_data[0]?.count || 1)) * 100, 8) + '%' }"></div>
                            </div>
                            <span class="text-sm font-black text-base-content w-8 text-right">{{ s.count }}</span>
                        </div>
                        <div v-if="!service_data.length" class="text-center text-base-content/30 py-8 font-bold">No service data available.</div>
                    </div>
                </div>

                <div class="bg-base-100 rounded-2xl border border-base-200 p-6">
                    <h3 class="text-sm font-black uppercase tracking-wider text-base-content/50 mb-6">Dentist Performance (This Month)</h3>
                    <div class="space-y-4">
                        <div v-for="d in dentist_data" :key="d.name" class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-400 to-blue-500 flex items-center justify-center text-white font-black text-sm flex-shrink-0">
                                {{ d.name.charAt(0) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-bold text-base-content truncate">Dr. {{ d.name }}</div>
                                <div class="text-[10px] text-base-content/40">{{ d.completed }}/{{ d.total_appointments }} completed</div>
                            </div>
                            <div class="text-right">
                                <span class="text-lg font-black" :class="d.completion_rate >= 80 ? 'text-green-500' : d.completion_rate >= 50 ? 'text-yellow-500' : 'text-red-500'">
                                    {{ d.completion_rate }}%
                                </span>
                            </div>
                        </div>
                        <div v-if="!dentist_data.length" class="text-center text-base-content/30 py-8 font-bold">No dentist data available.</div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
