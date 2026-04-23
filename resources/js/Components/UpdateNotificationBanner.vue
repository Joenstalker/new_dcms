<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import Swal from 'sweetalert2';

const hasUpdate = ref(false);
const latest = ref(null);
const checking = ref(false);
let intervalId = null;

async function checkOnce() {
    checking.value = true;
    try {
        const res = await window.axios.get('/api/updates/check');
        if (res?.data) {
            hasUpdate.value = Boolean(res.data.hasUpdate);
            latest.value = res.data.latest || null;
        }
    } catch (e) {
        // silent
    } finally {
        checking.value = false;
    }
}

async function applyUpdate() {
    const { isConfirmed } = await Swal.fire({
        title: 'Apply update?',
        text: `Apply update ${latest.value}? This will run migrations and rebuild assets. Proceed?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Apply',
        cancelButtonText: 'Cancel',
    });

    if (! isConfirmed) return;

    try {
        const resp = await window.axios.post('/api/updates/apply', { });
        if (resp?.data?.ok) {
            Swal.fire('Update started', 'Update execution has started. Check logs in System Settings.', 'success');
        } else {
            Swal.fire('Update failed', resp?.data?.error || 'Unknown response', 'error');
        }
    } catch (e) {
        Swal.fire('Update failed', e?.response?.data?.message || e.message || 'Request failed', 'error');
    }
}

onMounted(() => {
    checkOnce();
    intervalId = setInterval(checkOnce, 60000);
});

onUnmounted(() => {
    if (intervalId) clearInterval(intervalId);
});
</script>

<template>
    <div v-if="hasUpdate" class="hidden lg:flex items-center gap-3">
        <div class="px-3 py-2 bg-amber-100 text-amber-900 rounded-lg text-sm font-semibold">
            New system update available: <span class="font-bold">{{ latest }}</span>
        </div>
        <button class="btn btn-sm btn-primary" @click="applyUpdate">Apply</button>
    </div>
</template>
