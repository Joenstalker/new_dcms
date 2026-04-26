<script setup>
import { router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import Swal from 'sweetalert2';

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    editingService: {
        type: Object,
        default: null
    },
    isOwnerOrDentist: {
        type: Boolean,
        default: false
    },
    defaultServiceCatalog: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close', 'submit']);

const form = useForm({
    name: '',
    description: '',
    price: '',
});

const showGenerateModal = ref(false);
const generateSearch = ref('');
const selectedGenerateNames = ref([]);

const filteredGenerateOptions = computed(() => {
    const keyword = generateSearch.value.trim().toLowerCase();
    if (!keyword) {
        return props.defaultServiceCatalog;
    }

    return props.defaultServiceCatalog.filter((item) => {
        const name = String(item?.name || '').toLowerCase();
        const category = String(item?.category || '').toLowerCase();
        return name.includes(keyword) || category.includes(keyword);
    });
});

// Watch for show changes to update form
watch(() => props.show, (newVal) => {
    if (newVal) {
        if (props.editingService) {
            form.name = props.editingService.name;
            form.description = props.editingService.description;
            form.price = props.editingService.price;
        } else {
            form.reset();
        }
    }
});

const handleSubmit = () => {
    emit('submit', { form: form, editingService: props.editingService });
};

const handleClose = () => {
    form.reset();
    showGenerateModal.value = false;
    selectedGenerateNames.value = [];
    generateSearch.value = '';
    emit('close');
};

const openGenerateModal = () => {
    selectedGenerateNames.value = props.defaultServiceCatalog.map((item) => item.name);
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
            title: 'No services selected',
            text: 'Select at least one service to generate.',
        });
        return;
    }

    router.post(route('services.generate-defaults'), {
        names: selectedGenerateNames.value,
    }, {
        preserveScroll: true,
        onStart: () => {
            Swal.fire({
                title: 'Processing',
                text: 'Generating selected services...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => Swal.showLoading(),
            });
        },
        onSuccess: () => {
            showGenerateModal.value = false;
            Swal.fire({
                icon: 'success',
                title: 'Generated',
                text: 'Selected services were generated successfully.',
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
        title: 'Generate Services by Count',
        text: 'Enter how many services to generate from factory list (1-50).',
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

    router.post(route('services.generate-samples'), { count }, {
        preserveScroll: true,
        onStart: () => {
            Swal.fire({
                title: 'Processing',
                text: 'Generating services from factory list...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => Swal.showLoading(),
            });
        },
        onSuccess: () => {
            showGenerateModal.value = false;
            Swal.fire({
                icon: 'success',
                title: 'Generated',
                text: 'Services generated successfully.',
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
</script>

<template>
    <!-- Create/Edit Modal using daisyUI -->
    <div class="modal" :class="{'modal-open': show}">
        <div class="modal-box rounded-3xl max-w-lg">
            <h3 class="font-bold text-2xl mb-6">{{ editingService ? 'Edit Service' : 'New Service' }}</h3>
            <form @submit.prevent="handleSubmit" class="space-y-6">
                <!-- Service Name -->
                <div class="form-control w-full">
                    <label class="label">
                        <span class="label-text font-bold text-slate-600">Service Name</span>
                    </label>
                    <input v-model="form.name" type="text" placeholder="Enter service name" 
                        class="input input-bordered w-full rounded-xl focus:border-primary transition-all"
                        :class="{ 'input-error': form.errors.name }" />
                    <label v-if="form.errors.name" class="label">
                        <span class="label-text-alt text-error font-medium">{{ form.errors.name }}</span>
                    </label>
                </div>

                <div class="form-control w-full">
                    <label class="label font-semibold text-slate-600">Price (₱)</label>
                    <input v-model="form.price" type="number" step="0.01" placeholder="0.00" class="input input-bordered w-full rounded-xl focus:ring-2 focus:ring-primary shadow-sm" required />
                    <label v-if="form.errors.price" class="label text-error text-xs">{{ form.errors.price }}</label>
                </div>

                <div class="form-control w-full">
                    <label class="label font-semibold text-slate-600">Description</label>
                    <textarea v-model="form.description" class="textarea textarea-bordered h-24 rounded-xl focus:ring-2 focus:ring-primary shadow-sm" placeholder="Details about the service..."></textarea>
                    <label v-if="form.errors.description" class="label text-error text-xs">{{ form.errors.description }}</label>
                </div>

                <div class="modal-action mt-8 flex justify-end gap-2">
                    <button
                        v-if="!editingService && defaultServiceCatalog.length"
                        type="button"
                        @click="generateByCount"
                        class="btn rounded-xl"
                    >
                        Generate
                    </button>
                    <button type="button" @click="handleClose" class="btn btn-ghost rounded-xl">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-xl px-8 shadow-md" :disabled="form.processing">
                        {{ editingService ? 'UPDATE' : 'Create Service' }}
                    </button>
                </div>
            </form>
        </div>
        <div class="modal-backdrop bg-slate-900/40" @click="handleClose"></div>
    </div>

    <div class="modal" :class="{ 'modal-open': showGenerateModal }">
        <div class="modal-box rounded-3xl max-w-2xl">
            <h3 class="font-bold text-2xl mb-2">Generate Services</h3>
            <p class="text-sm text-slate-500 mb-4">Select services from your predefined list.</p>

            <div class="flex flex-col sm:flex-row gap-2 mb-3">
                <input
                    v-model="generateSearch"
                    type="text"
                    placeholder="Search service name or category..."
                    class="input input-bordered w-full rounded-xl"
                />
                <button type="button" class="btn rounded-xl" @click="selectAllVisible">Select Visible</button>
                <button type="button" class="btn rounded-xl" @click="clearAllVisible">Clear Visible</button>
            </div>

            <div class="max-h-80 overflow-y-auto border border-base-300 rounded-xl p-3 space-y-2">
                <label
                    v-for="item in filteredGenerateOptions"
                    :key="item.name"
                    class="flex items-center justify-between gap-3 px-3 py-2 rounded-lg hover:bg-base-200/60 cursor-pointer"
                >
                    <div class="min-w-0">
                        <p class="font-semibold text-sm truncate">{{ item.name }}</p>
                        <p class="text-xs text-base-content/60">{{ item.category }} - PHP {{ Number(item.amount || 0).toFixed(2) }}</p>
                    </div>
                    <input
                        type="checkbox"
                        class="checkbox checkbox-sm"
                        :checked="selectedGenerateNames.includes(item.name)"
                        @change="toggleGenerateSelection(item.name)"
                    >
                </label>
                <p v-if="filteredGenerateOptions.length === 0" class="text-sm text-base-content/60 text-center py-6">
                    No services match your search.
                </p>
            </div>

            <div class="modal-action mt-6">
                <div class="mr-auto text-xs font-semibold text-base-content/60 self-center">
                    {{ selectedGenerateNames.length }} selected
                </div>
                <button type="button" class="btn btn-ghost rounded-xl" @click="showGenerateModal = false">Cancel</button>
                <button type="button" class="btn btn-primary rounded-xl" @click="generateDefaults">
                    Generate Selected
                </button>
            </div>
        </div>
        <div class="modal-backdrop bg-slate-900/40" @click="showGenerateModal = false"></div>
    </div>
</template>
