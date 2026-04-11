<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import BookingModal from './Partials/BookingModal.vue';

const props = defineProps({
    dentists: {
        type: Array,
        default: () => [],
    },
    services: {
        type: Array,
        default: () => [],
    },
    medicalRecords: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const tenant = computed(() => page.props.tenant || null);
const showBookingModal = ref(true);
let bookingChannel = null;
let brandingChannel = null;

const handleClose = () => {
    showBookingModal.value = false;
    router.visit(route('tenant.landing'));
};

onMounted(() => {
    if (!window.Echo || !tenant.value?.id) return;

    bookingChannel = window.Echo.channel(`tenant.${tenant.value.id}.booking`)
        .listen('.OnlineBookingStatusUpdated', (event) => {
            if (!Boolean(event.online_booking_enabled)) {
                showBookingModal.value = false;
                router.visit(route('tenant.book.create'), { replace: true, preserveScroll: true });
            }
        });

    brandingChannel = window.Echo.channel(`tenant.${tenant.value.id}.branding`)
        .listen('.TenantBrandingUpdated', (event) => {
            if (Object.prototype.hasOwnProperty.call(event, 'online_booking_enabled') && !Boolean(event.online_booking_enabled)) {
                showBookingModal.value = false;
                router.visit(route('tenant.book.create'), { replace: true, preserveScroll: true });
            }
        });
});

onUnmounted(() => {
    if (window.Echo && tenant.value?.id) {
        window.Echo.leave(`tenant.${tenant.value.id}.booking`);
        window.Echo.leave(`tenant.${tenant.value.id}.branding`);
    }
    bookingChannel = null;
    brandingChannel = null;
});
</script>

<template>
    <div class="min-h-screen bg-gray-100">
        <BookingModal
            :show="showBookingModal"
            :tenant="tenant"
            :services="services"
            :dentists="dentists"
            :medical-records="medicalRecords"
            @close="handleClose"
        />
    </div>
</template>
