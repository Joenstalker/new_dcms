<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, useForm, usePage, router } from '@inertiajs/vue3';

const props = defineProps({
    stats: Object,
    concerns: Array,
});

const currentTab = ref('overview');
const liveStats = ref({ ...(props.stats || {}) });
const tenantId = computed(() => page.props.tenant?.id || null);
let appointmentsChannel = null;
let patientsChannel = null;
let billingChannel = null;
let pendingStatsReload = null;

const form = useForm({
    status: '',
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const roles = computed(() => user.value?.roles || []);
const permissions = computed(() => user.value?.permissions || []);

const isOwner = computed(() => roles.value.includes('Owner'));
const isDentist = computed(() => roles.value.includes('Dentist'));
const isAssistant = computed(() => roles.value.includes('Assistant'));

const portalName = computed(() => {
    if (isOwner.value) return 'Clinic Owner Portal';
    if (isDentist.value) return 'Dentist Portal';
    if (isAssistant.value) return 'Assistant Portal';
    return 'Staff Portal';
});

const hasOnlyBaseAccess = computed(() => {
    if (isOwner.value) return false;
    
    // An account is in "Onboarding/Base" state ONLY if it has absolutely zero permissions
    // This is the state for newly invited staff who haven't been touched yet.
    return permissions.value.length === 0;
});

const scheduleStatsReload = () => {
    if (pendingStatsReload) {
        clearTimeout(pendingStatsReload);
    }

    pendingStatsReload = setTimeout(() => {
        router.reload({
            only: ['stats'],
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                const nextStats = page.props.stats;
                if (nextStats) {
                    liveStats.value = { ...nextStats };
                }
            },
        });
        pendingStatsReload = null;
    }, 250);
};

onMounted(() => {
    liveStats.value = { ...(props.stats || {}) };

    if (!window.Echo || !tenantId.value) return;

    if (user.value.permissions.includes('view appointments') || user.value.permissions.includes('edit appointments')) {
        appointmentsChannel = window.Echo.private(`tenant.${tenantId.value}.appointments`)
            .listen('.OnlineBookingCreated', () => scheduleStatsReload())
            .listen('.TenantAppointmentChanged', () => scheduleStatsReload());
    }

    if (user.value.permissions.includes('view patients') || user.value.permissions.includes('edit patients')) {
        patientsChannel = window.Echo.private(`tenant.${tenantId.value}.patients`)
            .listen('.TenantPatientChanged', () => scheduleStatsReload());
    }

    if (user.value.permissions.includes('view billing') || user.value.permissions.includes('edit billing') || user.value.permissions.includes('create billing') || user.value.permissions.includes('view reports')) {
        billingChannel = window.Echo.private(`tenant.${tenantId.value}.billing`)
            .listen('.TenantInvoiceChanged', () => scheduleStatsReload());
    }
});

onUnmounted(() => {
    if (pendingStatsReload) {
        clearTimeout(pendingStatsReload);
        pendingStatsReload = null;
    }

    if (window.Echo && tenantId.value) {
        window.Echo.leave(`tenant.${tenantId.value}.appointments`);
        window.Echo.leave(`tenant.${tenantId.value}.patients`);
        window.Echo.leave(`tenant.${tenantId.value}.billing`);
    }

    appointmentsChannel = null;
    patientsChannel = null;
    billingChannel = null;
});

const updateStatus = (concern, newStatus) => {
    form.status = newStatus;
    form.patch(route('tenant.concern.update', concern.id), {
        preserveScroll: true,
    });
};

const firstName = computed(() => {
    const fullName = user.value?.name || 'there';
    return fullName.split(' ')[0];
});

const todayLabel = computed(() => {
    return new Date().toLocaleDateString(undefined, {
        weekday: 'long',
        month: 'long',
        day: 'numeric',
    });
});

const storagePercentage = computed(() => {
    const used = Number(liveStats.value?.storage_used_bytes || 0);
    const max = Number(liveStats.value?.max_storage_bytes || 0);
    if (!max) return 0;
    return Math.min((used / max) * 100, 100);
});

const formatMoney = (value) => `₱${Number(value || 0).toLocaleString()}`;

const hasPermission = (permission) => permissions.value.includes(permission);

const hasRoute = (name) => {
    try {
        return route().has(name);
    } catch (e) {
        return false;
    }
};

const quickActions = computed(() => {
    const actions = [
        {
            key: 'appointment',
            label: 'New Appointment',
            description: 'Manage booking queue',
            route: 'appointments.index',
            allowed: hasPermission('view appointments') || hasPermission('edit appointments') || isOwner.value,
        },
        {
            key: 'patient',
            label: 'Add Patient',
            description: 'Open patient registry',
            route: 'patients.index',
            allowed: hasPermission('view patients') || hasPermission('create patients') || isOwner.value,
        },
        {
            key: 'treatment',
            label: 'Record Treatment',
            description: 'Update treatment records',
            route: 'treatments.index',
            allowed: hasPermission('view treatments') || hasPermission('create treatments') || isOwner.value,
        },
        {
            key: 'billing',
            label: 'Open Billing',
            description: 'Track payments and POS',
            route: 'billing.index',
            allowed: hasPermission('view billing') || hasPermission('create billing') || isOwner.value,
        },
    ];

    return actions.filter((action) => action.allowed && hasRoute(action.route));
});

const openAction = (routeName) => {
    if (!hasRoute(routeName)) return;
    router.visit(route(routeName));
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <h2 class="text-3xl font-black leading-tight text-base-content tracking-tight">
                    {{ portalName }}
                </h2>
                <div class="flex items-center gap-2 rounded-2xl p-1.5 border border-base-300 bg-base-100 shadow-sm">
                    <button
                        @click="currentTab = 'overview'"
                        class="px-4 py-2 text-sm font-black rounded-xl transition-all"
                        :class="currentTab === 'overview' ? 'bg-primary/10 text-primary' : 'text-base-content/55 hover:text-base-content/85'"
                    >
                        Overview
                    </button>
                    <button
                        @click="currentTab = 'concerns'"
                        class="px-4 py-2 text-sm font-black rounded-xl transition-all flex items-center gap-2"
                        :class="currentTab === 'concerns' ? 'bg-primary/10 text-primary' : 'text-base-content/55 hover:text-base-content/85'"
                    >
                        Concerns
                        <span v-if="concerns.some((c) => c.status === 'pending')" class="w-2 h-2 bg-error rounded-full animate-pulse"></span>
                    </button>
                </div>
            </div>
        </template>

        <div class="py-8 px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-[1500px] space-y-8">
                
                <!-- Overview Content -->
                <div v-if="currentTab === 'overview'" class="space-y-8 animate-in fade-in duration-500">
                    <section class="relative overflow-hidden rounded-3xl border border-base-300 bg-base-100 shadow-xl p-6 md:p-8">
                        <div class="absolute -top-12 -right-10 w-56 h-56 rounded-full bg-primary/10 blur-3xl"></div>
                        <div class="relative z-10 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                            <div>
                                <p class="text-[11px] font-black uppercase tracking-[0.22em] text-base-content/40">Control Center</p>
                                <h3 class="text-3xl md:text-4xl font-black text-base-content mt-2">Welcome back, {{ firstName }}.</h3>
                                <p class="text-sm md:text-base text-base-content/60 mt-2">
                                    {{ todayLabel }} · Real-time clinic activity is synced and ready.
                                </p>
                            </div>
                            <div class="grid grid-cols-2 gap-3 min-w-[300px]">
                                <div class="rounded-2xl border border-base-300 bg-base-100 px-4 py-3">
                                    <p class="text-[10px] font-black uppercase tracking-[0.18em] text-base-content/45">Appointments</p>
                                    <p class="text-2xl font-black text-base-content mt-1">{{ liveStats.daily_appointments || 0 }}</p>
                                </div>
                                <div class="rounded-2xl border border-base-300 bg-base-100 px-4 py-3">
                                    <p class="text-[10px] font-black uppercase tracking-[0.18em] text-base-content/45">Pending</p>
                                    <p class="text-2xl font-black text-base-content mt-1">{{ liveStats.pending_appointments || 0 }}</p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section v-if="quickActions.length > 0" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                        <button
                            v-for="action in quickActions"
                            :key="action.key"
                            @click="openAction(action.route)"
                            class="group rounded-2xl border border-base-300 bg-base-100 p-4 text-left shadow-sm hover:shadow-lg hover:-translate-y-0.5 transition-all"
                        >
                            <p class="text-sm font-black text-base-content">{{ action.label }}</p>
                            <p class="text-xs text-base-content/55 mt-1">{{ action.description }}</p>
                            <p class="text-[10px] font-black uppercase tracking-[0.16em] mt-3 text-primary">Open</p>
                        </button>
                    </section>

                    <!-- Stat Cards -->
                    <div v-if="user.permissions.length > 0 && !hasOnlyBaseAccess" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5">
                        <!-- Daily Appointments -->
                        <div v-if="user.permissions.includes('view appointments')" class="overflow-hidden rounded-3xl p-6 border border-base-300 bg-base-100 shadow-sm hover:shadow-xl hover:-translate-y-0.5 transition-all">
                            <div class="text-[10px] font-black text-primary uppercase tracking-[0.18em] mb-2">
                                {{ isDentist ? 'My Appointments' : 'Daily Appointments' }}
                            </div>
                            <div class="text-4xl font-black text-base-content">{{ liveStats.daily_appointments || 0 }}</div>
                            <div class="mt-3 h-1.5 rounded-full bg-base-200 overflow-hidden">
                                <div class="h-full bg-primary" :style="{ width: `${Math.min((Number(liveStats.daily_appointments || 0) / 20) * 100, 100)}%` }"></div>
                            </div>
                        </div>

                        <!-- Monthly Revenue (Owner Only) -->
                        <div v-if="isOwner || user.permissions.includes('view reports')" class="overflow-hidden rounded-3xl p-6 border border-base-300 bg-base-100 shadow-sm hover:shadow-xl hover:-translate-y-0.5 transition-all">
                            <div class="text-[10px] font-black text-success uppercase tracking-[0.18em] mb-2">Monthly Revenue</div>
                            <div class="text-4xl font-black text-base-content">{{ formatMoney(liveStats.monthly_revenue) }}</div>
                            <div class="mt-3 h-1.5 rounded-full bg-base-200 overflow-hidden">
                                <div class="h-full bg-success" :style="{ width: `${Math.min((Number(liveStats.monthly_revenue || 0) / 50000) * 100, 100)}%` }"></div>
                            </div>
                        </div>

                        <!-- Total Patients -->
                        <div v-if="isOwner || user.permissions.includes('view patients')" class="overflow-hidden rounded-3xl p-6 border border-base-300 bg-base-100 shadow-sm hover:shadow-xl hover:-translate-y-0.5 transition-all">
                            <div class="text-[10px] font-black text-secondary uppercase tracking-[0.18em] mb-2">Total Patients</div>
                            <div class="text-4xl font-black text-base-content">{{ liveStats.total_patients || 0 }}</div>
                            <div class="mt-3 h-1.5 rounded-full bg-base-200 overflow-hidden">
                                <div class="h-full bg-secondary" :style="{ width: `${Math.min((Number(liveStats.total_patients || 0) / 200) * 100, 100)}%` }"></div>
                            </div>
                        </div>

                        <!-- Pending Bookings -->
                        <div v-if="isOwner || user.permissions.includes('view appointments')" class="overflow-hidden rounded-3xl p-6 border border-base-300 bg-base-100 shadow-sm hover:shadow-xl hover:-translate-y-0.5 transition-all">
                            <div class="text-[10px] font-black text-warning uppercase tracking-[0.18em] mb-2">Pending Bookings</div>
                            <div class="text-4xl font-black text-base-content">{{ liveStats.pending_appointments || 0 }}</div>
                            <div class="mt-3 h-1.5 rounded-full bg-base-200 overflow-hidden">
                                <div class="h-full bg-warning" :style="{ width: `${Math.min((Number(liveStats.pending_appointments || 0) / 20) * 100, 100)}%` }"></div>
                            </div>
                        </div>

                        <!-- Storage Usage (Owner only) -->
                        <div v-if="isOwner" class="overflow-hidden rounded-3xl p-6 border border-base-300 bg-base-100 shadow-sm hover:shadow-xl hover:-translate-y-0.5 transition-all md:col-span-2 xl:col-span-2">
                            <div class="flex justify-between items-center mb-1">
                                <div class="text-[10px] font-black text-info uppercase tracking-[0.18em]">Storage Used</div>
                                <div class="text-[10px] font-bold text-base-content/40 italic">
                                    {{ storagePercentage.toFixed(1) }}%
                                </div>
                            </div>
                            <div class="text-3xl font-black text-base-content leading-none mb-2">
                                {{ liveStats.storage_used_bytes >= 1048576 ? (liveStats.storage_used_bytes / 1048576).toFixed(1) + ' MB' : (liveStats.storage_used_bytes / 1024).toFixed(0) + ' KB' }}
                            </div>
                            <div class="w-full bg-base-200 h-2 rounded-full overflow-hidden shadow-inner">
                                <div 
                                    class="h-full bg-info rounded-full transition-all duration-1000" 
                                    :style="{ width: storagePercentage + '%' }"
                                ></div>
                            </div>
                            <div class="text-[10px] text-base-content/45 mt-2 flex justify-between items-center">
                                <span>Plan: <span class="font-bold">{{ (liveStats.max_storage_bytes / 1048576).toFixed(0) }}MB</span></span>
                                <span v-if="liveStats.storage_used_bytes > liveStats.max_storage_bytes" class="text-error font-bold animate-pulse">Limit Exceeded</span>
                            </div>
                        </div>
                    </div>

                    <!-- Welcome/Onboarding State (Staff with no clinical access) -->
                    <div v-else-if="hasOnlyBaseAccess" class="bg-base-100 overflow-hidden shadow-lg rounded-3xl p-12 border border-base-300 flex flex-col items-center justify-center text-center animate-in zoom-in duration-700 mb-8">
                        <div class="w-20 h-20 bg-primary/10 rounded-full flex items-center justify-center mb-6 shadow-inner text-4xl group-hover:rotate-12 transition-transform">
                            🏥
                        </div>
                        <h3 class="text-xl font-black text-base-content uppercase tracking-widest">Welcome to the Clinic</h3>
                        <p class="text-base-content/60 mt-2 max-w-sm mx-auto font-medium leading-relaxed">
                            Your account has been successfully created! Your Clinic Owner is currently setting up your specific module access (Clinical vs Scheduling). <br/><br/>
                            <span class="text-primary font-bold">Please check back shortly or refresh your page once notified.</span>
                        </p>
                    </div>

                    <!-- General Welcome Card -->
                    <div class="overflow-hidden bg-base-100 shadow-sm rounded-3xl p-8 border border-base-300 relative group">
                        <div class="relative z-10">
                            <h3 class="text-2xl font-black text-base-content mb-2">
                                Welcome Back, {{ user.name.split(' ')[0] }}! 👋
                            </h3>
                            <p class="text-base-content/60 leading-relaxed max-w-2xl">
                                <span v-if="isOwner">You are currently in the <strong>Admin Control Center</strong>. Manage your clinic operations and staff efficiently.</span>
                                <span v-else-if="isDentist">You are in the <strong>Dentist Portal</strong>. View your assigned treatments and patient records once authorized.</span>
                                <span v-else-if="isAssistant">You are in the <strong>Assistant Portal</strong>. Manage appointments and patient inquiries once authorized.</span>
                                <br v-if="liveStats.daily_appointments > 0 && user.permissions.includes('view appointments')"/>
                                <span v-if="liveStats.daily_appointments > 0 && user.permissions.includes('view appointments')" class="mt-2 block">
                                    You have <span class="text-primary font-bold underline">{{ liveStats.daily_appointments }} appointments</span> scheduled for today.
                                </span>
                            </p>
                        </div>
                        <div class="absolute top-0 right-0 p-8 opacity-5 group-hover:scale-110 group-hover:rotate-12 transition-transform duration-700">
                            <span class="text-9xl">🦷</span>
                        </div>
                    </div>
                </div>

                <!-- Concerns Content -->
                <div v-else-if="currentTab === 'concerns' && (isOwner || user.permissions.includes('view reports'))" class="animate-in slide-in-from-bottom duration-500 space-y-6">
                    <div class="bg-base-100 overflow-hidden shadow-sm rounded-3xl p-8 border border-base-300">
                        <div class="flex justify-between items-center mb-8">
                            <div>
                                <h3 class="text-2xl font-black text-base-content">Patient Concerns</h3>
                                <p class="text-base-content/60">Messages sent via the clinic landing page.</p>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="text-xs font-bold text-base-content/45 uppercase tracking-widest border-b border-base-200">
                                        <th class="pb-4 px-4 text-center">Date</th>
                                        <th class="pb-4 px-4">Patient</th>
                                        <th class="pb-4 px-4">Subject</th>
                                        <th class="pb-4 px-4">Status</th>
                                        <th class="pb-4 px-4 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-base-200 text-sm">
                                    <tr v-for="concern in concerns" :key="concern.id" class="group hover:bg-base-200/35 transition-colors">
                                        <td class="py-6 px-4 text-center text-base-content/45">
                                            {{ new Date(concern.created_at).toLocaleDateString() }}
                                        </td>
                                        <td class="py-6 px-4">
                                            <div class="font-bold text-base-content">{{ concern.name }}</div>
                                            <div class="text-xs text-base-content/55">{{ concern.email }}</div>
                                        </td>
                                        <td class="py-6 px-4">
                                            <div class="font-medium text-base-content/85">{{ concern.subject || 'No Subject' }}</div>
                                            <div class="text-xs text-base-content/45 truncate max-w-xs">{{ concern.message }}</div>
                                        </td>
                                        <td class="py-6 px-4">
                                            <span :class="['px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest', 
                                                concern.status === 'pending' ? 'bg-yellow-100 text-yellow-700' : 
                                                concern.status === 'in_progress' ? 'bg-blue-100 text-blue-700' :
                                                'bg-green-100 text-green-700']">
                                                {{ concern.status }}
                                            </span>
                                        </td>
                                        <td class="py-6 px-4 text-right">
                                            <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                                <button v-if="isOwner || isAssistant" @click="updateStatus(concern, 'resolved')" title="Mark as Resolved" 
                                                    class="p-2 bg-success/10 text-success rounded-lg hover:bg-success/20 transition-colors">
                                                    ✅
                                                </button>
                                                <button v-if="isOwner || isAssistant" @click="updateStatus(concern, 'in_progress')" title="Mark as In Progress" 
                                                    class="p-2 bg-primary/10 text-primary rounded-lg hover:bg-primary/20 transition-colors">
                                                    ⏳
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <!-- Empty State -->
                                    <tr v-if="concerns.length === 0">
                                        <td colspan="5" class="py-20 text-center text-base-content/45 italic">
                                            No patient concerns recorded yet.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
