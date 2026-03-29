<script setup>
import { computed } from 'vue';

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

// Multi-stage initialization to ensure deeply nested objects exist for v-model
const initConfig = () => {
    if (!props.form.landing_page_config) {
        props.form.landing_page_config = {};
    }
    
    if (!props.form.landing_page_config.background_color) {
        props.form.landing_page_config.background_color = '#ffffff';
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

const handleFileChange = (e, section) => {
    const file = e.target.files[0];
    if (file) {
        // Convert to Base64 for dynamic saving as requested
        const reader = new FileReader();
        reader.onload = (event) => {
            props.form.landing_page_config.sections[section].image = event.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const getPreviewUrl = (section) => {
    const imgData = props.form.landing_page_config?.sections?.[section]?.image;
    if (imgData && typeof imgData === 'string' && imgData.startsWith('data:image')) {
        return imgData;
    }
    return imgData ? '/tenant-storage/' + imgData : null;
};
</script>

<template>
    <div class="space-y-10 animate-fade-in" :class="{ 'opacity-60 pointer-events-none grayscale': !is_premium }">
        <div v-if="!is_premium" class="bg-warning/10 p-6 rounded-[2rem] border border-warning/20 flex items-center justify-between pointer-events-auto grayscale-0 mb-6">
            <div class="flex items-center gap-4">
                 <span class="text-3xl">🚀</span>
                 <div>
                     <p class="font-black uppercase tracking-widest text-xs text-secondary">Premium Component</p>
                     <p class="text-sm opacity-70">The Landing Page Designer is reserved for Pro & Ultimate clinics.</p>
                 </div>
            </div>
            <button class="btn btn-secondary btn-sm rounded-xl font-black uppercase text-[9px] tracking-widest">Upgrade Now</button>
        </div>
        <!-- Global Styling -->
        <section class="space-y-6">
            <h4 class="text-xs font-black text-primary uppercase tracking-[0.2em] flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-primary"></span>
                Canvas Styling
            </h4>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="form-control">
                    <label class="label"><span class="label-text font-bold text-[10px] uppercase tracking-widest opacity-50">Landing Background Color</span></label>
                    <div class="flex items-center gap-4 bg-base-200 p-3 rounded-2xl border border-base-300">
                        <input type="color" v-model="form.landing_page_config.background_color" class="w-12 h-12 rounded-xl border-none cursor-pointer bg-transparent">
                        <input type="text" v-model="form.landing_page_config.background_color" class="input input-sm border-none bg-transparent font-mono text-xs w-full focus:ring-0 uppercase">
                    </div>
                </div>
                
                <div class="bg-base-200 rounded-3xl p-6 border border-base-300 flex flex-col justify-center items-center">
                    <div class="w-24 h-16 rounded-xl border border-base-300 shadow-inner overflow-hidden" :style="{ backgroundColor: form.landing_page_config.background_color }">
                        <div class="w-full h-4 bg-base-100/50 mb-2"></div>
                        <div class="flex gap-1 px-2">
                            <div class="w-1/3 h-8 bg-base-100/30 rounded-sm"></div>
                            <div class="w-1/3 h-8 bg-base-100/30 rounded-sm"></div>
                            <div class="w-1/3 h-8 bg-base-100/30 rounded-sm"></div>
                        </div>
                    </div>
                    <p class="mt-3 text-[9px] font-black uppercase tracking-widest opacity-30">Background Live Preview</p>
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
                            <div class="h-32 bg-base-200 rounded-2xl border border-dashed border-base-300 flex items-center justify-center relative group overflow-hidden">
                                <img v-if="getPreviewUrl('services')" :src="getPreviewUrl('services')" class="w-full h-full object-cover">
                                <span v-else class="text-[10px] opacity-40 font-black uppercase tracking-widest">Click to upload image</span>
                                <input type="file" @change="handleFileChange($event, 'services')" class="absolute inset-0 opacity-0 cursor-pointer">
                            </div>
                        </div>
                        <div class="flex items-center">
                            <p class="text-[10px] text-gray-400 italic">This image will be displayed alongside your specialized services on the landing page.</p>
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
                            <div class="h-32 bg-base-200 rounded-2xl border border-dashed border-base-300 flex items-center justify-center relative group overflow-hidden">
                                <img v-if="getPreviewUrl('team')" :src="getPreviewUrl('team')" class="w-full h-full object-cover">
                                <span v-else class="text-[10px] opacity-40 font-black uppercase tracking-widest">Click to upload image</span>
                                <input type="file" @change="handleFileChange($event, 'team')" class="absolute inset-0 opacity-0 cursor-pointer">
                            </div>
                        </div>
                        <div class="flex items-center">
                            <p class="text-[10px] text-gray-400 italic">Upload a photo of your clinic team or staff in action to build trust with patients.</p>
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
                            <div class="h-32 bg-base-200 rounded-2xl border border-dashed border-base-300 flex items-center justify-center relative group overflow-hidden">
                                <img v-if="getPreviewUrl('contact')" :src="getPreviewUrl('contact')" class="w-full h-full object-cover">
                                <span v-else class="text-[10px] opacity-40 font-black uppercase tracking-widest">Click to upload image</span>
                                <input type="file" @change="handleFileChange($event, 'contact')" class="absolute inset-0 opacity-0 cursor-pointer">
                            </div>
                        </div>
                        <div class="flex items-center">
                            <p class="text-[10px] text-gray-400 italic">This image appears in the contact section to make your clinic feel approachable.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>
