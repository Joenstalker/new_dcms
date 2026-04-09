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
});

const page = usePage();
const tenant = computed(() => page.props.tenant || null);
const showBookingModal = ref(true);
let bookingChannel = null;

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
});

onUnmounted(() => {
    if (window.Echo && tenant.value?.id) {
        window.Echo.leave(`tenant.${tenant.value.id}.booking`);
    }
    bookingChannel = null;
});
</script>

<template>
    <div class="min-h-screen bg-gray-100">
        <BookingModal
            :show="showBookingModal"
            :tenant="tenant"
            :services="services"
            :dentists="dentists"
            @close="handleClose"
        />
    </div>
</template>
