<script setup>
import { ref, computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
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
</script>

<template>
    <AdminLayout title="Pending Registrations">
        <div class="space-y-6">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="text-sm text-gray-500">Pending Review</div>
                    <div class="text-2xl font-bold text-yellow-600">{{ stats?.pending || 0 }}</div>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="text-sm text-gray-500">Approved</div>
                    <div class="text-2xl font-bold text-green-600">{{ stats?.approved || 0 }}</div>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="text-sm text-gray-500">Rejected</div>
                    <div class="text-2xl font-bold text-red-600">{{ stats?.rejected || 0 }}</div>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="text-sm text-gray-500">Total</div>
                    <div class="text-2xl font-bold text-gray-600">{{ stats?.total || 0 }}</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex flex-col sm:flex-row gap-4">
                    <div class="flex-1">
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search by clinic name, email, or subdomain..."
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500"
                        />
                    </div>
                    <div>
                        <select
                            v-model="statusFilter"
                            class="border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500"
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
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Clinic</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contact</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Plan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="registration in filteredRegistrations" :key="registration.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900">{{ registration.clinic_name }}</div>
                                <div class="text-sm text-gray-500">{{ registration.subdomain }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">{{ registration.first_name }} {{ registration.last_name }}</div>
                                <div class="text-sm text-gray-500">{{ registration.email }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                {{ registration.plan_name }}
                            </td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                {{ registration.formatted_amount }}
                            </td>
                            <td class="px-6 py-4">
                                <span :class="['px-2 py-1 text-xs font-semibold rounded-full', getStatusBadgeClass(registration.status)]">
                                    {{ registration.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                {{ formatDate(registration.created_at) }}
                            </td>
                            <td class="px-6 py-4 text-right space-x-2">
                                <Link
                                    :href="`/admin/pending-registrations/${registration.id}`"
                                    class="text-teal-600 hover:text-teal-900 text-sm"
                                >
                                    View
                                </Link>
                                <button
                                    v-if="registration.status === 'pending'"
                                    @click="approveRegistration(registration)"
                                    class="text-green-600 hover:text-green-900 text-sm"
                                >
                                    Approve
                                </button>
                                <button
                                    v-if="registration.status === 'pending'"
                                    @click="openRejectModal(registration)"
                                    class="text-red-600 hover:text-red-900 text-sm"
                                >
                                    Reject
                                </button>
                            </td>
                        </tr>
                        <tr v-if="filteredRegistrations.length === 0">
                            <td colspan="7" class="px-6 py-4 text-center text-gray-500">
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
                        :class="[
                            link.active ? 'bg-teal-50 border-teal-500 text-teal-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                            'relative inline-flex items-center px-4 py-2 border text-sm font-medium'
                        ]"
                        v-html="link.label"
                    />
                </nav>
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
                            Are you sure you want to reject "{{ selectedRegistration?.clinic_name }}"? 
                            This will {{ selectedRegistration?.stripe_payment_intent_id ? 'trigger a refund and ' : '' }}notify the applicant.
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
