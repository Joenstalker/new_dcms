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

const isExportModalOpen = ref(false);
const exportForm = ref({
    type: 'revenue',
    filter: 'month',
    start_date: '',
    end_date: '',
});

const openExportModal = () => {
    isExportModalOpen.value = true;
};

const handleExport = () => {
    const params = new URLSearchParams(exportForm.value).toString();
    window.open(route('reports.export') + '?' + params, '_blank');
    isExportModalOpen.value = false;
};

const maxRevenue = computed(() => Math.max(...props.revenue_data.map(d => d.revenue), 1));
const maxNewPatients = computed(() => Math.max(...props.patient_data.map(d => d.new), 1));
const maxAppointments = computed(() => Math.max(...props.appointment_data.map(d => d.total), 1));
</script>

<template>
    <Head title="Advanced Analytics" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">Clinic Insights & Reports</h2>
                </div>
                <button 
                    @click="openExportModal"
                    class="btn btn-primary btn-sm h-10 px-6 rounded-xl border-none text-[10px] font-black uppercase tracking-widest shadow-lg shadow-primary/20 hover:scale-[1.02] transition-all"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Export Report
                </button>
            </div>
        </template>

        <div class="space-y-8 mt-6">

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-base-100/95 backdrop-blur-md rounded-2xl border border-base-300 p-5 shadow-sm">
                    <div class="text-[10px] font-black uppercase tracking-widest text-base-content/40">Monthly Revenue</div>
                    <div class="text-2xl font-black text-base-content mt-1">₱{{ Number(summary.revenue).toLocaleString(undefined, {minimumFractionDigits: 2}) }}</div>
                    <div class="text-xs font-bold mt-1" :class="summary.revenue_change >= 0 ? 'text-green-500' : 'text-red-500'">
                        {{ summary.revenue_change >= 0 ? '↑' : '↓' }} {{ Math.abs(summary.revenue_change) }}% vs last month
                    </div>
                </div>
                <div class="bg-base-100/95 backdrop-blur-md rounded-2xl border border-base-300 p-5 shadow-sm">
                    <div class="text-[10px] font-black uppercase tracking-widest text-base-content/40">New Patients</div>
                    <div class="text-2xl font-black text-base-content mt-1">{{ summary.new_patients }}</div>
                    <div class="text-xs font-bold mt-1" :class="summary.patient_change >= 0 ? 'text-green-500' : 'text-red-500'">
                        {{ summary.patient_change >= 0 ? '↑' : '↓' }} {{ Math.abs(summary.patient_change) }}% vs last month
                    </div>
                </div>
                <div class="bg-base-100/95 backdrop-blur-md rounded-2xl border border-base-300 p-5 shadow-sm">
                    <div class="text-[10px] font-black uppercase tracking-widest text-base-content/40">Appointments</div>
                    <div class="text-2xl font-black text-base-content mt-1">{{ summary.appointments }}</div>
                    <div class="text-xs font-bold text-base-content/50 mt-1">{{ summary.total_patients }} total patients</div>
                </div>
                <div class="bg-base-100/95 backdrop-blur-md rounded-2xl border border-base-300 p-5 shadow-sm">
                    <div class="text-[10px] font-black uppercase tracking-widest text-base-content/40">No-Show Rate</div>
                    <div class="text-2xl font-black text-base-content mt-1">{{ summary.no_show_rate }}%</div>
                    <div class="text-xs font-bold text-base-content/50 mt-1">cancellations this month</div>
                </div>
            </div>

            <!-- Revenue Report -->
            <div class="bg-base-100/95 backdrop-blur-md rounded-2xl border border-base-300 p-6 shadow-md">
                <h3 class="text-sm font-black uppercase tracking-wider text-base-content/50 mb-6">Revenue Trend (12 Months)</h3>
                <div class="flex items-end gap-2 h-56">
                    <div v-for="d in revenue_data" :key="d.month + d.year" class="flex-1 flex flex-col items-center gap-1">
                        <span class="text-[9px] font-bold text-base-content/50">₱{{ Number(d.revenue).toLocaleString() }}</span>
                        <div 
                            class="w-full rounded-t-lg bg-gradient-to-t from-blue-500 to-indigo-600 transition-all duration-500"
                            :style="{ height: Math.max((d.revenue / maxRevenue) * 100, 3) + '%' }"
                        ></div>
                        <span class="text-[9px] font-bold text-base-content/30">{{ d.month }}</span>
                    </div>
                </div>
            </div>

            <!-- Patients Acquisition -->
            <div class="bg-base-100/95 backdrop-blur-md rounded-2xl border border-base-300 p-6 shadow-md">
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

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Appointments Fill Rate -->
                <div class="bg-base-100/95 backdrop-blur-md rounded-2xl border border-base-300 p-6 shadow-md">
                    <h3 class="text-sm font-black uppercase tracking-wider text-base-content/50 mb-6">Appointment Fill Rate (12 Months)</h3>
                    <div class="flex items-end gap-2 h-40">
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

                <!-- Top Services -->
                <div class="bg-base-100/95 backdrop-blur-md rounded-2xl border border-base-300 p-6 shadow-md">
                    <h3 class="text-sm font-black uppercase tracking-wider text-base-content/50 mb-6">Top Services (Last 3 Months)</h3>
                    <div class="space-y-3">
                        <div v-for="(s, i) in service_data" :key="s.name" class="flex items-center gap-3">
                            <span class="text-xs font-black text-base-content/20 w-5">{{ i + 1 }}</span>
                            <span class="text-sm font-bold text-base-content flex-1 truncate">{{ s.name }}</span>
                            <div class="w-28 bg-base-200 rounded-full h-2.5 overflow-hidden">
                                <div class="h-full rounded-full bg-gradient-to-r from-blue-500 to-indigo-600 transition-all" 
                                     :style="{ width: Math.max((s.count / (service_data[0]?.count || 1)) * 100, 8) + '%' }"></div>
                            </div>
                            <span class="text-sm font-black text-base-content w-8 text-right">{{ s.count }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Export Modal -->
        <div class="modal" :class="{'modal-open': isExportModalOpen}">
            <div class="modal-box rounded-3xl max-w-md bg-base-100/95 backdrop-blur-md border border-base-300 shadow-2xl">
                <h3 class="font-black text-xl mb-6 uppercase tracking-tight">Export Report</h3>
                
                <div class="space-y-6">
                    <div class="form-control w-full">
                        <label class="label font-black text-[10px] uppercase tracking-widest text-base-content/40">Report Type</label>
                        <select v-model="exportForm.type" class="select select-bordered rounded-xl font-bold text-sm">
                            <option value="revenue">Revenue Report</option>
                            <option value="appointments">Appointments Report</option>
                            <option value="patients">Patients Registry</option>
                            <option value="treatments">Treatments History</option>
                        </select>
                    </div>

                    <div class="form-control w-full">
                        <label class="label font-black text-[10px] uppercase tracking-widest text-base-content/40">Time Period</label>
                        <select v-model="exportForm.filter" class="select select-bordered rounded-xl font-bold text-sm">
                            <option value="today">Today</option>
                            <option value="week">This Week</option>
                            <option value="month">This Month</option>
                            <option value="year">This Year</option>
                            <option value="custom">Custom Range</option>
                        </select>
                    </div>

                    <div v-if="exportForm.filter === 'custom'" class="grid grid-cols-2 gap-4">
                        <div class="form-control w-full">
                            <label class="label font-black text-[10px] uppercase tracking-widest text-base-content/40">Start Date</label>
                            <input v-model="exportForm.start_date" type="date" class="input input-bordered rounded-xl font-bold text-sm" />
                        </div>
                        <div class="form-control w-full">
                            <label class="label font-black text-[10px] uppercase tracking-widest text-base-content/40">End Date</label>
                            <input v-model="exportForm.end_date" type="date" class="input input-bordered rounded-xl font-bold text-sm" />
                        </div>
                    </div>

                    <div class="p-4 rounded-2xl bg-base-200/50 border border-base-300">
                        <h4 class="text-[9px] font-black uppercase tracking-[0.2em] text-base-content/30 mb-2">Export Info</h4>
                        <ul class="text-[10px] font-bold text-base-content/60 space-y-1">
                            <li class="flex items-center gap-2">
                                <svg class="w-3 h-3 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                Format: PDF (DOMPDF)
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="w-3 h-3 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                Layout: A4 Landscape
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="w-3 h-3 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                Includes Clinic Header & Digital Signature
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="modal-action mt-8 flex justify-end gap-2">
                    <button @click="isExportModalOpen = false" class="btn btn-ghost rounded-xl text-[10px] font-black uppercase tracking-widest">Cancel</button>
                    <button @click="handleExport" class="btn btn-primary rounded-xl px-8 shadow-lg text-[10px] font-black uppercase tracking-widest border-none">
                        Download PDF
                    </button>
                </div>
            </div>
            <form method="dialog" class="modal-backdrop" @click="isExportModalOpen = false">
                <button>close</button>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
