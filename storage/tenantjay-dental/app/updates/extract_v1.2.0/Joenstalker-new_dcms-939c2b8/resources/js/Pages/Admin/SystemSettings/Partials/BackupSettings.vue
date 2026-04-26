<script setup>
import axios from 'axios';
import Swal from 'sweetalert2';
import { ref, computed } from 'vue';

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    backupData: {
        type: Object,
        required: true,
    },
});

const loading = ref(false);

const googleDriveConnected = computed(() => props.backupData.google_drive_connected === true);
const usesServiceAccount = computed(() => props.backupData.drive_connection_type === 'service_account');
const lastBackup = computed(() => props.backupData.last_backup || null);
const backupLogs = computed(() => props.backupData.backup_logs || []);

const connectDrive = () => {
    window.location.href = route('admin.drive.connect');
};

const disconnectDrive = () => {
    if (! confirm('Disconnect Google Drive?')) {
        return;
    }

    window.location.href = route('admin.drive.disconnect');
};

const runBackup = async () => {
    loading.value = true;

    try {
        const response = await axios.post(route('admin.system-settings.backup.run'));

        await Swal.fire({
            icon: 'success',
            title: 'Backup Started',
            text: response.data.message || 'Backup started successfully.',
        });
    } catch (error) {
        const message = error.response?.data?.message || error.message || 'Unable to start backup. Please try again.';

        await Swal.fire({
            icon: 'error',
            title: 'Backup Failed',
            text: message,
        });
    } finally {
        loading.value = false;
    }
};

const saveGroup = async () => {
    loading.value = true;

    try {
        const response = await axios.post(route('admin.system-settings.backup.update'), {
            auto_backup_enabled: props.form.auto_backup_enabled,
            backup_frequency: props.form.backup_frequency,
            backup_time: props.form.backup_time,
            backup_retention_days: props.form.backup_retention_days,
        });

        await Swal.fire({
            icon: 'success',
            title: 'Backup Settings Saved',
            text: response.data.message || 'Backup settings updated successfully.',
        });
    } catch (error) {
        const message = error.response?.data?.message || error.message || 'Unable to save backup settings. Please try again.';

        await Swal.fire({
            icon: 'error',
            title: 'Save Failed',
            text: message,
        });
    } finally {
        loading.value = false;
    }
};
</script>

<template>
    <div class="bg-base-100 shadow-sm rounded-lg border border-base-300 overflow-hidden">
        <div class="p-6 border-b border-base-300">
            <h2 class="text-lg font-bold text-base-content">Backup & Google Drive</h2>
            <p class="mt-1 text-sm text-base-content/50">Run and configure database backups for central and tenant databases, and upload archives to Google Drive.</p>
        </div>

        <div class="p-6 space-y-6">
            <div class="grid grid-cols-1 gap-4">
                <div class="rounded-lg border border-base-300 bg-base-200 p-4">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h3 class="font-semibold text-base-content">Google Drive Connection</h3>
                            <p class="text-sm text-base-content/60">
                                {{ usesServiceAccount ? 'Service account credentials are configured; no admin Google OAuth sign-in is required.' : googleDriveConnected ? 'Google Drive is connected.' : 'Google Drive is not connected.' }}
                            </p>
                        </div>
                        <div class="flex gap-2">
                            <button
                                v-if="!usesServiceAccount && !googleDriveConnected"
                                @click.prevent="connectDrive"
                                class="btn btn-outline btn-sm"
                            >
                                Connect Drive
                            </button>
                            <button
                                v-if="!usesServiceAccount && googleDriveConnected"
                                @click.prevent="disconnectDrive"
                                class="btn btn-error btn-sm text-white"
                            >
                                Disconnect Drive
                            </button>
                            <span v-if="usesServiceAccount" class="badge badge-outline badge-primary">Service Account</span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-bold text-base-content/70">Auto Backup</label>
                        <p class="text-xs text-base-content/50 mt-1">Enable automatic database backups on the configured schedule.</p>
                    </div>
                    <div class="flex items-center">
                        <input
                            type="checkbox"
                            class="toggle border-2 border-base-300 shadow-sm"
                            :style="props.form.auto_backup_enabled ? { backgroundColor: '#0ea5e9', borderColor: '#0ea5e9' } : {}"
                            v-model="props.form.auto_backup_enabled"
                        />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-bold text-base-content/70">Backup Frequency</label>
                        <p class="text-xs text-base-content/50 mt-1">Select how often scheduled backups should run.</p>
                    </div>
                    <div>
                        <select
                            v-model="props.form.backup_frequency"
                            class="block w-full rounded-md border-base-300 bg-base-100 text-base-content shadow-sm focus:border-primary focus:ring-primary sm:text-sm px-3 py-2 border"
                        >
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-bold text-base-content/70">Backup Time</label>
                        <p class="text-xs text-base-content/50 mt-1">Time of day in 24-hour format when scheduled backups run.</p>
                    </div>
                    <div>
                        <input
                            v-model="props.form.backup_time"
                            type="time"
                            class="block w-full rounded-md border-base-300 bg-base-100 text-base-content shadow-sm focus:border-primary focus:ring-primary sm:text-sm px-3 py-2 border"
                        />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-bold text-base-content/70">Retention Days</label>
                        <p class="text-xs text-base-content/50 mt-1">How many days backup archives are retained before cleanup.</p>
                    </div>
                    <div>
                        <input
                            v-model="props.form.backup_retention_days"
                            type="number"
                            min="1"
                            max="365"
                            class="block w-full rounded-md border-base-300 bg-base-100 text-base-content shadow-sm focus:border-primary focus:ring-primary sm:text-sm px-3 py-2 border"
                        />
                    </div>
                </div>

                <div class="rounded-lg border border-base-300 bg-base-200 p-4">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h3 class="font-semibold text-base-content">Manual Backup</h3>
                            <p class="text-sm text-base-content/60">Start a backup immediately and upload it to Google Drive.</p>
                        </div>
                        <button
                            @click.prevent="runBackup"
                            :disabled="loading || !googleDriveConnected"
                            class="btn btn-primary btn-sm"
                        >
                            {{ loading ? 'Starting...' : 'Run Backup Now' }}
                        </button>
                    </div>
                    <div class="mt-3">
                        <p class="text-sm text-base-content/70">
                            {{ usesServiceAccount ? 'Backups will use the configured service account credentials.' : 'Google Drive must be connected before starting a manual backup.' }}
                        </p>
                    </div>
                </div>

                <div class="rounded-lg border border-base-300 bg-base-200 p-4">
                    <h3 class="text-base font-semibold text-base-content">Last Backup</h3>
                    <div v-if="lastBackup">
                        <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-3">
                            <div>
                                <span class="text-sm text-base-content/70">Completed</span>
                                <p class="text-sm font-medium text-base-content">{{ lastBackup.completed_at }}</p>
                            </div>
                            <div>
                                <span class="text-sm text-base-content/70">Status</span>
                                <p class="text-sm font-medium text-base-content">{{ lastBackup.status }}</p>
                            </div>
                            <div>
                                <span class="text-sm text-base-content/70">File Size</span>
                                <p class="text-sm font-medium text-base-content">{{ lastBackup.file_size }}</p>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-sm text-base-content/60 mt-3">No backup has completed yet.</p>
                </div>

                <div class="rounded-lg border border-base-300 bg-base-200 p-4">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-base font-semibold text-base-content">Recent Backup Logs</h3>
                        <span class="text-sm text-base-content/60">Last 10 records</span>
                    </div>
                    <div class="space-y-3">
                        <div
                            v-for="log in backupLogs"
                            :key="log.id"
                            class="rounded-lg border border-base-300 bg-base-100 p-3"
                        >
                            <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                                <div>
                                    <span class="text-xs text-base-content/60">Started</span>
                                    <p class="text-sm text-base-content">{{ log.started_at || 'N/A' }}</p>
                                </div>
                                <div>
                                    <span class="text-xs text-base-content/60">Completed</span>
                                    <p class="text-sm text-base-content">{{ log.completed_at || 'N/A' }}</p>
                                </div>
                                <div>
                                    <span class="text-xs text-base-content/60">Status</span>
                                    <p class="text-sm font-medium text-base-content">{{ log.status }}</p>
                                </div>
                            </div>
                            <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2">
                                <div>
                                    <span class="text-xs text-base-content/60">Size</span>
                                    <p class="text-sm text-base-content">{{ log.file_size || '—' }}</p>
                                </div>
                                <div>
                                    <span class="text-xs text-base-content/60">Duration</span>
                                    <p class="text-sm text-base-content">{{ log.duration || '—' }}</p>
                                </div>
                            </div>
                            <div v-if="log.error_message" class="mt-3 rounded-md border border-error bg-error/10 p-3 text-sm text-error">
                                <span class="font-semibold">Error:</span> {{ log.error_message }}
                            </div>
                        </div>
                        <p v-if="backupLogs.length === 0" class="text-sm text-base-content/60">No backup logs available.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="px-6 py-4 bg-base-200/50 border-t border-base-300 flex justify-end gap-2">
            <button
                class="btn btn-secondary btn-sm"
                @click="saveGroup"
            >
                Save Backup Settings
            </button>
        </div>
    </div>
</template>
