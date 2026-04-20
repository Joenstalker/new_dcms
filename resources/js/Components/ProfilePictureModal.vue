<script setup>
import { ref, onMounted, watch, nextTick, computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Croppie from 'croppie';
import 'croppie/croppie.css';
import Swal from 'sweetalert2';

const props = defineProps({
    show: Boolean,
});

const emit = defineEmits(['close']);

// Detect tenant context for correct route resolution
const isTenant = computed(() => !!usePage().props.tenant);

const croppieRef = ref(null);
const croppieInstance = ref(null);
const imageSelected = ref(false);
const processing = ref(false);

const form = useForm({
    image: null,
});

const allowedProfileImageTypes = [
    'image/png',
    'image/jpeg',
    'image/jpg',
    'image/gif',
    'image/webp',
    'image/svg+xml',
];

const onFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        // Validate file type
        if (!allowedProfileImageTypes.includes(file.type)) {
            Swal.fire({
                icon: 'error',
                title: 'Invalid File',
                text: 'Please select PNG, JPG/JPEG, GIF, WEBP, or SVG image.',
                confirmButtonColor: '#2B7CB3',
            });
            e.target.value = '';
            return;
        }

        // Validate file size (max 5MB)
        if (file.size > 5 * 1024 * 1024) {
            Swal.fire({
                icon: 'error',
                title: 'File Too Large',
                text: 'Please select an image smaller than 5MB.',
                confirmButtonColor: '#2B7CB3',
            });
            e.target.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = (event) => {
            imageSelected.value = true;
            nextTick(() => {
                initializeCroppie(event.target.result);
            });
        };
        reader.onerror = () => {
            Swal.fire({
                icon: 'error',
                title: 'Cannot Read Image',
                text: 'Failed to read the selected image. Please try a different file.',
                confirmButtonColor: '#2B7CB3',
            });
            e.target.value = '';
        };
        reader.readAsDataURL(file);
    }
};

const initializeCroppie = (url) => {
    if (croppieInstance.value) {
        croppieInstance.value.destroy();
    }

    try {
        croppieInstance.value = new Croppie(croppieRef.value, {
            viewport: { width: 200, height: 200, type: 'square' },
            boundary: { width: 300, height: 300 },
            showZoomer: true,
            enableOrientation: true,
        });

        croppieInstance.value.bind({
            url: url,
        });
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Cannot Read Image',
            text: 'This image format is not supported. Please try a different image.',
            confirmButtonColor: '#2B7CB3',
        });
        imageSelected.value = false;
    }
};

const save = () => {
    if (!croppieInstance.value) return;

    processing.value = true;
    croppieInstance.value.result({
        type: 'base64',
        size: 'viewport',
        format: 'jpeg',
        quality: 0.9,
    }).then((base64) => {
        form.image = base64;
        form.post(route(isTenant.value ? 'tenant.profile.update-picture' : 'profile.update-picture'), {
            preserveScroll: true,
            onSuccess: () => {
                Swal.fire({
                    icon: 'success',
                    title: 'Profile Picture Updated',
                    text: 'Your profile picture has been updated successfully.',
                    confirmButtonColor: '#2B7CB3',
                    timer: 2000,
                    timerProgressBar: true,
                });
                close();
                processing.value = false;
            },
            onError: () => {
                processing.value = false;
                Swal.fire({
                    icon: 'error',
                    title: 'Upload Failed',
                    text: 'Could not update your profile picture. Please try again.',
                    confirmButtonColor: '#2B7CB3',
                });
            },
        });
    }).catch(() => {
        processing.value = false;
        Swal.fire({
            icon: 'error',
            title: 'Cannot Read Image',
            text: 'Failed to process the image. This image format may not be supported.',
            confirmButtonColor: '#2B7CB3',
        });
    });
};

const close = () => {
    imageSelected.value = false;
    if (croppieInstance.value) {
        croppieInstance.value.destroy();
        croppieInstance.value = null;
    }
    emit('close');
};

watch(() => props.show, (showing) => {
    if (!showing) {
        imageSelected.value = false;
    }
});
</script>

<template>
    <Modal :show="show" @close="close" max-width="md">
        <div class="p-6">
            <h2 class="text-lg font-black text-base-content uppercase tracking-widest mb-4">
                Update Profile Picture
            </h2>

            <div v-show="!imageSelected" class="flex flex-col items-center justify-center border-2 border-dashed border-base-300 rounded-2xl p-12 transition-all hover:border-primary/50 bg-base-200/30">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 text-base-content/20 mb-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM18.75 10.5h.008v.008h-.008V10.5Z" />
                </svg>
                <p class="text-xs font-bold text-base-content/40 uppercase tracking-widest mb-4">Select an image to crop</p>
                <input 
                    type="file" 
                    class="hidden" 
                    id="profile-picture-input" 
                    accept="image/png,image/jpeg,image/jpg,image/gif,image/webp,image/svg+xml"
                    @change="onFileChange"
                />
                <label 
                    for="profile-picture-input"
                    class="btn btn-primary btn-sm rounded-xl px-6"
                >
                    Choose Image
                </label>
            </div>

            <div v-show="imageSelected" class="space-y-4">
                <div ref="croppieRef" class="mx-auto rounded-xl overflow-hidden shadow-inner bg-base-200"></div>
                <div class="flex justify-center">
                    <button 
                        @click="imageSelected = false" 
                        class="text-[10px] font-black uppercase tracking-widest text-base-content/30 hover:text-primary transition-colors"
                    >
                        Change Image
                    </button>
                </div>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <SecondaryButton @click="close" class="rounded-xl px-6">
                    Cancel
                </SecondaryButton>

                <PrimaryButton 
                    :class="{ 'opacity-25': processing }" 
                    :disabled="processing || !imageSelected"
                    @click="save"
                    class="rounded-xl px-8"
                >
                    Save Changes
                </PrimaryButton>
            </div>
        </div>
    </Modal>
</template>

<style>
.cr-boundary {
    border-radius: 1rem;
}
.cr-viewport {
    border-radius: 1rem;
    box-shadow: 0 0 0 2000px rgba(0, 0, 0, 0.4);
    border: 2px solid white;
}
.cr-slider {
    @apply mt-6 block w-full appearance-none bg-base-300 h-1.5 rounded-full outline-none;
}
.cr-slider::-webkit-slider-thumb {
    @apply appearance-none w-4 h-4 rounded-full bg-primary cursor-pointer shadow-lg transition-transform hover:scale-110;
}
</style>
