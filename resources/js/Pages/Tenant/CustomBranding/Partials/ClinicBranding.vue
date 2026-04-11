<script setup>
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { brandingState } from '@/States/brandingState';

const props = defineProps({
    form: {
        type: Object,
        required: true
    },
    tenant: {
        type: Object,
        required: true
    },
    is_premium: {
        type: Boolean,
        default: false
    }
});

const fontOptions = [
    { name: 'Inter (Default)', value: 'font-sans' },
    { name: 'Roboto', value: 'font-roboto' },
    { name: 'Poppins', value: 'font-poppins' },
    { name: 'Montserrat', value: 'font-montserrat' },
    { name: 'Open Sans', value: 'font-open-sans' },
];

// Track upload state per logo field
const uploading = reactive({
    logo: false,
    logo_login: false,
    logo_booking: false,
    portal_background: false,
});

// Track object URLs for live preview
const logoPreviews = ref({
    logo: null,
    logo_login: null,
    logo_booking: null,
    portal_background: null,
});

// Store the confirmed server URL for each logo (to use after upload)
const logoUrls = ref({
    logo: null,
    logo_login: null,
    logo_booking: null,
    portal_background: null,
});

const resizeImage = (file, maxWidth = 800, maxHeight = 800) => {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onerror = () => reject(new Error('Failed to read the file.'));
        reader.onload = (e) => {
            const img = new Image();
            img.onerror = () => reject(new Error('Cannot read image: the file may be corrupted or in an unsupported format.'));
            img.onload = () => {
                const canvas = document.createElement('canvas');
                let width = img.width;
                let height = img.height;

                if (width > maxWidth || height > maxHeight) {
                    if (width > height) {
                        height *= maxWidth / width;
                        width = maxWidth;
                    } else {
                        width *= maxHeight / height;
                        height = maxHeight;
                    }
                }

                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);

                canvas.toBlob((blob) => {
                    if (!blob) {
                        reject(new Error('Failed to process the image.'));
                        return;
                    }
                    const resizedFile = new File([blob], file.name, {
                        type: file.type,
                        lastModified: Date.now(),
                    });
                    resolve(resizedFile);
                }, file.type, 0.9);
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    });
};

const handleFileChange = async (e, field) => {
    const file = e.target.files[0];
    if (!file) return;

    // Show local preview immediately
    if (logoPreviews.value[field]) {
        URL.revokeObjectURL(logoPreviews.value[field]);
    }
    logoPreviews.value[field] = URL.createObjectURL(file);

    uploading[field] = true;

    try {
        // Resize client-side if image
        let uploadFile = file;
        if (file.type.startsWith('image/')) {
            try {
                uploadFile = await resizeImage(file);
                // Update preview to resized version
                URL.revokeObjectURL(logoPreviews.value[field]);
                logoPreviews.value[field] = URL.createObjectURL(uploadFile);
            } catch (resizeErr) {
                console.warn('Resize failed, uploading original:', resizeErr.message);
            }
        }

        // Upload via dedicated endpoint — NOT through the main form
        const formData = new FormData();
        formData.append('field', field);
        formData.append('image', uploadFile);

        const response = await fetch('/settings/logo', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Accept': 'application/json',
            },
        });

        const result = await response.json();

        if (!response.ok) {
            throw new Error(result.error || 'Upload failed');
        }

        // Store the server URL and update preview
        logoUrls.value[field] = result.url;
        if (logoPreviews.value[field]) {
            URL.revokeObjectURL(logoPreviews.value[field]);
        }
        logoPreviews.value[field] = result.url;

        if (field === 'portal_background') {
            // Auto-switch to image mode and apply instantly in layout preview.
            props.form.portal_background_type = 'image';
            brandingState.setPortalBackgroundType('image');
            brandingState.setPortalBackgroundImage(result.url);
        }

        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: `${fieldLabel(field)} uploaded successfully!`,
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true,
        });

    } catch (error) {
        console.error('Logo upload error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Upload Failed',
            text: error.message || 'Could not upload the logo. Please try again.',
        });
        // Revert preview on error
        if (logoPreviews.value[field]) {
            URL.revokeObjectURL(logoPreviews.value[field]);
            logoPreviews.value[field] = null;
        }
    } finally {
        uploading[field] = false;
        // Reset file input so same file can be re-selected
        e.target.value = '';
    }
};

const handleDeleteLogo = async (field) => {
    const result = await Swal.fire({
        title: 'Remove Logo?',
        text: `Are you sure you want to remove the ${fieldLabel(field)}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Yes, remove it',
    });

    if (!result.isConfirmed) return;

    try {
        const response = await fetch('/settings/logo', {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ field }),
        });

        if (!response.ok) {
            const data = await response.json();
            throw new Error(data.error || 'Delete failed');
        }

        // Clear preview
        if (logoPreviews.value[field]) {
            URL.revokeObjectURL(logoPreviews.value[field]);
            logoPreviews.value[field] = null;
        }
        logoUrls.value[field] = null;

        if (field === 'portal_background') {
            brandingState.setPortalBackgroundImage(null);
            props.form.portal_background_type = 'color';
            brandingState.setPortalBackgroundType('color');
        }

        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: `${fieldLabel(field)} removed`,
            showConfirmButton: false,
            timer: 2000,
        });

    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message || 'Could not remove the logo.',
        });
    }
};

const fieldLabel = (field) => ({
    logo: 'Header Logo',
    logo_login: 'Login Logo',
    logo_booking: 'Booking Logo',
    portal_background: 'Portal Background',
}[field] || field);

const getLogoUrl = (path, field) => {
    // Priority: local preview > uploaded server URL > tenant prop path
    if (logoPreviews.value[field]) {
        return logoPreviews.value[field];
    }
    if (logoUrls.value[field]) {
        return logoUrls.value[field];
    }
    if (!path) return null;
    if (path.startsWith('data:image/')) return path;
    if (path.startsWith('http://') || path.startsWith('https://')) return path;
    return '/tenant-storage/' + path;
};
</script>

<template>
    <div class="space-y-8 animate-fade-in">
        <!-- Clinic Profile Info -->
        <section class="space-y-6">
            <h4 class="text-xs font-black text-primary uppercase tracking-[0.2em] flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-primary"></span>
                General Information
            </h4>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="form-control w-full">
                    <label class="label"><span class="label-text font-bold text-[10px] uppercase tracking-widest opacity-50">Clinic Name</span></label>
                    <input type="text" v-model="form.clinic_name" required class="input input-bordered w-full rounded-2xl bg-base-100 focus:border-primary border-base-300">
                </div>

                <div class="form-control w-full">
                    <label class="label"><span class="label-text font-bold text-[10px] uppercase tracking-widest opacity-50">Contact Email</span></label>
                    <input type="email" v-model="form.email" class="input input-bordered w-full rounded-2xl bg-base-100 focus:border-primary border-base-300">
                </div>

                <div class="form-control w-full">
                    <label class="label"><span class="label-text font-bold text-[10px] uppercase tracking-widest opacity-50">Phone Number</span></label>
                    <input type="text" v-model="form.phone" class="input input-bordered w-full rounded-2xl bg-base-100 focus:border-primary border-base-300">
                </div>

                <div class="form-control w-full">
                    <label class="label"><span class="label-text font-bold text-[10px] uppercase tracking-widest opacity-50">Business Address</span></label>
                    <input type="text" v-model="form.address" class="input input-bordered w-full rounded-2xl bg-base-100 focus:border-primary border-base-300">
                </div>
            </div>
        </section>

        <!-- Visual Branding -->
        <section class="space-y-6 pt-6 border-t border-base-200">
            <div class="flex items-center justify-between">
                <h4 class="text-xs font-black text-primary uppercase tracking-[0.2em] flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-primary"></span>
                    Visual Identity
                </h4>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Colors & Fonts -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="form-control">
                        <label class="label"><span class="label-text font-bold text-[10px] uppercase tracking-widest opacity-50">Brand Primary Color</span></label>
                        <div class="flex items-center gap-4 bg-base-200 p-3 rounded-2xl border border-base-300">
                            <input type="color" v-model="form.branding_color" :disabled="!is_premium" class="w-12 h-12 rounded-xl border-none cursor-pointer bg-transparent">
                            <input type="text" v-model="form.branding_color" :disabled="!is_premium" class="input input-sm border-none bg-transparent font-mono text-xs w-full focus:ring-0 uppercase">
                        </div>
                    </div>

                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-bold text-[10px] uppercase tracking-widest opacity-50">Support Chat Bottom Offset (px)</span>
                        </label>
                        <div class="flex items-center gap-3 bg-base-200 p-3 rounded-2xl border border-base-300">
                            <input
                                type="range"
                                min="16"
                                max="160"
                                step="1"
                                v-model.number="form.support_chat_bottom_offset"
                                class="range range-primary range-sm w-full"
                            >
                            <input
                                type="number"
                                min="16"
                                max="160"
                                step="1"
                                v-model.number="form.support_chat_bottom_offset"
                                class="input input-sm w-20 text-center font-mono"
                            >
                        </div>
                        <p class="text-[10px] text-base-content/50 mt-1">
                            Controls vertical position of the floating support bubble for this tenant.
                        </p>
                    </div>

                    <div class="space-y-4 p-4 rounded-2xl border border-base-300 bg-base-200/40">
                        <div class="form-control">
                            <label class="label"><span class="label-text font-bold text-[10px] uppercase tracking-widest opacity-50">Portal Background Type</span></label>
                            <select v-model="form.portal_background_type" class="select select-bordered w-full rounded-2xl border-base-300 bg-base-100 focus:border-primary">
                                <option value="color">Solid Color</option>
                                <option value="image">Background Image</option>
                            </select>
                        </div>

                        <div v-if="form.portal_background_type === 'color'" class="form-control">
                            <label class="label"><span class="label-text font-bold text-[10px] uppercase tracking-widest opacity-50">Portal Background Color</span></label>
                            <div class="flex items-center gap-4 bg-base-100 p-3 rounded-2xl border border-base-300">
                                <input type="color" v-model="form.portal_background_color" :disabled="!is_premium" class="w-12 h-12 rounded-xl border-none cursor-pointer bg-transparent">
                                <input type="text" v-model="form.portal_background_color" :disabled="!is_premium" class="input input-sm border-none bg-transparent font-mono text-xs w-full focus:ring-0 uppercase">
                            </div>
                        </div>

                        <div v-else class="form-control bg-base-100 p-4 rounded-2xl border border-dashed border-base-300 hover:border-primary transition-colors relative group"
                            :class="{ 'pointer-events-none': uploading.portal_background }">
                            <label class="label mb-2"><span class="label-text font-bold text-[9px] uppercase tracking-widest opacity-50">Portal Background Image</span></label>
                            <div class="h-32 w-full flex flex-col items-center justify-center gap-2 relative rounded-xl overflow-hidden bg-base-200/60">
                                <div v-if="uploading.portal_background" class="absolute inset-0 flex items-center justify-center bg-base-200/80 rounded-xl z-10">
                                    <span class="loading loading-spinner loading-md text-primary"></span>
                                </div>
                                <img v-if="getLogoUrl(tenant.portal_background_image, 'portal_background')" :src="getLogoUrl(tenant.portal_background_image, 'portal_background')" class="h-full w-full object-cover" />
                                <div v-else class="text-2xl opacity-20">🖼️</div>
                                <input type="file" accept="image/*" @change="handleFileChange($event, 'portal_background')" class="absolute inset-0 opacity-0 cursor-pointer" :disabled="uploading.portal_background">
                            </div>
                            <div class="mt-3 text-center flex items-center justify-center gap-3">
                                <span class="text-[9px] font-black uppercase tracking-widest text-primary opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer">
                                    {{ getLogoUrl(tenant.portal_background_image, 'portal_background') ? 'Replace' : 'Upload' }}
                                </span>
                                <button v-if="getLogoUrl(tenant.portal_background_image, 'portal_background')"
                                    @click.stop="handleDeleteLogo('portal_background')"
                                    class="text-[9px] font-black uppercase tracking-widest text-error opacity-0 group-hover:opacity-100 transition-opacity hover:underline">
                                    Remove
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-control">
                            <label class="label"><span class="label-text font-bold text-[10px] uppercase tracking-widest opacity-50">Header Typography</span></label>
                            <select v-model="form.font_family.header" :disabled="!is_premium" class="select select-bordered w-full rounded-2xl border-base-300 bg-base-100 focus:border-primary">
                                <option v-for="font in fontOptions" :key="font.value" :value="font.value" :class="font.value">
                                    {{ font.name }}
                                </option>
                            </select>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-bold text-[10px] uppercase tracking-widest opacity-50">Sidebar Navigation</span></label>
                            <select v-model="form.font_family.sidebar" :disabled="!is_premium" class="select select-bordered w-full rounded-2xl border-base-300 bg-base-100 focus:border-primary">
                                <option v-for="font in fontOptions" :key="font.value" :value="font.value" :class="font.value">
                                    {{ font.name }}
                                </option>
                            </select>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-bold text-[10px] uppercase tracking-widest opacity-50">Patient & Staff Names</span></label>
                            <select v-model="form.font_family.names" :disabled="!is_premium" class="select select-bordered w-full rounded-2xl border-base-300 bg-base-100 focus:border-primary">
                                <option v-for="font in fontOptions" :key="font.value" :value="font.value" :class="font.value">
                                    {{ font.name }}
                                </option>
                            </select>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-bold text-[10px] uppercase tracking-widest opacity-50">General Portal Text</span></label>
                            <select v-model="form.font_family.general" :disabled="!is_premium" class="select select-bordered w-full rounded-2xl border-base-300 bg-base-100 focus:border-primary">
                                <option v-for="font in fontOptions" :key="font.value" :value="font.value" :class="font.value">
                                    {{ font.name }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Preview Area -->
                <div class="bg-base-200 rounded-3xl p-6 border border-base-300 flex flex-col justify-center items-center text-center">
                    <div class="w-16 h-16 rounded-2xl mb-4 flex items-center justify-center text-white text-2xl font-black shadow-lg" :style="{ backgroundColor: form.branding_color }">
                        Aa
                    </div>
                    <p class="text-xs font-black uppercase tracking-widest opacity-30 mb-2">Typography Preview</p>
                    <div class="space-y-2">
                        <p :class="form.font_family.header" class="text-sm font-black uppercase">Header Sample</p>
                        <p :class="form.font_family.names" class="text-lg font-bold">Dr. John Doe</p>
                        <p :class="form.font_family.general" class="text-xs opacity-60">The quick brown fox jumps over the lazy dog.</p>
                    </div>
                    <div class="w-full rounded-2xl mt-4 border border-base-300 overflow-hidden">
                        <div
                            class="h-20"
                            :style="form.portal_background_type === 'image' && getLogoUrl(tenant.portal_background_image, 'portal_background')
                                ? { backgroundImage: `url(${getLogoUrl(tenant.portal_background_image, 'portal_background')})`, backgroundSize: 'cover', backgroundPosition: 'center' }
                                : { backgroundColor: form.portal_background_color || '#e5e7eb' }"
                        ></div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Multi-Logo Suite -->
        <section class="space-y-6 pt-6 border-t border-base-200">
            <h4 class="text-xs font-black text-primary uppercase tracking-[0.2em] flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-primary"></span>
                Logo Suite
            </h4>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                <!-- Main Header Logo (BASIC ALLOWED) -->
                <div class="form-control bg-base-200/50 p-5 rounded-3xl border border-dashed border-base-300 hover:border-primary transition-colors relative group"
                    :class="{ 'pointer-events-none': uploading.logo }">
                    <label class="label mb-2"><span class="label-text font-bold text-[9px] uppercase tracking-widest opacity-50">Header & Sidebar (Public)</span></label>
                    <div class="h-24 w-full flex flex-col items-center justify-center gap-2 relative">
                        <!-- Upload spinner overlay -->
                        <div v-if="uploading.logo" class="absolute inset-0 flex items-center justify-center bg-base-200/80 rounded-xl z-10">
                            <span class="loading loading-spinner loading-md text-primary"></span>
                        </div>
                        <img v-if="getLogoUrl(tenant.logo_path, 'logo')" :src="getLogoUrl(tenant.logo_path, 'logo')" class="h-full w-full object-contain" />
                        <div v-else class="text-2xl opacity-20">🏥</div>
                        <input type="file" accept="image/*" @change="handleFileChange($event, 'logo')" class="absolute inset-0 opacity-0 cursor-pointer" :disabled="uploading.logo">
                    </div>
                    <div class="mt-3 text-center flex items-center justify-center gap-3">
                        <span class="text-[9px] font-black uppercase tracking-widest text-primary opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer">
                            {{ getLogoUrl(tenant.logo_path, 'logo') ? 'Replace' : 'Upload' }}
                        </span>
                        <button v-if="getLogoUrl(tenant.logo_path, 'logo')" 
                            @click.stop="handleDeleteLogo('logo')" 
                            class="text-[9px] font-black uppercase tracking-widest text-error opacity-0 group-hover:opacity-100 transition-opacity hover:underline">
                            Remove
                        </button>
                    </div>
                </div>

                <!-- Login Modal Logo -->
                <div class="form-control bg-base-200/50 p-5 rounded-3xl border border-dashed border-base-300 hover:border-primary transition-colors relative group cursor-pointer"
                    :class="{ 'pointer-events-none': uploading.logo_login }">
                    <label class="label mb-2 font-bold text-[9px] uppercase tracking-widest opacity-50 space-x-2">
                        <span>Login Modal</span>
                    </label>
                    <div class="h-24 w-full flex flex-col items-center justify-center gap-2 relative">
                        <div v-if="uploading.logo_login" class="absolute inset-0 flex items-center justify-center bg-base-200/80 rounded-xl z-10">
                            <span class="loading loading-spinner loading-md text-primary"></span>
                        </div>
                        <img v-if="getLogoUrl(tenant.logo_login_path, 'logo_login')" :src="getLogoUrl(tenant.logo_login_path, 'logo_login')" class="h-full w-full object-contain" />
                        <div v-else class="text-2xl opacity-20">🔐</div>
                        <input type="file" accept="image/*" @change="handleFileChange($event, 'logo_login')" class="absolute inset-0 opacity-0 cursor-pointer" :disabled="uploading.logo_login">
                    </div>
                    <div class="mt-3 text-center flex items-center justify-center gap-3">
                        <span class="text-[9px] font-black uppercase tracking-widest text-primary opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer">
                            {{ getLogoUrl(tenant.logo_login_path, 'logo_login') ? 'Replace' : 'Upload' }}
                        </span>
                        <button v-if="getLogoUrl(tenant.logo_login_path, 'logo_login')" 
                            @click.stop="handleDeleteLogo('logo_login')" 
                            class="text-[9px] font-black uppercase tracking-widest text-error opacity-0 group-hover:opacity-100 transition-opacity hover:underline">
                            Remove
                        </button>
                    </div>
                </div>

                <!-- Booking Modal Logo -->
                <div class="form-control bg-base-200/50 p-5 rounded-3xl border border-dashed border-base-300 hover:border-primary transition-colors relative group cursor-pointer"
                    :class="{ 'pointer-events-none': uploading.logo_booking }">
                    <label class="label mb-2 font-bold text-[9px] uppercase tracking-widest opacity-50 space-x-2">
                        <span>Booking Modal</span>
                    </label>
                    <div class="h-24 w-full flex flex-col items-center justify-center gap-2 relative">
                        <div v-if="uploading.logo_booking" class="absolute inset-0 flex items-center justify-center bg-base-200/80 rounded-xl z-10">
                            <span class="loading loading-spinner loading-md text-primary"></span>
                        </div>
                        <img v-if="getLogoUrl(tenant.logo_booking_path, 'logo_booking')" :src="getLogoUrl(tenant.logo_booking_path, 'logo_booking')" class="h-full w-full object-contain" />
                        <div v-else class="text-2xl opacity-20">📅</div>
                        <input type="file" accept="image/*" @change="handleFileChange($event, 'logo_booking')" class="absolute inset-0 opacity-0 cursor-pointer" :disabled="uploading.logo_booking">
                    </div>
                    <div class="mt-3 text-center flex items-center justify-center gap-3">
                        <span class="text-[9px] font-black uppercase tracking-widest text-primary opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer">
                            {{ getLogoUrl(tenant.logo_booking_path, 'logo_booking') ? 'Replace' : 'Upload' }}
                        </span>
                        <button v-if="getLogoUrl(tenant.logo_booking_path, 'logo_booking')" 
                            @click.stop="handleDeleteLogo('logo_booking')" 
                            class="text-[9px] font-black uppercase tracking-widest text-error opacity-0 group-hover:opacity-100 transition-opacity hover:underline">
                            Remove
                        </button>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;700;900&family=Poppins:wght@400;700;900&family=Montserrat:wght@400;700;900&family=Open+Sans:wght@400;700;800&display=swap');

.font-roboto { font-family: 'Roboto', sans-serif; }
.font-poppins { font-family: 'Poppins', sans-serif; }
.font-montserrat { font-family: 'Montserrat', sans-serif; }
.font-open-sans { font-family: 'Open Sans', sans-serif; }
</style>
