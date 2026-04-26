<script setup>
import { brandingState } from '@/States/brandingState';
import Modal from '@/Components/Modal.vue';
import { router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import Swal from 'sweetalert2';

const props = defineProps({
    show: Boolean,
    defaultMedicalChecklist: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close']);

const primaryColor = computed(() => brandingState.primary_color);

const form = useForm({
    name: '',
    description: '',
    is_active: true,
});

const showGenerateModal = ref(false);
const generateSearch = ref('');
const selectedGenerateNames = ref([]);

const filteredGenerateOptions = computed(() => {
    const keyword = generateSearch.value.trim().toLowerCase();
    if (!keyword) {
        return props.defaultMedicalChecklist;
    }

    return props.defaultMedicalChecklist.filter((item) => {
        const name = String(item?.name || '').toLowerCase();
        const description = String(item?.description || '').toLowerCase();
        return name.includes(keyword) || description.includes(keyword);
    });
});

const close = () => {
    form.reset();
    form.clearErrors();
    form.is_active = true;
    showGenerateModal.value = false;
    selectedGenerateNames.value = [];
    generateSearch.value = '';
    emit('close');
};

const submit = () => {
    form.post(route('medical-records.store'), {
        preserveScroll: true,
        onSuccess: () => {
            close();
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Medical record item created successfully',
                showConfirmButton: false,
                timer: 2500,
            });
        },
    });
};

const openGenerateModal = () => {
    selectedGenerateNames.value = props.defaultMedicalChecklist.map((item) => item.name);
    generateSearch.value = '';
    showGenerateModal.value = true;
};

const toggleGenerateSelection = (name) => {
    if (selectedGenerateNames.value.includes(name)) {
        selectedGenerateNames.value = selectedGenerateNames.value.filter((item) => item !== name);
        return;
    }

    selectedGenerateNames.value = [...selectedGenerateNames.value, name];
};

const selectAllVisible = () => {
    const visibleNames = filteredGenerateOptions.value.map((item) => item.name);
    selectedGenerateNames.value = Array.from(new Set([...selectedGenerateNames.value, ...visibleNames]));
};

const clearAllVisible = () => {
    const visibleNames = new Set(filteredGenerateOptions.value.map((item) => item.name));
    selectedGenerateNames.value = selectedGenerateNames.value.filter((name) => !visibleNames.has(name));
};

const generateDefaults = () => {
    if (selectedGenerateNames.value.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'No items selected',
            text: 'Select at least one medical record item to generate.',
        });
        return;
    }

    router.post(route('medical-records.generate-defaults'), {
        names: selectedGenerateNames.value,
    }, {
        preserveScroll: true,
        onStart: () => {
            Swal.fire({
                title: 'Processing',
                text: 'Generating selected medical record items...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => Swal.showLoading(),
            });
        },
        onSuccess: () => {
            showGenerateModal.value = false;
            close();
            Swal.fire({
                icon: 'success',
                title: 'Generated',
                text: 'Selected medical record items were generated successfully.',
                timer: 1800,
                showConfirmButton: false,
            });
        },
        onError: () => {
            Swal.fire({
                icon: 'error',
                title: 'Generation failed',
                text: 'Please try again.',
            });
        },
    });
};

const generateByCount = async () => {
    const { value: count, isConfirmed } = await Swal.fire({
        title: 'Generate Medical Items by Count',
        text: 'Enter how many medical items to generate from factory list (1-50).',
        input: 'number',
        inputValue: 10,
        inputAttributes: {
            min: '1',
            max: '50',
            step: '1',
        },
        showCancelButton: true,
        confirmButtonText: 'Generate',
        cancelButtonText: 'Cancel',
        preConfirm: (value) => {
            const parsed = Number(value);
            if (!Number.isInteger(parsed) || parsed < 1 || parsed > 50) {
                Swal.showValidationMessage('Please enter a whole number from 1 to 50.');
                return false;
            }
            return parsed;
        },
    });

    if (!isConfirmed || !count) {
        return;
    }

    router.post(route('medical-records.generate-samples'), { count }, {
        preserveScroll: true,
        onStart: () => {
            Swal.fire({
                title: 'Processing',
                text: 'Generating medical items from factory list...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => Swal.showLoading(),
            });
        },
        onSuccess: () => {
            showGenerateModal.value = false;
            close();
            Swal.fire({
                icon: 'success',
                title: 'Generated',
                text: 'Medical record items generated successfully.',
                timer: 1800,
                showConfirmButton: false,
            });
        },
        onError: () => {
            Swal.fire({
                icon: 'error',
                title: 'Generation failed',
                text: 'Please try again.',
            });
        },
    });
};

watch(() => props.show, (value) => {
    if (!value) {
        form.reset();
        form.clearErrors();
        form.is_active = true;
    }
});
</script>

<template>
    <Modal :show="show" @close="close">
        <div class="p-6">
            <h2 class="text-xl font-black text-base-content uppercase tracking-tight mb-6">Create Medical Record Item</h2>
            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Name</label>
                    <input v-model="form.name" type="text" class="w-full rounded-xl border-base-300 bg-base-100 focus:ring-primary" placeholder="e.g. Bleeding Disorder" required>
                    <p v-if="form.errors.name" class="mt-1 text-xs font-bold text-error">{{ form.errors.name }}</p>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Description (Optional)</label>
                    <textarea v-model="form.description" rows="3" class="w-full rounded-xl border-base-300 bg-base-100 focus:ring-primary" placeholder="Short note about why this item is clinically relevant."></textarea>
                    <p v-if="form.errors.description" class="mt-1 text-xs font-bold text-error">{{ form.errors.description }}</p>
                </div>
                <div>
                    <label class="flex items-center gap-2 mt-1">
                        <input v-model="form.is_active" type="checkbox" class="checkbox checkbox-sm">
                        <span class="text-sm font-bold text-base-content/70">Active</span>
                    </label>
                </div>
                <div class="flex justify-end gap-3 mt-8">
                    <button
                        v-if="defaultMedicalChecklist.length"
                        type="button"
                        @click="generateByCount"
                        class="btn rounded-xl px-6"
                    >
                        Generate
                    </button>
                    <button type="button" @click="close" class="btn rounded-xl px-6">Cancel</button>
                    <button type="submit" class="btn rounded-xl px-8 border-0 text-white font-black text-xs uppercase tracking-widest" :style="{ backgroundColor: primaryColor }" :disabled="form.processing">
                        Create
                    </button>
                </div>
            </form>
        </div>
    </Modal>

    <Modal :show="showGenerateModal" @close="showGenerateModal = false">
        <div class="p-6">
            <h2 class="text-xl font-black text-base-content uppercase tracking-tight mb-2">Generate Medical Record Items</h2>
            <p class="text-sm text-base-content/60 mb-4">Select items from your predefined checklist.</p>

            <div class="flex flex-col sm:flex-row gap-2 mb-3">
                <input
                    v-model="generateSearch"
                    type="text"
                    class="w-full rounded-xl border-base-300 bg-base-100"
                    placeholder="Search item..."
                >
                <button type="button" class="btn rounded-xl" @click="selectAllVisible">Select Visible</button>
                <button type="button" class="btn rounded-xl" @click="clearAllVisible">Clear Visible</button>
            </div>

            <div class="max-h-72 overflow-y-auto border border-base-300 rounded-xl p-3 space-y-2">
                <label
                    v-for="item in filteredGenerateOptions"
                    :key="item.name"
                    class="flex items-start justify-between gap-3 px-3 py-2 rounded-lg hover:bg-base-200/60 cursor-pointer"
                >
                    <div>
                        <p class="text-sm font-semibold">{{ item.name }}</p>
                        <p class="text-xs text-base-content/60">{{ item.description }}</p>
                    </div>
                    <input
                        type="checkbox"
                        class="checkbox checkbox-sm mt-1"
                        :checked="selectedGenerateNames.includes(item.name)"
                        @change="toggleGenerateSelection(item.name)"
                    >
                </label>
                <p v-if="filteredGenerateOptions.length === 0" class="text-sm text-base-content/60 text-center py-6">
                    No items match your search.
                </p>
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <p class="mr-auto self-center text-xs font-semibold text-base-content/60">{{ selectedGenerateNames.length }} selected</p>
                <button type="button" @click="showGenerateModal = false" class="btn rounded-xl px-6">Cancel</button>
                <button type="button" @click="generateDefaults" class="btn rounded-xl px-8 border-0 text-white font-black text-xs uppercase tracking-widest" :style="{ backgroundColor: primaryColor }">
                    Generate Selected
                </button>
            </div>
        </div>
    </Modal>
</template>
