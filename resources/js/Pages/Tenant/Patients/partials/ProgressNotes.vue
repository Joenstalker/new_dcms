<script setup>
import { computed } from 'vue';

const props = defineProps({
    patient: { type: Object, required: true },
});

const notes = computed(() => {
    const list = Array.isArray(props.patient?.treatments) ? [...props.patient.treatments] : [];

    return list.sort((a, b) => {
        const aTime = new Date(a?.created_at || 0).getTime();
        const bTime = new Date(b?.created_at || 0).getTime();
        return bTime - aTime;
    });
});

const formatDateTime = (value) => {
    if (!value) return 'N/A';

    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return 'N/A';

    return d.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const formatMoney = (value) => {
    const num = Number(value || 0);
    return `P${num.toFixed(2)}`;
};
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h4 class="text-sm font-black uppercase tracking-widest text-base-content">Progress Notes</h4>
                <p class="text-xs text-base-content/50">Patient-linked clinical notes and procedures</p>
            </div>
            <span class="badge badge-sm badge-outline font-black">{{ notes.length }} notes</span>
        </div>

        <div v-if="notes.length === 0" class="rounded-2xl border border-base-300 bg-base-100 p-10 text-center text-base-content/50">
            <p class="text-sm font-black uppercase tracking-widest">No progress notes yet</p>
            <p class="mt-1 text-xs">Once procedures are recorded, notes will appear here.</p>
        </div>

        <div v-else class="space-y-3">
            <article
                v-for="note in notes"
                :key="`note-${note.id}`"
                class="rounded-2xl border border-base-300 bg-base-100 p-4"
            >
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-black text-base-content">{{ note.diagnosis || 'No diagnosis provided' }}</p>
                        <p class="text-xs font-bold uppercase tracking-widest text-base-content/45 mt-1">
                            Procedure: {{ note.procedure || 'N/A' }}
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs font-black uppercase tracking-widest text-base-content/45">Date</p>
                        <p class="text-xs font-bold text-base-content">{{ formatDateTime(note.created_at) }}</p>
                    </div>
                </div>

                <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="rounded-xl bg-base-200/70 p-3">
                        <p class="text-[10px] font-black uppercase tracking-widest text-base-content/40">Clinical Notes</p>
                        <p class="mt-1 text-xs text-base-content/80 whitespace-pre-wrap">{{ note.notes || 'No additional notes.' }}</p>
                    </div>
                    <div class="rounded-xl bg-base-200/70 p-3">
                        <p class="text-[10px] font-black uppercase tracking-widest text-base-content/40">Cost</p>
                        <p class="mt-1 text-sm font-black text-primary">{{ formatMoney(note.cost) }}</p>
                    </div>
                </div>
            </article>
        </div>
    </div>
</template>
