<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

import CalendarColor from './Partials/CalendarColor.vue';
import NotificationPreferences from './Partials/NotificationPreferences.vue';
import WorkingHours from './Partials/WorkingHours.vue';

const props = defineProps({
    calendarColor: String,
    notificationPreferences: Object,
    workingHours: Object,
    permissions: Object,
});
</script>

<template>
    <Head title="My Settings" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                My Settings
            </h2>
        </template>

        <div class="mt-6 space-y-8 max-w-4xl">
            <!-- 1. Calendar Color -->
            <CalendarColor 
                :calendarColor="calendarColor" 
                :hasPermission="permissions.calendar" 
            />

            <!-- 2. Notification Preferences -->
            <NotificationPreferences 
                :preferences="notificationPreferences" 
                :hasPermission="permissions.notifications" 
            />

            <!-- 3. Working Hours -->
            <WorkingHours 
                :hours="workingHours" 
                :hasPermission="permissions.workingHours" 
            />
        </div>
    </AuthenticatedLayout>
</template>
