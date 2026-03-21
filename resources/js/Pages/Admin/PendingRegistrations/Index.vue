<script setup>
import { ref, computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Link, useForm, usePage, router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

const props = defineProps({
    registrations: Object,
    stats: Object,
    filters: Object,
});

const page = usePage();
const searchQuery = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');

// Filter registrations locally for immediate feedback
const filteredRegistrations = computed(() => {
    let result = props.registrations.data;
    
    if (statusFilter.value) {
        result = result.filter(r => r.status === statusFilter.value);
    }
    
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(r => 
            r.clinic_name.toLowerCase().includes(query) ||
            r.email.toLowerCase().includes(query) ||
            r.subdomain.toLowerCase().includes(query)
        );
    }
    
    return result;
});

const getStatusBadgeClass = (status) => {
    const classes = {
        'pending': 'bg-warning/20 text-warning',
        'approved': 'bg-success/20 text-success',
        'rejected': 'bg-error/20 text-error',
        'refunded': 'bg-info/20 text-info',
    };
    return classes[status] || 'bg-base-200 text-base-content/70';
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

// Rejection form
const showRejectModal = ref(false);
const rejectForm = useForm({
    rejection_message: '',
});
const selectedRegistration = ref(null);

const openRejectModal = (registration) => {
    selectedRegistration.value = registration;
    rejectForm.rejection_message = '';
    showRejectModal.value = true;
};

const submitReject = () => {
    rejectForm.post(`/admin/pending-registrations/${selectedRegistration.value.id}/reject`, {
        onSuccess: () => {
            showRejectModal.value = false;
            Swal.fire({
                icon: 'success',
                title: 'Registration Rejected',
                text: 'The applicant has been notified.',
                confirmButtonColor: '#0d9488',
            });
        },
    });
};

// Extend time function
const extendTime = (registration) => {
    Swal.fire({
        title: 'Extend Pending Time',
        input: 'number',
        inputLabel: 'Hours to extend',
        inputValue: 24,
        showCancelButton: true,
        confirmButtonColor: '#0d9488',
        inputValidator: (value) => {
            if (!value || value <= 0) {
                return 'Please enter a valid number of hours!';
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(`/admin/pending-registrations/${registration.id}/extend`, { hours: result.value }, { preserveScroll: true });
        }
    });
};

// Toggle reminder function
const toggleReminder = (registration) => {
    router.post(`/admin/pending-registrations/${registration.id}/toggle-reminder`, {}, { preserveScroll: true });
};

// Toggle auto-approve function
const toggleAutoApprove = (registration) => {
    router.post(`/admin/pending-registrations/${registration.id}/toggle-auto-approve`, {}, { preserveScroll: true });
};

const approveRegistration = (registration) => {
    Swal.fire({
        title: 'Approve Registration',
        text: `Are you sure you want to approve "${registration.clinic_name}"? This will create the tenant and notify the user.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0d9488',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, Approve',
        cancelButtonText: 'Cancel',
    }).then((result) => {
        if (result.isConfirmed) {
            axios.post(`/admin/pending-registrations/${registration.id}/approve`)
                .then((response) => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Registration Approved',
                        text: response.data.message || 'The tenant has been created and notified.',
                        confirmButtonColor: '#0d9488',
                    }).then(() => {
                        window.location.reload();
                    });
                })
                .catch((error) => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: error.response?.data?.message || 'Failed to approve registration.',
                        confirmButtonColor: '#0d9488',
                    });
                });
        }
    });
};

// Extend time modal
const showExtendModal = ref(false);
const extendForm = useForm({
    hours: 24,
});

const openExtendModal = (registration) => {
    selectedRegistration.value = registration;
    extendForm.hours = 24;
    showExtendModal.value = true;
};

const submitExtend = () => {
    axios.post(`/admin/pending-registrations/${selectedRegistration.value.id}/extend`, {
        hours: extendForm.hours,
    })
    .then((response) => {
        showExtendModal.value = false;
        Swal.fire({
            icon: 'success',
            title: 'Time Extended',
            text: `Pending time extended by ${extendForm.hours} hour(s).`,
            confirmButtonColor: '#0d9488',
        }).then(() => {
            window.location.reload();
        });
    })
    .catch((error) => {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.response?.data?.message || 'Failed to extend time.',
            confirmButtonColor: '#0d9488',
        });
    });
};

// Toggle reminder
const toggleReminder = (registration) => {
    axios.post(`/admin/pending-registrations/${registration.id}/toggle-reminder`)
        .then((response) => {
            Swal.fire({
                icon: 'success',
                title: 'Reminder Toggled',
                text: response.data.message || 'Reminder settings updated.',
                confirmButtonColor: '#0d9488',
            }).then(() => {
                window.location.reload();
            });
        })
        .catch((error) => {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.response?.data?.message || 'Failed to toggle reminder.',
                confirmButtonColor: '#0d9488',
            });
        });
};

// Toggle auto-approve
const toggleAutoApprove = (registration) => {
    axios.post(`/admin/pending-registrations/${registration.id}/toggle-auto-approve`)
        .then((response) => {
            Swal.fire({
                icon: 'success',
                title: 'Auto-Approve Toggled',
                text: response.data.message || 'Auto-approve settings updated.',
                confirmButtonColor: '#0d9488',
            }).then(() => {
                window.location.reload();
            });
        })
        .catch((error) => {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.response?.data?.message || 'Failed to toggle auto-approve.',
                confirmButtonColor: '#0d9488',
            });
        });
};
</script>

<template>
    <AdminLayout title="Pending Registrations">
        <template #header>
            <h1 class="text-xl font-bold text-base-content">Pending Registrations</h1>
        </template>
        <div class="space-y-6">

            <!-- Tabs -->
            <div class="tabs tabs-boxed inline-flex bg-base-200 border border-base-300">
                <Link
                    href="/admin/subscriptions"
                    class="tab transition-colors font-medium hover:text-base-content"
                >
                    Active Subscriptions
                </Link>
                <Link
                    href="/admin/pending-registrations"
                    class="tab tab-active transition-colors font-medium"
                >
                    Pending Registrations
                </Link>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-base-100 rounded-lg shadow-sm border border-base-300 p-4">
                    <div class="text-sm text-base-content/60">Pending Review</div>
                    <div class="text-2xl font-bold text-warning">{{ stats?.pending || 0 }}</div>
                </div>
                <div class="bg-base-100 rounded-lg shadow-sm border border-base-300 p-4">
                    <div class="text-sm text-base-content/60">Approved</div>
                    <div class="text-2xl font-bold text-success">{{ stats?.approved || 0 }}</div>
                </div>
                <div class="bg-base-100 rounded-lg shadow-sm border border-base-300 p-4">
                    <div class="text-sm text-base-content/60">Rejected</div>
                    <div class="text-2xl font-bold text-error">{{ stats?.rejected || 0 }}</div>
                </div>
                <div class="bg-base-100 rounded-lg shadow-sm border border-base-300 p-4">
                    <div class="text-sm text-base-content/60">Total</div>
                    <div class="text-2xl font-bold text-base-content">{{ stats?.total || 0 }}</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-base-100 rounded-lg shadow-sm border border-base-300 p-4">
                <div class="flex flex-col sm:flex-row gap-4">
                    <div class="flex-1">
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search by clinic name, email, or subdomain..."
                            class="w-full border-base-300 bg-base-100 text-base-content rounded-md shadow-sm focus:ring-primary focus:border-primary"
                        />
                    </div>
                    <div>
                        <select
                            v-model="statusFilter"
                            class="border-base-300 bg-base-100 text-base-content rounded-md shadow-sm focus:ring-primary focus:border-primary"
                        >
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                            <option value="refunded">Refunded</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Registrations Table -->
            <div class="bg-base-100 rounded-lg shadow-sm border border-base-300 overflow-hidden">
                <table class="min-w-full divide-y divide-base-300">
                    <thead class="bg-base-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-base-content/60 uppercase">Clinic</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-base-content/60 uppercase">Contact</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-base-content/60 uppercase">Plan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-base-content/60 uppercase">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-base-content/60 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-base-content/60 uppercase">Date</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-base-content/60 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-base-100 divide-y divide-base-300">
                        <tr v-for="registration in filteredRegistrations" :key="registration.id" class="hover:bg-base-200 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-medium text-base-content">{{ registration.clinic_name }}</div>
                                <div class="text-sm text-base-content/50">{{ registration.subdomain }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-base-content">{{ registration.first_name }} {{ registration.last_name }}</div>
                                <div class="text-sm text-base-content/50">{{ registration.email }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-base-content/70">
                                {{ registration.plan_name }}
                            </td>
                            <td class="px-6 py-4 text-sm font-medium text-base-content">
                                {{ registration.formatted_amount }}
                            </td>
                            <td class="px-6 py-4">
                                <span :class="['px-2 py-1 text-xs font-semibold rounded-full', getStatusBadgeClass(registration.status)]">
                                    {{ registration.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-base-content/50">
                                {{ formatDate(registration.created_at) }}
                            </td>
                            <td class="px-6 py-4 text-right space-x-2 whitespace-nowrap">
                                <button 
                                    v-if="registration.status === 'pending'"
                                    @click="extendTime(registration)"
                                    class="text-info hover:text-info/80 text-sm font-medium"
                                    title="Extend Time"
                                >
                                    Extend
                                </button>
                                <button 
                                    v-if="registration.status === 'pending'"
                                    @click="toggleReminder(registration)"
                                    :class="registration.reminder_enabled ? 'text-success hover:text-success/80' : 'text-base-content/40 hover:text-base-content/60'"
                                    class="text-sm font-medium transition-colors"
                                    :title="registration.reminder_enabled ? 'Disable Reminder' : 'Enable Reminder'"
                                >
                                    Reminder
                                </button>
                                <button 
                                    v-if="registration.status === 'pending'"
                                    @click="toggleAutoApprove(registration)"
                                    :class="registration.auto_approve_enabled ? 'text-success hover:text-success/80' : 'text-base-content/40 hover:text-base-content/60'"
                                    class="text-sm font-medium transition-colors"
                                    :title="registration.auto_approve_enabled ? 'Disable Auto-Approve' : 'Enable Auto-Approve'"
                                >
                                    Auto
                                </button>
                                <span v-if="registration.status === 'pending'" class="text-base-content/20 px-1">|</span>
                                <Link
                                    :href="`/admin/pending-registrations/${registration.id}`"
                                    class="text-primary hover:text-primary-focus text-sm font-medium"
                                >
                                    View
                                </Link>
                                <button
                                    v-if="registration.status === 'pending'"
                                    @click="approveRegistration(registration)"
                                    class="text-success hover:text-success/80 text-sm font-medium"
                                >
                                    Approve
                                </button>
                                <button
                                    v-if="registration.status === 'pending'"
                                    @click="openRejectModal(registration)"
                                    class="text-error hover:text-error/80 text-sm font-medium"
                                >
                                    Reject
                                </button>
                            </td>
                        </tr>
                        <tr v-if="filteredRegistrations.length === 0">
                            <td colspan="7" class="px-6 py-12 text-center text-base-content/30">
                                No pending registrations found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="registrations.links" class="flex justify-center">
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                    <Link
                        v-for="link in registrations.links"
                        :key="link.label"
                        :href="link.url"
                        v-if="link.url"
                        :class="[
                            link.active ? 'bg-primary/10 border-primary text-primary' : 'bg-base-100 border-base-300 text-base-content/50 hover:bg-base-200',
                            'relative inline-flex items-center px-4 py-2 border text-sm font-medium'
                        ]"
                        v-html="link.label"
                    />
                    <span
                        v-else
                        v-html="link.label"
                        class="relative inline-flex items-center px-4 py-2 border border-base-300 bg-base-100 text-sm font-medium text-base-content/20 cursor-not-allowed"
                    ></span>
                </nav>
            </div>
        </div>

        <!-- Reject Modal -->
        <div v-if="showRejectModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-neutral/80 backdrop-blur-sm" @click="showRejectModal = false" />
                
                <div class="inline-block align-bottom bg-base-100 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full border border-base-300">
                    <div class="bg-base-100 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg font-medium text-base-content mb-4">Reject Registration</h3>
                        <p class="text-sm text-base-content/70 mb-4">
                            Are you sure you want to reject "{{ selectedRegistration?.clinic_name }}"? 
                            This will {{ selectedRegistration?.stripe_payment_intent_id ? 'trigger a refund and ' : '' }}notify the applicant.
                        </p>
                        <div>
                            <label class="block text-sm font-medium text-base-content/70 mb-1">Rejection Reason (optional)</label>
                            <textarea
                                v-model="rejectForm.rejection_message"
                                rows="3"
                                class="w-full border-base-300 bg-base-100 text-base-content rounded-md shadow-sm focus:ring-primary focus:border-primary"
                                placeholder="Enter reason for rejection..."
                            ></textarea>
                        </div>
                    </div>
                    <div class="bg-base-200 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-base-300">
                        <button
                            @click="submitReject"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-error text-base font-medium text-error-content hover:bg-error/80 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Reject Registration
                        </button>
                        <button
                            @click="showRejectModal = false"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-base-300 shadow-sm px-4 py-2 bg-base-100 text-base font-medium text-base-content hover:bg-base-200 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Extend Modal -->
        <div v-if="showExtendModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-neutral/80 backdrop-blur-sm" @click="showExtendModal = false" />
                
                <div class="inline-block align-bottom bg-base-100 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full border border-base-300">
                    <div class="bg-base-100 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg font-medium text-base-content mb-4">Extend Pending Time</h3>
                        <p class="text-sm text-base-content/70 mb-4">
                            Extend the expiration timer for "{{ selectedRegistration?.clinic_name }}".
                        </p>
                        <div>
                            <label class="block text-sm font-medium text-base-content/70 mb-1">Hours to extend</label>
                            <input
                                v-model="extendForm.hours"
                                type="number"
                                min="1"
                                class="w-full border-base-300 bg-base-100 text-base-content rounded-md shadow-sm focus:ring-primary focus:border-primary"
                                placeholder="Enter hours..."
                            />
                        </div>
                    </div>
                    <div class="bg-base-200 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-base-300">
                        <button
                            @click="submitExtend"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-info text-base font-medium text-info-content hover:opacity-80 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Extend Time
                        </button>
                        <button
                            @click="showExtendModal = false"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-base-300 shadow-sm px-4 py-2 bg-base-100 text-base font-medium text-base-content hover:bg-base-200 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
