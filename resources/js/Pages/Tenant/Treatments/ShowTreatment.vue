<script setup>
import { computed } from 'vue';
import { brandingState } from '@/States/brandingState';
import { usePage } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    show: Boolean,
    treatment: Object,
});

const emit = defineEmits(['close']);

const primaryColor = computed(() => brandingState.primary_color);

const close = () => {
    emit('close');
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit'
    });
};
</script>

<template>
    <Modal :show="show" @close="close">
        <div class="p-6">
            <div class="flex items-center justify-between mb-8 pb-4 border-b border-base-200">
                <div>
                    <h2 class="text-xl font-black text-base-content uppercase tracking-tight">Treatment Details</h2>
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-base-content/30 mt-1">
                        Record ID: TR-{{ treatment?.id }}
                    </p>
                </div>
                <button @click="close" class="btn btn-sm btn-circle btn-ghost">✕</button>
            </div>

            <div v-if="treatment" class="space-y-6">
                <!-- Header Info -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-base-200/50 p-4 rounded-2xl border border-base-200">
                        <p class="text-[10px] font-black uppercase tracking-widest text-base-content/40 mb-1">Patient</p>
                        <p class="text-sm font-black text-base-content">
                            {{ treatment.patient?.first_name }} {{ treatment.patient?.last_name }}
                        </p>
                    </div>
                    <div class="bg-base-200/50 p-4 rounded-2xl border border-base-200">
                        <p class="text-[10px] font-black uppercase tracking-widest text-base-content/40 mb-1">Date</p>
                        <p class="text-sm font-black text-base-content">
                            {{ formatDate(treatment.created_at) }}
                        </p>
                    </div>
                </div>

                <!-- Diagnosis & Procedure -->
                <div class="space-y-4">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-base-content/40 mb-1">Diagnosis</p>
                        <p class="text-sm font-bold text-base-content bg-base-100 p-4 rounded-xl border border-base-200">
                            {{ treatment.diagnosis }}
                        </p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-base-content/40 mb-1">Procedure</p>
                        <div class="text-sm text-base-content bg-base-100 p-4 rounded-xl border border-base-200 whitespace-pre-wrap">
                            {{ treatment.procedure }}
                        </div>
                    </div>
                </div>

                <!-- Dentist & Cost -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-base-content/40 mb-1">Attending Dentist</p>
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center text-primary text-[10px] font-black">
                                {{ treatment.dentist?.name?.charAt(0) }}
                            </div>
                            <p class="text-sm font-bold text-base-content">{{ treatment.dentist?.name }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] font-black uppercase tracking-widest text-base-content/40 mb-1">Total Cost</p>
                        <p class="text-2xl font-black text-primary">
                            ${{ Number(treatment.cost).toFixed(2) }}
                        </p>
                    </div>
                </div>

                <!-- Notes -->
                <div v-if="treatment.notes">
                    <p class="text-[10px] font-black uppercase tracking-widest text-base-content/40 mb-1">Internal Notes</p>
                    <div class="text-xs text-base-content/60 italic bg-base-200/30 p-4 rounded-xl border border-dashed border-base-300">
                        "{{ treatment.notes }}"
                    </div>
                </div>

                <div class="flex justify-end pt-4">
                    <SecondaryButton @click="close" class="rounded-xl px-8">
                        Close
                    </SecondaryButton>
                </div>
            </div>
        </div>
    </Modal>
</template>
