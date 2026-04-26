<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    stats: Object,
    recent_appointments: Array,
    report_level: { type: String, default: 'basic' },
    trends: { type: Array, default: () => [] },
    appointment_breakdown: { type: Object, default: () => ({}) },
    service_breakdown: { type: Array, default: () => [] },
    patient_growth: { type: Array, default: () => [] },
    can_export: { type: Boolean, default: false },
});

const isEnhanced = computed(() => ['enhanced', 'advanced'].includes(props.report_level));
const isAdvanced = computed(() => props.report_level === 'advanced');

const maxRevenue = computed(() => {
    if (!props.trends.length) return 1;
    return Math.max(...props.trends.map(t => t.revenue), 1);
});

const maxAppointments = computed(() => {
    if (!props.trends.length) return 1;
    return Math.max(...props.trends.map(t => t.appointments), 1);
});

const totalBreakdown = computed(() => {
    const b = props.appointment_breakdown;
    return (b.scheduled || 0) + (b.completed || 0) + (b.cancelled || 0) + (b.walk_in || 0) || 1;
});

const incomePeriod = ref('daily');

const incomeValue = computed(() => {
    if (incomePeriod.value === 'weekly') {
        return Number(props.stats?.weekly_income || 0);
    }

    if (incomePeriod.value === 'monthly') {
        return Number(props.stats?.monthly_income || props.stats?.monthly_revenue || 0);
    }

    return Number(props.stats?.daily_income || 0);
});

const incomeLabel = computed(() => {
    if (incomePeriod.value === 'weekly') return 'Weekly Paid Income';
    if (incomePeriod.value === 'monthly') return 'Monthly Paid Income';
    return 'Daily Paid Income';
});

const isExportModalOpen = ref(false);
const exportFilter = ref('today');

const openExportModal = () => {
    isExportModalOpen.value = true;
};

const handleExportPdf = () => {
    const params = new URLSearchParams({ filter: exportFilter.value }).toString();
    window.open(`${route('reports.export')}?${params}`, '_blank');
    isExportModalOpen.value = false;
};
</script>

<template>
    <Head title="Reports & Analytics" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Clinic Insights & Reports
                </h2>
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        @click="openExportModal"
                        class="btn btn-sm bg-gradient-to-r from-amber-400 to-orange-500 text-white border-none shadow hover:shadow-lg transition-all"
                    >
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                        Export PDF
                    </button>
                </div>
            </div>
        </template>

        <div class="space-y-8 mt-6">
            <!-- ========== BASIC TIER: Stat Cards ========== -->
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                <div class="bg-base-100 p-6 rounded-2xl shadow-sm border border-base-200 flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-blue-100 text-blue-600 flex items-center justify-center flex-shrink-0">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                    </div>
                    <div>
                        <div class="text-xs font-bold text-base-content/40 uppercase tracking-wider">Today's Appointments</div>
                        <div class="text-3xl font-black text-base-content">{{ stats.daily_appointments }}</div>
                    </div>
                </div>

                <div class="bg-base-100 p-6 rounded-2xl shadow-sm border border-base-200 flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-green-100 text-green-600 flex items-center justify-center flex-shrink-0">
                        <span class="text-3xl font-black leading-none">₱</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-2">
                            <div class="text-xs font-bold text-base-content/40 uppercase tracking-wider">{{ incomeLabel }}</div>
                            <select
                                v-model="incomePeriod"
                                class="select select-xs border-base-300 bg-base-100 font-black text-[10px] uppercase tracking-wider min-h-0 h-7"
                            >
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                            </select>
                        </div>
                        <div class="text-3xl font-black text-base-content">₱{{ incomeValue.toLocaleString(undefined, { minimumFractionDigits: 2 }) }}</div>
                    </div>
                </div>

                <div class="bg-base-100 p-6 rounded-2xl shadow-sm border border-base-200 flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-amber-100 text-amber-600 flex items-center justify-center flex-shrink-0">
                        <span class="text-3xl font-black leading-none">₱</span>
                    </div>
                    <div>
                        <div class="text-xs font-bold text-base-content/40 uppercase tracking-wider">Unpaid Balance</div>
                        <div class="text-3xl font-black text-warning">₱{{ Number(stats.unpaid_balance || 0).toLocaleString(undefined, {minimumFractionDigits: 2}) }}</div>
                    </div>
                </div>
            </div>

            <!-- ========== ENHANCED TIER: Revenue Trend ========== -->
            <div v-if="isEnhanced" class="bg-base-100 rounded-2xl shadow-sm border border-base-200 p-6">
                <h3 class="text-sm font-black uppercase tracking-wider text-base-content/50 mb-6">Revenue Trend (6 Months)</h3>
                <div class="flex items-end gap-3 h-48">
                    <div v-for="t in trends" :key="t.month" class="flex-1 flex flex-col items-center gap-2">
                        <span class="text-[10px] font-bold text-base-content/60">₱{{ Number(t.revenue).toLocaleString() }}</span>
                        <div 
                            class="w-full rounded-t-xl bg-gradient-to-t from-blue-500 to-blue-400 transition-all duration-500"
                            :style="{ height: Math.max((t.revenue / maxRevenue) * 100, 4) + '%' }"
                        ></div>
                        <span class="text-[10px] font-bold text-base-content/40">{{ t.month.split(' ')[0] }}</span>
                    </div>
                </div>
            </div>

            <!-- ========== ENHANCED TIER: Appointment Breakdown ========== -->
            <div v-if="isEnhanced" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-base-100 rounded-2xl shadow-sm border border-base-200 p-6">
                    <h3 class="text-sm font-black uppercase tracking-wider text-base-content/50 mb-6">Appointment Status (This Month)</h3>
                    <div class="space-y-4">
                        <div class="flex items-center gap-4">
                            <span class="text-xs font-bold text-base-content/60 w-20">Scheduled</span>
                            <div class="flex-1 bg-base-200 rounded-full h-4 overflow-hidden">
                                <div class="h-full rounded-full bg-yellow-400 transition-all" :style="{ width: (appointment_breakdown.scheduled / totalBreakdown * 100) + '%' }"></div>
                            </div>
                            <span class="text-sm font-black text-base-content w-8 text-right">{{ appointment_breakdown.scheduled }}</span>
                        </div>
                        <div class="flex items-center gap-4">
                            <span class="text-xs font-bold text-base-content/60 w-20">Completed</span>
                            <div class="flex-1 bg-base-200 rounded-full h-4 overflow-hidden">
                                <div class="h-full rounded-full bg-green-400 transition-all" :style="{ width: (appointment_breakdown.completed / totalBreakdown * 100) + '%' }"></div>
                            </div>
                            <span class="text-sm font-black text-base-content w-8 text-right">{{ appointment_breakdown.completed }}</span>
                        </div>
                        <div class="flex items-center gap-4">
                            <span class="text-xs font-bold text-base-content/60 w-20">Cancelled</span>
                            <div class="flex-1 bg-base-200 rounded-full h-4 overflow-hidden">
                                <div class="h-full rounded-full bg-red-400 transition-all" :style="{ width: (appointment_breakdown.cancelled / totalBreakdown * 100) + '%' }"></div>
                            </div>
                            <span class="text-sm font-black text-base-content w-8 text-right">{{ appointment_breakdown.cancelled }}</span>
                        </div>
                        <div class="flex items-center gap-4">
                            <span class="text-xs font-bold text-base-content/60 w-20">Walk-in</span>
                            <div class="flex-1 bg-base-200 rounded-full h-4 overflow-hidden">
                                <div class="h-full rounded-full bg-blue-400 transition-all" :style="{ width: (appointment_breakdown.walk_in / totalBreakdown * 100) + '%' }"></div>
                            </div>
                            <span class="text-sm font-black text-base-content w-8 text-right">{{ appointment_breakdown.walk_in }}</span>
                        </div>
                    </div>
                </div>

                <div class="bg-base-100 rounded-2xl shadow-sm border border-base-200 p-6">
                    <h3 class="text-sm font-black uppercase tracking-wider text-base-content/50 mb-6">Appointment Trend (6 Months)</h3>
                    <div class="flex items-end gap-3 h-48">
                        <div v-for="t in trends" :key="t.month" class="flex-1 flex flex-col items-center gap-2">
                            <span class="text-[10px] font-bold text-base-content/60">{{ t.appointments }}</span>
                            <div 
                                class="w-full rounded-t-xl bg-gradient-to-t from-cyan-500 to-cyan-400 transition-all duration-500"
                                :style="{ height: Math.max((t.appointments / maxAppointments) * 100, 4) + '%' }"
                            ></div>
                            <span class="text-[10px] font-bold text-base-content/40">{{ t.month.split(' ')[0] }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========== ADVANCED TIER: Service Breakdown ========== -->
            <div v-if="isAdvanced && service_breakdown.length" class="bg-base-100 rounded-2xl shadow-sm border border-base-200 p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-sm font-black uppercase tracking-wider text-base-content/50">Top Services (This Month)</h3>
                </div>
                <div class="space-y-3">
                    <div v-for="(s, i) in service_breakdown" :key="s.service" class="flex items-center gap-4">
                        <span class="text-xs font-black text-base-content/30 w-6">{{ i + 1 }}.</span>
                        <span class="text-sm font-bold text-base-content flex-1 truncate">{{ s.service }}</span>
                        <div class="w-32 bg-base-200 rounded-full h-3 overflow-hidden">
                            <div class="h-full rounded-full bg-gradient-to-r from-amber-400 to-orange-500 transition-all" 
                                 :style="{ width: Math.max((s.count / (service_breakdown[0]?.count || 1)) * 100, 8) + '%' }"></div>
                        </div>
                        <span class="text-sm font-black text-base-content w-10 text-right">{{ s.count }}</span>
                    </div>
                </div>
            </div>

            <!-- ========== ADVANCED TIER: Patient Growth ========== -->
            <div v-if="isAdvanced && patient_growth.length" class="bg-base-100 rounded-2xl shadow-sm border border-base-200 p-6">
                <h3 class="text-sm font-black uppercase tracking-wider text-base-content/50 mb-6">Patient Growth (6 Months)</h3>
                <div class="grid grid-cols-6 gap-4">
                    <div v-for="g in patient_growth" :key="g.month" class="text-center">
                        <div class="text-2xl font-black text-base-content">{{ g.new_patients }}</div>
                        <div class="text-[9px] font-bold text-base-content/30 uppercase tracking-wider">new</div>
                        <div class="text-xs font-bold text-base-content/50 mt-1">{{ g.total }} total</div>
                        <div class="text-[10px] font-bold text-base-content/30 mt-2">{{ g.month.split(' ')[0] }}</div>
                    </div>
                </div>
            </div>

            <!-- ========== BASIC TIER: Recent Activity ========== -->
            <div class="bg-base-100 rounded-2xl shadow-sm border border-base-200 overflow-hidden">
                <h3 class="px-6 py-4 border-b border-base-200 text-sm font-black uppercase tracking-wider text-base-content/50 bg-base-100">
                    Recent Appointments
                </h3>
                <ul class="divide-y divide-base-200 text-sm">
                    <li v-for="apt in recent_appointments" :key="apt.id" class="px-6 py-4 flex justify-between items-center hover:bg-base-50 transition-colors">
                        <div>
                            <div class="font-bold text-base-content">{{ new Date(apt.appointment_date).toLocaleString() }}</div>
                            <div class="text-base-content/50 text-xs mt-0.5" v-if="apt.patient">{{ apt.patient.first_name }} {{ apt.patient.last_name }} — {{ apt.service || 'General' }}</div>
                            <div class="text-base-content/50 text-xs mt-0.5" v-else>{{ apt.guest_first_name }} {{ apt.guest_last_name }} (Guest) — {{ apt.service || 'General' }}</div>
                        </div>
                        <span :class="{
                            'px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider': true,
                            'bg-yellow-100 text-yellow-700': apt.status === 'scheduled',
                            'bg-green-100 text-green-700': apt.status === 'completed',
                            'bg-red-100 text-red-700': apt.status === 'cancelled',
                            'bg-blue-100 text-blue-700': apt.status === 'walk-in',
                        }">{{ apt.status }}</span>
                    </li>
                    <li v-if="recent_appointments.length === 0" class="px-6 py-8 text-center text-base-content/30 font-bold">No recent appointments.</li>
                </ul>
            </div>

            <!-- ========== Upgrade CTA for non-enhanced plans ========== -->
            <div v-if="!isEnhanced" class="bg-base-100 rounded-2xl shadow-sm border-2 border-dashed border-base-300 p-8 text-center">
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-400 to-cyan-500 flex items-center justify-center mx-auto mb-4 shadow-lg">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                </div>
                <h3 class="text-lg font-black text-base-content mb-2">Unlock Enhanced Reports</h3>
                <p class="text-sm text-base-content/50 max-w-md mx-auto mb-4">
                    Upgrade to <strong>Pro</strong> for trend charts, appointment breakdowns, and more detailed analytics.
                </p>
            </div>
        </div>

        <div class="modal" :class="{ 'modal-open': isExportModalOpen }">
            <div class="modal-box rounded-3xl max-w-md bg-base-100/95 backdrop-blur-md border border-base-300 shadow-2xl">
                <h3 class="font-black text-xl mb-6 uppercase tracking-tight">Export PDF Report</h3>
                <div class="form-control w-full">
                    <label class="label font-black text-[10px] uppercase tracking-widest text-base-content/40">Filter Period</label>
                    <select v-model="exportFilter" class="select select-bordered rounded-xl font-bold text-sm">
                        <option value="today">Today</option>
                        <option value="week">This Week</option>
                        <option value="month">This Month</option>
                        <option value="year">This Year</option>
                    </select>
                </div>
                <div class="modal-action mt-8 flex justify-end gap-2">
                    <button @click="isExportModalOpen = false" class="btn btn-ghost rounded-xl text-[10px] font-black uppercase tracking-widest">Cancel</button>
                    <button @click="handleExportPdf" class="btn btn-primary rounded-xl px-8 shadow-lg text-[10px] font-black uppercase tracking-widest border-none">Download PDF</button>
                </div>
            </div>
            <form method="dialog" class="modal-backdrop" @click="isExportModalOpen = false">
                <button>close</button>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
