<script setup>
import { ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

const props = defineProps({
    registration: Object,
});

const showRejectModal = ref(false);
const rejectForm = useForm({
    rejection_message: '',
});

const getStatusBadgeClass = (status) => {
    const classes = {
        'pending': 'bg-yellow-100 text-yellow-800',
        'approved': 'bg-green-100 text-green-800',
        'rejected': 'bg-red-100 text-red-800',
        'refunded': 'bg-blue-100 text-blue-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const approveRegistration = () => {
    Swal.fire({
        title: 'Approve Registration',
        text: `Are you sure you want to approve "${props.registration.clinic_name}"? This will create the tenant and notify the user.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0d9488',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, Approve',
        cancelButtonText: 'Cancel',
    }).then((result) => {
        if (result.isConfirmed) {
            axios.post(`/admin/pending-registrations/${props.registration.id}/approve`)
                .then((response) => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Registration Approved',
                        text: 'The tenant has been created and notified.',
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

const openRejectModal = () => {
    rejectForm.rejection_message = '';
    showRejectModal.value = true;
};

const submitReject = () => {
    rejectForm.post(`/admin/pending-registrations/${props.registration.id}/reject`, {
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
</script>

<template>
    <AdminLayout :title="`Pending Registration: ${registration.clinic_name}`">
        <div class="space-y-6">
            <!-- Back Button -->
            <div>
                <Link
                    href="/admin/pending-registrations"
                    class="text-teal-600 hover:text-teal-900 flex items-center gap-2"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Back to Pending Registrations
                </Link>
            </div>

            <!-- Status and Actions -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ registration.clinic_name }}</h1>
                        <div class="flex items-center gap-3 mt-2">
                            <span :class="['px-3 py-1 text-sm font-semibold rounded-full', getStatusBadgeClass(registration.status)]">
                                {{ registration.status }}
                            </span>
                            <span v-if="registration.is_expired" class="px-3 py-1 text-sm font-semibold rounded-full bg-red-100 text-red-800">
                                Expired
                            </span>
                        </div>
                    </div>
                    <div v-if="registration.status === 'pending' && !registration.is_expired" class="flex gap-3">
                        <button
                            @click="approveRegistration"
                            class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700"
                        >
                            <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Approve
                        </button>
                        <button
                            @click="openRejectModal"
                            class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700"
                        >
                            <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            Reject
                        </button>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Clinic Information -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Clinic Information</h2>
                    <dl class="space-y-4">
                        <div class="grid grid-cols-3 gap-4">
                            <dt class="text-sm font-medium text-gray-500">Clinic Name</dt>
                            <dd class="col-span-2 text-sm text-gray-900">{{ registration.clinic_name }}</dd>
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <dt class="text-sm font-medium text-gray-500">Subdomain</dt>
                            <dd class="col-span-2 text-sm text-gray-900 font-mono">{{ registration.subdomain }}</dd>
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <dt class="text-sm font-medium text-gray-500">Plan</dt>
                            <dd class="col-span-2 text-sm text-gray-900">{{ registration.plan_name }}</dd>
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <dt class="text-sm font-medium text-gray-500">Billing Cycle</dt>
                            <dd class="col-span-2 text-sm text-gray-900 capitalize">{{ registration.billing_cycle }}</dd>
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <dt class="text-sm font-medium text-gray-500">Amount Paid</dt>
                            <dd class="col-span-2 text-sm font-bold text-green-600">{{ registration.formatted_amount }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Contact Information -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Contact Information</h2>
                    <dl class="space-y-4">
                        <div class="grid grid-cols-3 gap-4">
                            <dt class="text-sm font-medium text-gray-500">Name</dt>
                            <dd class="col-span-2 text-sm text-gray-900">{{ registration.first_name }} {{ registration.last_name }}</dd>
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <dt class="text-sm font-medium text-gray-500">Email</dt>
                            <dd class="col-span-2 text-sm text-gray-900">{{ registration.email }}</dd>
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <dt class="text-sm font-medium text-gray-500">Phone</dt>
                            <dd class="col-span-2 text-sm text-gray-900">{{ registration.phone }}</dd>
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <dt class="text-sm font-medium text-gray-500">Address</dt>
                            <dd class="col-span-2 text-sm text-gray-900">
                                {{ registration.street }}, {{ registration.barangay }}<br>
                                {{ registration.city }}, {{ registration.province }}
                            </dd>
                        </div>
                    </dl>
                </div>

                <!-- Payment Information -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Payment Details</h2>
                    <dl class="space-y-4">
                        <div class="grid grid-cols-3 gap-4">
                            <dt class="text-sm font-medium text-gray-500">Stripe Session</dt>
                            <dd class="col-span-2 text-sm text-gray-900 font-mono">{{ registration.stripe_session_id || 'N/A' }}</dd>
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <dt class="text-sm font-medium text-gray-500">Payment Intent</dt>
                            <dd class="col-span-2 text-sm text-gray-900 font-mono">{{ registration.stripe_payment_intent_id || 'N/A' }}</dd>
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <dt class="text-sm font-medium text-gray-500">Amount</dt>
                            <dd class="col-span-2 text-sm font-bold text-green-600">{{ registration.formatted_amount }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Timeline -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Timeline</h2>
                    <dl class="space-y-4">
                        <div class="grid grid-cols-3 gap-4">
                            <dt class="text-sm font-medium text-gray-500">Submitted</dt>
                            <dd class="col-span-2 text-sm text-gray-900">{{ formatDate(registration.created_at) }}</dd>
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <dt class="text-sm font-medium text-gray-500">Expires</dt>
                            <dd class="col-span-2 text-sm" :class="registration.is_expired ? 'text-red-600' : 'text-gray-900'">
                                {{ formatDate(registration.expires_at) }}
                                <span v-if="registration.is_expired" class="ml-2 text-xs">(Expired)</span>
                            </dd>
                        </div>
                        <div v-if="registration.approved_at" class="grid grid-cols-3 gap-4">
                            <dt class="text-sm font-medium text-gray-500">Approved</dt>
                            <dd class="col-span-2 text-sm text-green-600">{{ formatDate(registration.approved_at) }}</dd>
                        </div>
                        <div v-if="registration.rejected_at" class="grid grid-cols-3 gap-4">
                            <dt class="text-sm font-medium text-gray-500">Rejected</dt>
                            <dd class="col-span-2 text-sm text-red-600">{{ formatDate(registration.rejected_at) }}</dd>
                        </div>
                        <div v-if="registration.admin_rejection_message" class="grid grid-cols-3 gap-4">
                            <dt class="text-sm font-medium text-gray-500">Rejection Reason</dt>
                            <dd class="col-span-2 text-sm text-gray-900">{{ registration.admin_rejection_message }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        <!-- Reject Modal -->
        <div v-if="showRejectModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="showRejectModal = false" />
                
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Reject Registration</h3>
                        <p class="text-sm text-gray-500 mb-4">
                            Are you sure you want to reject "{{ registration.clinic_name }}"?
                            This will {{ registration.stripe_payment_intent_id ? 'trigger a refund and ' : '' }}notify the applicant.
                        </p>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Rejection Reason (optional)</label>
                            <textarea
                                v-model="rejectForm.rejection_message"
                                rows="3"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500"
                                placeholder="Enter reason for rejection..."
                            ></textarea>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button
                            @click="submitReject"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Reject Registration
                        </button>
                        <button
                            @click="showRejectModal = false"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
