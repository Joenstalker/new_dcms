<script setup>
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';

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

// Store object URLs for real-time preview without altering the form payload
const logoPreviews = ref({
    logo: null,
    logo_login: null,
    logo_booking: null,
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
    if (file) {
        // Show loading state or immediate preview if possible
        if (logoPreviews.value[field]) {
            URL.revokeObjectURL(logoPreviews.value[field]);
        }
        logoPreviews.value[field] = URL.createObjectURL(file);

        // Perform client-side resizing if it's an image
        if (file.type.startsWith('image/')) {
            try {
                const resizedFile = await resizeImage(file);
                props.form[field] = resizedFile;
                
                // Update preview to the resized version for accuracy
                URL.revokeObjectURL(logoPreviews.value[field]);
                logoPreviews.value[field] = URL.createObjectURL(resizedFile);
            } catch (error) {
                console.error('Image resize error:', error.message);
                // Fall back to original file if resizing fails
                props.form[field] = file;
            }
        } else {
            props.form[field] = file;
        }
    }
};

const getLogoUrl = (path, field) => {
    if (logoPreviews.value[field]) {
        return logoPreviews.value[field];
    }
    if (!path) return null;
    
    // Handle Base64 data URLs directly (Support for Database-Only Isolation)
    if (path.startsWith('data:image/')) return path;
    
    // If it's a full URL (from TenantBrandingService route), use as-is
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
        <section class="space-y-6 pt-6 border-t border-base-200" :class="{ 'opacity-50 pointer-events-none grayscale': !is_premium }">
            <div class="flex items-center justify-between">
                <h4 class="text-xs font-black text-primary uppercase tracking-[0.2em] flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-primary"></span>
                    Visual Identity
                </h4>
                <div v-if="!is_premium" class="badge badge-warning font-black uppercase tracking-tighter text-[8px] p-2 flex gap-1">
                    <span>👑</span> Upgrade to Pro for Custom Colors & Fonts
                </div>
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
                    <div class="w-16 h-16 rounded-2xl mb-4 flex items-center justify-center text-white text-2xl font-black shadow-lg" :style="{ backgroundColor: is_premium ? form.branding_color : '#2563eb' }">
                        Aa
                    </div>
                    <p class="text-xs font-black uppercase tracking-widest opacity-30 mb-2">Typography Preview</p>
                    <div class="space-y-2">
                        <p :class="form.font_family.header" class="text-sm font-black uppercase">Header Sample</p>
                        <p :class="form.font_family.names" class="text-lg font-bold">Dr. John Doe</p>
                        <p :class="form.font_family.general" class="text-xs opacity-60">The quick brown fox jumps over the lazy dog.</p>
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
                <div class="form-control bg-base-200/50 p-5 rounded-3xl border border-dashed border-base-300 hover:border-primary transition-colors cursor-pointer relative group">
                    <label class="label mb-2"><span class="label-text font-bold text-[9px] uppercase tracking-widest opacity-50">Header & Sidebar (Public)</span></label>
                    <div class="h-24 w-full flex flex-col items-center justify-center gap-2">
                        <img v-if="getLogoUrl(tenant.logo_path, 'logo')" :src="getLogoUrl(tenant.logo_path, 'logo')" class="h-full w-full object-contain" />
                        <div v-else class="text-2xl opacity-20">🏥</div>
                        <input type="file" @change="handleFileChange($event, 'logo')" class="absolute inset-0 opacity-0 cursor-pointer">
                    </div>
                    <div class="mt-3 text-center">
                        <span class="text-[9px] font-black uppercase tracking-widest text-primary opacity-0 group-hover:opacity-100 transition-opacity">Change Logo</span>
                    </div>
                </div>

                <!-- Login Modal Logo (PREMIUM) -->
                <div class="form-control bg-base-200/50 p-5 rounded-3xl border border-dashed transition-colors cursor-pointer relative group"
                    :class="[is_premium ? 'border-base-300 hover:border-primary' : 'border-base-200 opacity-40 grayscale pointer-events-none']">
                    <label class="label mb-2 font-bold text-[9px] uppercase tracking-widest opacity-50 space-x-2">
                        <span>Login Modal</span>
                        <span v-if="!is_premium">🏆</span>
                    </label>
                    <div class="h-24 w-full flex flex-col items-center justify-center gap-2">
                        <img v-if="getLogoUrl(tenant.logo_login_path, 'logo_login')" :src="getLogoUrl(tenant.logo_login_path, 'logo_login')" class="h-full w-full object-contain" />
                        <div v-else class="text-2xl opacity-20">🔐</div>
                        <input type="file" v-if="is_premium" @change="handleFileChange($event, 'logo_login')" class="absolute inset-0 opacity-0 cursor-pointer">
                    </div>
                </div>

                <!-- Booking Modal Logo (PREMIUM) -->
                <div class="form-control bg-base-200/50 p-5 rounded-3xl border border-dashed transition-colors cursor-pointer relative group"
                    :class="[is_premium ? 'border-base-300 hover:border-primary' : 'border-base-200 opacity-40 grayscale pointer-events-none']">
                    <label class="label mb-2 font-bold text-[9px] uppercase tracking-widest opacity-50 space-x-2">
                        <span>Booking Modal</span>
                        <span v-if="!is_premium">🏆</span>
                    </label>
                    <div class="h-24 w-full flex flex-col items-center justify-center gap-2">
                        <img v-if="getLogoUrl(tenant.logo_booking_path, 'logo_booking')" :src="getLogoUrl(tenant.logo_booking_path, 'logo_booking')" class="h-full w-full object-contain" />
                        <div v-else class="text-2xl opacity-20">📅</div>
                        <input type="file" v-if="is_premium" @change="handleFileChange($event, 'logo_booking')" class="absolute inset-0 opacity-0 cursor-pointer">
                    </div>
                </div>
            </div>
            <p v-if="!is_premium" class="text-[9px] text-center font-black uppercase text-secondary">Upgrade to Pro to customize Login & Booking portals</p>
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
