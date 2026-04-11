<script setup>
import { computed, ref, reactive } from 'vue';
import Swal from 'sweetalert2';

const props = defineProps({
    form: {
        type: Object,
        required: true
    },
    tenant: {
        type: Object,
        default: () => ({})
    },
    is_premium: {
        type: Boolean,
        default: false
    }
});

const uploading = reactive({
    services: false,
    team: false,
    contact: false,
});

// Multi-stage initialization to ensure deeply nested objects exist for v-model
const initConfig = () => {
    if (!props.form.landing_page_config) {
        props.form.landing_page_config = {};
    }
    
    if (!props.form.landing_page_config.background_color) {
        props.form.landing_page_config.background_color = '#ffffff';
    }
    
    if (!props.form.landing_page_config.text_primary) {
        props.form.landing_page_config.text_primary = '#111827';
    }
    
    if (!props.form.landing_page_config.text_secondary) {
        props.form.landing_page_config.text_secondary = '#4b5563';
    }
    
    if (!props.form.landing_page_config.sections) {
        props.form.landing_page_config.sections = {
            services: { active: true, image: null },
            team: { active: true, image: null },
            contact: { active: true, image: null }
        };
    } else {
        // Ensure individual sections exist if sections object exists but is incomplete
        ['services', 'team', 'contact'].forEach(sec => {
            if (!props.form.landing_page_config.sections[sec]) {
                props.form.landing_page_config.sections[sec] = { active: true, image: null };
            }
        });
    }
};

initConfig();

const resizeImage = (file, maxWidth = 1600, maxHeight = 1600) => {
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
                    resolve(new File([blob], file.name, {
                        type: file.type,
                        lastModified: Date.now(),
                    }));
                }, file.type, 0.9);
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    });
};

const handleFileChange = async (e, section) => {
    const file = e.target.files[0];
    if (!file) return;

    uploading[section] = true;

    try {
        let uploadFile = file;
        if (file.type.startsWith('image/')) {
            try {
                uploadFile = await resizeImage(file, 1600, 1600);
            } catch (resizeErr) {
                console.warn('Resize failed, uploading original:', resizeErr.message);
            }
        }

        const formData = new FormData();
        const fieldMap = {
            services: 'landing_services',
            team: 'landing_team',
            contact: 'landing_contact'
        };
        formData.append('field', fieldMap[section]);
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

        // Apply newly generated streaming route URL
        props.form.landing_page_config.sections[section].image = result.url;

        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: `Image uploaded successfully!`,
            showConfirmButton: false,
            timer: 2000,
        });

    } catch (error) {
        console.error('Image upload error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Upload Failed',
            text: error.message || 'Could not upload the image. Please try again.',
        });
    } finally {
        uploading[section] = false;
        e.target.value = '';
    }
};

const getPreviewUrl = (section) => {
    const imgData = props.form.landing_page_config?.sections?.[section]?.image;
    if (!imgData) {
        // High-quality dental/medical defaults
        const defaults = {
            services: '/images/branding/defaults/services.png',
            team: '/images/branding/defaults/team.png',
            contact: '/images/branding/defaults/contact.png'
        };
        return defaults[section];
    }
    
    // Support all variations of previously or newly saved imagery
    if (imgData.startsWith('data:image/') || imgData.startsWith('http') || imgData.startsWith('/settings/')) {
        return imgData;
    }
    return '/tenant-storage/' + imgData;
};
</script>

<template>
    <div class="space-y-10 animate-fade-in">
        <!-- Global Styling -->
        <section class="space-y-6">
            <h4 class="text-xs font-black text-primary uppercase tracking-[0.2em] flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-primary"></span>
                Canvas Styling
            </h4>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <div class="form-control">
                        <label class="label"><span class="label-text font-bold text-[10px] uppercase tracking-widest opacity-50">Landing Background Color</span></label>
                        <div class="flex items-center gap-4 bg-base-200 p-3 rounded-2xl border border-base-300 hover:border-primary transition-colors">
                            <input type="color" v-model="form.landing_page_config.background_color" class="w-10 h-10 rounded-xl border-none cursor-pointer bg-transparent">
                            <input type="text" v-model="form.landing_page_config.background_color" class="input input-sm border-none bg-transparent font-mono text-xs w-full focus:ring-0 uppercase">
                        </div>
                    </div>
                    
                    <div class="form-control">
                        <label class="label"><span class="label-text font-bold text-[10px] uppercase tracking-widest opacity-50">Heading Text Color</span></label>
                        <div class="flex items-center gap-4 bg-base-200 p-3 rounded-2xl border border-base-300 hover:border-primary transition-colors">
                            <input type="color" v-model="form.landing_page_config.text_primary" class="w-10 h-10 rounded-xl border-none cursor-pointer bg-transparent">
                            <input type="text" v-model="form.landing_page_config.text_primary" class="input input-sm border-none bg-transparent font-mono text-xs w-full focus:ring-0 uppercase">
                        </div>
                    </div>

                    <div class="form-control">
                        <label class="label"><span class="label-text font-bold text-[10px] uppercase tracking-widest opacity-50">Paragraph Text Color</span></label>
                        <div class="flex items-center gap-4 bg-base-200 p-3 rounded-2xl border border-base-300 hover:border-primary transition-colors">
                            <input type="color" v-model="form.landing_page_config.text_secondary" class="w-10 h-10 rounded-xl border-none cursor-pointer bg-transparent">
                            <input type="text" v-model="form.landing_page_config.text_secondary" class="input input-sm border-none bg-transparent font-mono text-xs w-full focus:ring-0 uppercase">
                        </div>
                    </div>
                </div>
                
                <div class="bg-base-200 rounded-3xl p-6 border border-base-300 flex flex-col justify-center items-center h-full">
                    <div class="w-full max-w-[200px] rounded-xl border border-base-300 shadow-inner overflow-hidden p-4" :style="{ backgroundColor: form.landing_page_config.background_color }">
                        <div class="w-2/3 h-5 mb-3 rounded-md font-black text-[10px] flex items-center shadow-sm" :style="{ color: form.landing_page_config.text_primary }">Hero Title</div>
                        <div class="space-y-1.5 mb-4">
                            <div class="w-full h-2 rounded-sm" :style="{ backgroundColor: form.landing_page_config.text_secondary, opacity: 0.8 }"></div>
                            <div class="w-4/5 h-2 rounded-sm" :style="{ backgroundColor: form.landing_page_config.text_secondary, opacity: 0.8 }"></div>
                            <div class="w-5/6 h-2 rounded-sm" :style="{ backgroundColor: form.landing_page_config.text_secondary, opacity: 0.8 }"></div>
                        </div>
                        <div class="flex gap-2">
                            <div class="w-1/3 h-8 rounded-lg opacity-20" :style="{ backgroundColor: form.landing_page_config.text_primary }"></div>
                            <div class="w-1/3 h-8 rounded-lg opacity-20" :style="{ backgroundColor: form.landing_page_config.text_primary }"></div>
                            <div class="w-1/3 h-8 rounded-lg opacity-20" :style="{ backgroundColor: form.landing_page_config.text_primary }"></div>
                        </div>
                    </div>
                    <p class="mt-4 text-[9px] font-black uppercase tracking-widest opacity-30 text-center">Live Contrast Preview</p>
                </div>
            </div>
        </section>

        <!-- Section Customization -->
        <section class="space-y-6 pt-6 border-t border-base-200">
            <h4 class="text-xs font-black text-primary uppercase tracking-[0.2em] flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-primary"></span>
                Section Designer
            </h4>

            <div class="space-y-4">
                <!-- Hero & About Content Section -->
                <div class="p-6 bg-base-100 rounded-3xl border border-base-300 space-y-4 shadow-sm">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-3">
                            <span class="text-xl">✍️</span>
                            <span class="text-xs font-black uppercase tracking-widest text-base-content">Hero & Content</span>
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                         <div class="form-control w-full">
                            <label class="label"><span class="label-text font-bold text-[10px] uppercase tracking-widest opacity-50">Main Hero Title</span></label>
                            <input type="text" v-model="form.hero_title" placeholder="e.g. Expert Dental Care" class="input input-bordered w-full rounded-2xl bg-base-100 focus:border-primary border-base-300">
                        </div>
                        <div class="form-control w-full">
                            <label class="label"><span class="label-text font-bold text-[10px] uppercase tracking-widest opacity-50">Hero Subtitle</span></label>
                            <textarea v-model="form.hero_subtitle" rows="2" placeholder="e.g. Providing high-quality care..." class="textarea textarea-bordered w-full rounded-2xl bg-base-100 focus:border-primary border-base-300"></textarea>
                        </div>
                        <div class="form-control w-full mt-4">
                            <label class="label"><span class="label-text font-bold text-[10px] uppercase tracking-widest opacity-50">About Us Description</span></label>
                            <textarea v-model="form.about_us_description" rows="3" placeholder="e.g. Our clinic is dedicated to..." class="textarea textarea-bordered w-full rounded-2xl bg-base-100 focus:border-primary border-base-300"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Services Section -->
                <div class="p-6 bg-base-100 rounded-3xl border border-base-300 space-y-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="text-xl">🦷</span>
                            <span class="text-xs font-black uppercase tracking-widest text-base-content">Services Section</span>
                        </div>
                        <input type="checkbox" v-model="form.landing_page_config.sections.services.active" class="toggle toggle-primary toggle-md">
                    </div>
                    
                    <div v-if="form.landing_page_config.sections.services.active" class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                        <div class="form-control">
                            <label class="label"><span class="label-text font-bold text-[9px] uppercase tracking-widest opacity-40">Feature Image</span></label>
                            <div class="h-32 bg-base-200 rounded-2xl border border-dashed border-base-300 flex items-center justify-center relative group overflow-hidden" :class="{ 'pointer-events-none': uploading.services }">
                                <div v-if="uploading.services" class="absolute inset-0 flex items-center justify-center bg-base-200/80 z-10">
                                    <span class="loading loading-spinner text-primary"></span>
                                </div>
                                <img v-if="getPreviewUrl('services')" :src="getPreviewUrl('services')" class="w-full h-full object-cover">
                                <span v-else class="text-[10px] opacity-40 font-black uppercase tracking-widest">Click to upload image</span>
                                <input type="file" accept="image/*" @change="handleFileChange($event, 'services')" class="absolute inset-0 opacity-0 cursor-pointer" :disabled="uploading.services">
                            </div>
                        </div>
                        <div class="flex items-center">
                            <p class="text-[10px] text-base-content/50 italic">This image will be displayed alongside your specialized services on the landing page.</p>
                        </div>
                    </div>
                </div>

                <!-- Team Section -->
                <div class="p-6 bg-base-100 rounded-3xl border border-base-300 space-y-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="text-xl">👨‍⚕️</span>
                            <span class="text-xs font-black uppercase tracking-widest text-base-content">Our Team Section</span>
                        </div>
                        <input type="checkbox" v-model="form.landing_page_config.sections.team.active" class="toggle toggle-primary toggle-md">
                    </div>

                    <div v-if="form.landing_page_config.sections.team.active" class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                         <div class="form-control">
                            <label class="label"><span class="label-text font-bold text-[9px] uppercase tracking-widest opacity-40">Team Banner Image</span></label>
                            <div class="h-32 bg-base-200 rounded-2xl border border-dashed border-base-300 flex items-center justify-center relative group overflow-hidden" :class="{ 'pointer-events-none': uploading.team }">
                                <div v-if="uploading.team" class="absolute inset-0 flex items-center justify-center bg-base-200/80 z-10">
                                    <span class="loading loading-spinner text-primary"></span>
                                </div>
                                <img v-if="getPreviewUrl('team')" :src="getPreviewUrl('team')" class="w-full h-full object-cover">
                                <span v-else class="text-[10px] opacity-40 font-black uppercase tracking-widest">Click to upload image</span>
                                <input type="file" accept="image/*" @change="handleFileChange($event, 'team')" class="absolute inset-0 opacity-0 cursor-pointer" :disabled="uploading.team">
                            </div>
                        </div>
                        <div class="flex items-center">
                            <p class="text-[10px] text-base-content/50 italic">Upload a photo of your clinic team or staff in action to build trust with patients.</p>
                        </div>
                    </div>
                </div>

                 <!-- Contact Section -->
                 <div class="p-6 bg-base-100 rounded-3xl border border-base-300 space-y-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="text-xl">📞</span>
                            <span class="text-xs font-black uppercase tracking-widest text-base-content">Contact & Map Section</span>
                        </div>
                        <input type="checkbox" v-model="form.landing_page_config.sections.contact.active" class="toggle toggle-primary toggle-md">
                    </div>

                    <div v-if="form.landing_page_config.sections.contact.active" class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                         <div class="form-control">
                            <label class="label"><span class="label-text font-bold text-[9px] uppercase tracking-widest opacity-40">Contact Support Image</span></label>
                            <div class="h-32 bg-base-200 rounded-2xl border border-dashed border-base-300 flex items-center justify-center relative group overflow-hidden" :class="{ 'pointer-events-none': uploading.contact }">
                                <div v-if="uploading.contact" class="absolute inset-0 flex items-center justify-center bg-base-200/80 z-10">
                                    <span class="loading loading-spinner text-primary"></span>
                                </div>
                                <img v-if="getPreviewUrl('contact')" :src="getPreviewUrl('contact')" class="w-full h-full object-cover">
                                <span v-else class="text-[10px] opacity-40 font-black uppercase tracking-widest">Click to upload image</span>
                                <input type="file" accept="image/*" @change="handleFileChange($event, 'contact')" class="absolute inset-0 opacity-0 cursor-pointer" :disabled="uploading.contact">
                            </div>
                        </div>
                        <div class="flex items-center">
                            <p class="text-[10px] text-base-content/50 italic">This image appears in the contact section to make your clinic feel approachable.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>
