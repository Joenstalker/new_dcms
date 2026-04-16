<script setup>
import { ref, reactive } from 'vue';
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
    content: false,
    team: false,
    contact: false,
    heroBg: false,
    contentBg: false,
    servicesBg: false,
    teamBg: false,
    contactBg: false,
    manualCardImage: false,
});

const draggedManualCardIndex = ref(null);

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

    if (!props.form.landing_page_config.operating_hours_style || typeof props.form.landing_page_config.operating_hours_style !== 'object') {
        props.form.landing_page_config.operating_hours_style = {};
    }

    const operatingHoursDefaults = {
        section_background: '#111827',
        section_title_color: '#ffffff',
        section_border_color: '#1f2937',
        card_open_background: '#1f2937',
        card_closed_background: '#111827',
        card_open_day_color: '#ffffff',
        card_closed_day_color: '#fca5a5',
        card_time_color: '#9ca3af',
        closed_label_color: '#fda4af',
        copyright_color: '#6b7280',
    };

    Object.entries(operatingHoursDefaults).forEach(([key, value]) => {
        if (!props.form.landing_page_config.operating_hours_style[key]) {
            props.form.landing_page_config.operating_hours_style[key] = value;
        }
    });
    
    const defaultsBySection = {
        hero: {
            active: true,
            background_type: 'color',
            background_color: '#f9fafb',
            background_image: null,
            badge_text: 'Expert Dental Care',
            cta_text: 'Schedule Your Visit',
        },
        content: {
            active: true,
            image: null,
            title: 'Committed to Excellence in Dental Care',
            subtitle: 'Our clinic is dedicated to providing the best dental care in the region. Our team of experienced professionals is here to ensure your smile remains healthy and beautiful.',
            highlights: ['Modern Technology', 'Sterilized Environment', 'Compassionate Experts'],
            background_type: 'color',
            background_color: '#f9fafb',
            background_image: null,
        },
        services: {
            active: true,
            image: null,
            title: 'Our Specialized Services',
            subtitle: 'We offer a wide range of dental treatments to keep your clinic healthy and your smile glowing.',
            background_type: 'color',
            background_color: '#ffffff',
            background_image: null,
        },
        team: {
            active: true,
            image: null,
            title: 'Meet Our Specialist Team',
            subtitle: 'Expert dentists dedicated to provide world-class dental treatments with care.',
            background_type: 'color',
            background_color: '#ffffff',
            background_image: null,
        },
        contact: {
            active: true,
            image: null,
            title: "Have a Concern? We're Here to Help.",
            subtitle: 'Whether you are looking for an appointment or have a general inquiry, feel free to send us a message. Our team will respond as quickly as possible.',
            background_type: 'color',
            background_color: '#ffffff',
            background_image: null,
        },
    };

    if (!props.form.landing_page_config.sections) {
        props.form.landing_page_config.sections = {
            hero: { ...defaultsBySection.hero },
            content: { ...defaultsBySection.content },
            services: { ...defaultsBySection.services },
            team: { ...defaultsBySection.team },
            contact: { ...defaultsBySection.contact },
        };
    } else {
        // Ensure individual sections exist if sections object exists but is incomplete
        ['hero', 'content', 'services', 'team', 'contact'].forEach(sec => {
            if (!props.form.landing_page_config.sections[sec]) {
                props.form.landing_page_config.sections[sec] = { ...defaultsBySection[sec] };
            }

            if (!props.form.landing_page_config.sections[sec].title) {
                const defaultTitles = {
                    content: 'Committed to Excellence in Dental Care',
                    services: 'Our Specialized Services',
                    team: 'Meet Our Specialist Team',
                    contact: "Have a Concern? We're Here to Help.",
                };
                if (defaultTitles[sec]) {
                    props.form.landing_page_config.sections[sec].title = defaultTitles[sec];
                }
            }

            if (!props.form.landing_page_config.sections[sec].subtitle) {
                const defaultSubtitles = {
                    content: 'Our clinic is dedicated to providing the best dental care in the region. Our team of experienced professionals is here to ensure your smile remains healthy and beautiful.',
                    services: 'We offer a wide range of dental treatments to keep your clinic healthy and your smile glowing.',
                    team: 'Expert dentists dedicated to provide world-class dental treatments with care.',
                    contact: 'Whether you are looking for an appointment or have a general inquiry, feel free to send us a message. Our team will respond as quickly as possible.',
                };
                if (defaultSubtitles[sec]) {
                    props.form.landing_page_config.sections[sec].subtitle = defaultSubtitles[sec];
                }
            }

            if (!['color', 'image'].includes(props.form.landing_page_config.sections[sec].background_type)) {
                props.form.landing_page_config.sections[sec].background_type = defaultsBySection[sec].background_type;
            }

            if (!props.form.landing_page_config.sections[sec].background_color) {
                props.form.landing_page_config.sections[sec].background_color = defaultsBySection[sec].background_color;
            }

            if (!Object.prototype.hasOwnProperty.call(props.form.landing_page_config.sections[sec], 'background_image')) {
                props.form.landing_page_config.sections[sec].background_image = defaultsBySection[sec].background_image;
            }

            if (sec === 'content' && !Array.isArray(props.form.landing_page_config.sections.content.highlights)) {
                props.form.landing_page_config.sections.content.highlights = [...defaultsBySection.content.highlights];
            }
        });
    }

    ['content', 'services', 'team', 'contact'].forEach((sec) => {
        if (!props.form.landing_page_config.sections[sec].title) {
            const defaultTitles = {
                services: 'Our Specialized Services',
                team: 'Meet Our Specialist Team',
                contact: "Have a Concern? We're Here to Help.",
            };
            props.form.landing_page_config.sections[sec].title = defaultTitles[sec];
        }

        if (!props.form.landing_page_config.sections[sec].subtitle) {
            const defaultSubtitles = {
                services: 'We offer a wide range of dental treatments to keep your clinic healthy and your smile glowing.',
                team: 'Expert dentists dedicated to provide world-class dental treatments with care.',
                contact: 'Whether you are looking for an appointment or have a general inquiry, feel free to send us a message. Our team will respond as quickly as possible.',
            };
            props.form.landing_page_config.sections[sec].subtitle = defaultSubtitles[sec];
        }
    });

    if (!props.form.landing_page_config.team || typeof props.form.landing_page_config.team !== 'object') {
        props.form.landing_page_config.team = {
            source_mode: 'auto_staff',
            include_owner: true,
            manual_cards: [],
        };
    }

    if (!['auto_staff', 'manual', 'hybrid'].includes(props.form.landing_page_config.team.source_mode)) {
        props.form.landing_page_config.team.source_mode = 'auto_staff';
    }

    if (!Array.isArray(props.form.landing_page_config.team.manual_cards)) {
        props.form.landing_page_config.team.manual_cards = [];
    }
};

initConfig();

const addManualTeamCard = () => {
    props.form.landing_page_config.team.manual_cards.push({
        id: `manual-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
        name: '',
        role: '',
        bio: '',
        image_url: '',
    });
};

const removeManualTeamCard = (index) => {
    props.form.landing_page_config.team.manual_cards.splice(index, 1);
};

const handleManualCardImageChange = async (event, index) => {
    const file = event.target.files?.[0];
    if (!file) return;

    uploading.manualCardImage = true;

    try {
        let processed = file;
        if (file.type.startsWith('image/')) {
            processed = await resizeImage(file, 1200, 1200);
        }

        const card = props.form.landing_page_config.team.manual_cards[index];
        if (!card?.id) {
            throw new Error('Missing team card id. Please remove and re-add the card.');
        }

        const formData = new FormData();
        formData.append('field', 'landing_team_card');
        formData.append('card_id', String(card.id));
        formData.append('image', processed);

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

        props.form.landing_page_config.team.manual_cards[index].image_url = result.url;

        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: 'Card image updated',
            showConfirmButton: false,
            timer: 1800,
        });
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Upload Failed',
            text: error.message || 'Could not process the image.',
        });
    } finally {
        uploading.manualCardImage = false;
        event.target.value = '';
    }
};

const onManualCardDragStart = (index) => {
    draggedManualCardIndex.value = index;
};

const onManualCardDrop = (targetIndex) => {
    const sourceIndex = draggedManualCardIndex.value;
    draggedManualCardIndex.value = null;

    if (sourceIndex === null || sourceIndex === targetIndex) return;

    const cards = props.form.landing_page_config.team.manual_cards;
    const [moved] = cards.splice(sourceIndex, 1);
    cards.splice(targetIndex, 0, moved);
};

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
            content: 'landing_content',
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

const handleSectionBackgroundImageChange = async (e, section) => {
    const file = e.target.files?.[0];
    if (!file) return;

    const stateMap = {
        hero: 'heroBg',
        content: 'contentBg',
        services: 'servicesBg',
        team: 'teamBg',
        contact: 'contactBg',
    };

    const fieldMap = {
        hero: 'landing_bg_hero',
        content: 'landing_bg_content',
        services: 'landing_bg_services',
        team: 'landing_bg_team',
        contact: 'landing_bg_contact',
    };

    const stateKey = stateMap[section];
    const uploadField = fieldMap[section];
    if (!stateKey || !uploadField) return;

    uploading[stateKey] = true;

    try {
        let uploadFile = file;
        if (file.type.startsWith('image/')) {
            uploadFile = await resizeImage(file, 2000, 2000);
        }

        const formData = new FormData();
        formData.append('field', uploadField);
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

        props.form.landing_page_config.sections[section].background_image = result.url;
        props.form.landing_page_config.sections[section].background_type = 'image';

        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: 'Background image uploaded',
            showConfirmButton: false,
            timer: 1800,
        });
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Upload Failed',
            text: error.message || 'Could not upload the section background image.',
        });
    } finally {
        uploading[stateKey] = false;
        e.target.value = '';
    }
};

const getPreviewUrl = (section) => {
    const imgData = props.form.landing_page_config?.sections?.[section]?.image;
    if (!imgData) {
        // High-quality dental/medical defaults
        const defaults = {
            content: '/images/branding/defaults/team.png',
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

const getSectionBackgroundPreviewUrl = (section) => {
    const imgData = props.form.landing_page_config?.sections?.[section]?.background_image;
    if (!imgData) return null;

    if (typeof imgData === 'string' && (imgData.startsWith('data:image/') || imgData.startsWith('http') || imgData.startsWith('/'))) {
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

        <section class="space-y-6 pt-6 border-t border-base-200">
            <h4 class="text-xs font-black text-primary uppercase tracking-[0.2em] flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-primary"></span>
                Footer Operating Hours Designer
            </h4>

            <div class="p-6 bg-base-100 rounded-3xl border border-base-300 space-y-5 shadow-sm">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label"><span class="label-text font-bold text-[9px] uppercase tracking-widest opacity-40">Footer Background</span></label>
                        <div class="flex items-center gap-3 bg-base-200 p-2 rounded-xl border border-base-300">
                            <input type="color" v-model="form.landing_page_config.operating_hours_style.section_background" class="w-8 h-8 rounded border-none bg-transparent" />
                            <input type="text" v-model="form.landing_page_config.operating_hours_style.section_background" class="input input-xs border-none bg-transparent font-mono" />
                        </div>
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text font-bold text-[9px] uppercase tracking-widest opacity-40">Section Title Color</span></label>
                        <div class="flex items-center gap-3 bg-base-200 p-2 rounded-xl border border-base-300">
                            <input type="color" v-model="form.landing_page_config.operating_hours_style.section_title_color" class="w-8 h-8 rounded border-none bg-transparent" />
                            <input type="text" v-model="form.landing_page_config.operating_hours_style.section_title_color" class="input input-xs border-none bg-transparent font-mono" />
                        </div>
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text font-bold text-[9px] uppercase tracking-widest opacity-40">Top Border Color</span></label>
                        <div class="flex items-center gap-3 bg-base-200 p-2 rounded-xl border border-base-300">
                            <input type="color" v-model="form.landing_page_config.operating_hours_style.section_border_color" class="w-8 h-8 rounded border-none bg-transparent" />
                            <input type="text" v-model="form.landing_page_config.operating_hours_style.section_border_color" class="input input-xs border-none bg-transparent font-mono" />
                        </div>
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text font-bold text-[9px] uppercase tracking-widest opacity-40">Copyright Text Color</span></label>
                        <div class="flex items-center gap-3 bg-base-200 p-2 rounded-xl border border-base-300">
                            <input type="color" v-model="form.landing_page_config.operating_hours_style.copyright_color" class="w-8 h-8 rounded border-none bg-transparent" />
                            <input type="text" v-model="form.landing_page_config.operating_hours_style.copyright_color" class="input input-xs border-none bg-transparent font-mono" />
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label"><span class="label-text font-bold text-[9px] uppercase tracking-widest opacity-40">Open Day Card Background</span></label>
                        <div class="flex items-center gap-3 bg-base-200 p-2 rounded-xl border border-base-300">
                            <input type="color" v-model="form.landing_page_config.operating_hours_style.card_open_background" class="w-8 h-8 rounded border-none bg-transparent" />
                            <input type="text" v-model="form.landing_page_config.operating_hours_style.card_open_background" class="input input-xs border-none bg-transparent font-mono" />
                        </div>
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text font-bold text-[9px] uppercase tracking-widest opacity-40">Closed Day Card Background</span></label>
                        <div class="flex items-center gap-3 bg-base-200 p-2 rounded-xl border border-base-300">
                            <input type="color" v-model="form.landing_page_config.operating_hours_style.card_closed_background" class="w-8 h-8 rounded border-none bg-transparent" />
                            <input type="text" v-model="form.landing_page_config.operating_hours_style.card_closed_background" class="input input-xs border-none bg-transparent font-mono" />
                        </div>
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text font-bold text-[9px] uppercase tracking-widest opacity-40">Open Day Label Color</span></label>
                        <div class="flex items-center gap-3 bg-base-200 p-2 rounded-xl border border-base-300">
                            <input type="color" v-model="form.landing_page_config.operating_hours_style.card_open_day_color" class="w-8 h-8 rounded border-none bg-transparent" />
                            <input type="text" v-model="form.landing_page_config.operating_hours_style.card_open_day_color" class="input input-xs border-none bg-transparent font-mono" />
                        </div>
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text font-bold text-[9px] uppercase tracking-widest opacity-40">Closed Day Label Color</span></label>
                        <div class="flex items-center gap-3 bg-base-200 p-2 rounded-xl border border-base-300">
                            <input type="color" v-model="form.landing_page_config.operating_hours_style.card_closed_day_color" class="w-8 h-8 rounded border-none bg-transparent" />
                            <input type="text" v-model="form.landing_page_config.operating_hours_style.card_closed_day_color" class="input input-xs border-none bg-transparent font-mono" />
                        </div>
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text font-bold text-[9px] uppercase tracking-widest opacity-40">Open Time Text Color</span></label>
                        <div class="flex items-center gap-3 bg-base-200 p-2 rounded-xl border border-base-300">
                            <input type="color" v-model="form.landing_page_config.operating_hours_style.card_time_color" class="w-8 h-8 rounded border-none bg-transparent" />
                            <input type="text" v-model="form.landing_page_config.operating_hours_style.card_time_color" class="input input-xs border-none bg-transparent font-mono" />
                        </div>
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text font-bold text-[9px] uppercase tracking-widest opacity-40">Closed Badge Color</span></label>
                        <div class="flex items-center gap-3 bg-base-200 p-2 rounded-xl border border-base-300">
                            <input type="color" v-model="form.landing_page_config.operating_hours_style.closed_label_color" class="w-8 h-8 rounded border-none bg-transparent" />
                            <input type="text" v-model="form.landing_page_config.operating_hours_style.closed_label_color" class="input input-xs border-none bg-transparent font-mono" />
                        </div>
                    </div>
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
                <!-- Hero Section -->
                <div class="p-6 bg-base-100 rounded-3xl border border-base-300 space-y-4 shadow-sm">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-3">
                            <span class="text-xl">✍️</span>
                            <span class="text-xs font-black uppercase tracking-widest text-base-content">Hero Section</span>
                        </div>
                        <input type="checkbox" v-model="form.landing_page_config.sections.hero.active" class="toggle toggle-primary toggle-md">
                    </div>
                    
                    <div v-if="form.landing_page_config.sections.hero.active" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div class="form-control">
                                <label class="label"><span class="label-text font-bold text-[9px] uppercase tracking-widest opacity-40">Background Type</span></label>
                                <select v-model="form.landing_page_config.sections.hero.background_type" class="select select-bordered rounded-xl w-full max-w-[220px] h-10 min-h-10 text-xs leading-tight bg-base-100 text-base-content">
                                    <option value="color">Color</option>
                                    <option value="image">Image</option>
                                </select>
                            </div>
                            <div class="form-control" v-if="form.landing_page_config.sections.hero.background_type === 'color'">
                                <label class="label"><span class="label-text font-bold text-[9px] uppercase tracking-widest opacity-40">Background Color</span></label>
                                <div class="flex items-center gap-3 bg-base-200 p-2 rounded-xl border border-base-300">
                                    <input type="color" v-model="form.landing_page_config.sections.hero.background_color" class="w-8 h-8 rounded border-none bg-transparent" />
                                    <input type="text" v-model="form.landing_page_config.sections.hero.background_color" class="input input-xs border-none bg-transparent font-mono" />
                                </div>
                            </div>
                            <div class="form-control md:col-span-2" v-else>
                                <label class="label"><span class="label-text font-bold text-[9px] uppercase tracking-widest opacity-40">Background Image</span></label>
                                <div class="h-28 bg-base-200 rounded-2xl border border-dashed border-base-300 flex items-center justify-center relative overflow-hidden" :class="{ 'pointer-events-none': uploading.heroBg }">
                                    <div v-if="uploading.heroBg" class="absolute inset-0 flex items-center justify-center bg-base-200/80 z-10">
                                        <span class="loading loading-spinner text-primary"></span>
                                    </div>
                                    <img v-if="getSectionBackgroundPreviewUrl('hero')" :src="getSectionBackgroundPreviewUrl('hero')" class="w-full h-full object-cover" />
                                    <span v-else class="text-[10px] opacity-40 font-black uppercase tracking-widest">Click to upload hero background</span>
                                    <input type="file" accept="image/*" @change="handleSectionBackgroundImageChange($event, 'hero')" class="absolute inset-0 opacity-0 cursor-pointer" :disabled="uploading.heroBg">
                                </div>
                            </div>
                        </div>

                         <div class="form-control w-full">
                            <label class="label"><span class="label-text font-bold text-[10px] uppercase tracking-widest opacity-50">Main Hero Title</span></label>
                            <input type="text" v-model="form.hero_title" placeholder="e.g. Expert Dental Care" class="input input-bordered w-full rounded-2xl bg-base-100 focus:border-primary border-base-300">
                        </div>
                        <div class="form-control w-full">
                            <label class="label"><span class="label-text font-bold text-[10px] uppercase tracking-widest opacity-50">Hero Badge Text</span></label>
                            <input type="text" v-model="form.landing_page_config.sections.hero.badge_text" placeholder="e.g. Expert Dental Care" class="input input-bordered w-full rounded-2xl bg-base-100 focus:border-primary border-base-300">
                        </div>
                        <div class="form-control w-full">
                            <label class="label"><span class="label-text font-bold text-[10px] uppercase tracking-widest opacity-50">Hero Subtitle</span></label>
                            <textarea v-model="form.hero_subtitle" rows="2" placeholder="e.g. Providing high-quality care..." class="textarea textarea-bordered w-full rounded-2xl bg-base-100 focus:border-primary border-base-300"></textarea>
                        </div>
                        <div class="form-control w-full">
                            <label class="label"><span class="label-text font-bold text-[10px] uppercase tracking-widest opacity-50">Hero Primary Button Text</span></label>
                            <input type="text" v-model="form.landing_page_config.sections.hero.cta_text" placeholder="e.g. Schedule Your Visit" class="input input-bordered w-full rounded-2xl bg-base-100 focus:border-primary border-base-300">
                        </div>
                    </div>
                </div>

                <!-- Content Section -->
                <div class="p-6 bg-base-100 rounded-3xl border border-base-300 space-y-4 shadow-sm">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-3">
                            <span class="text-xl">📖</span>
                            <span class="text-xs font-black uppercase tracking-widest text-base-content">Content Section</span>
                        </div>
                        <input type="checkbox" v-model="form.landing_page_config.sections.content.active" class="toggle toggle-primary toggle-md">
                    </div>

                    <div v-if="form.landing_page_config.sections.content.active" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div class="form-control">
                                <label class="label"><span class="label-text font-bold text-[9px] uppercase tracking-widest opacity-40">Background Type</span></label>
                                <select v-model="form.landing_page_config.sections.content.background_type" class="select select-bordered rounded-xl w-full max-w-[220px] h-10 min-h-10 text-xs leading-tight bg-base-100 text-base-content">
                                    <option value="color">Color</option>
                                    <option value="image">Image</option>
                                </select>
                            </div>
                            <div class="form-control" v-if="form.landing_page_config.sections.content.background_type === 'color'">
                                <label class="label"><span class="label-text font-bold text-[9px] uppercase tracking-widest opacity-40">Background Color</span></label>
                                <div class="flex items-center gap-3 bg-base-200 p-2 rounded-xl border border-base-300">
                                    <input type="color" v-model="form.landing_page_config.sections.content.background_color" class="w-8 h-8 rounded border-none bg-transparent" />
                                    <input type="text" v-model="form.landing_page_config.sections.content.background_color" class="input input-xs border-none bg-transparent font-mono" />
                                </div>
                            </div>
                            <div class="form-control md:col-span-2" v-else>
                                <label class="label"><span class="label-text font-bold text-[9px] uppercase tracking-widest opacity-40">Background Image</span></label>
                                <div class="h-28 bg-base-200 rounded-2xl border border-dashed border-base-300 flex items-center justify-center relative overflow-hidden" :class="{ 'pointer-events-none': uploading.contentBg }">
                                    <div v-if="uploading.contentBg" class="absolute inset-0 flex items-center justify-center bg-base-200/80 z-10"><span class="loading loading-spinner text-primary"></span></div>
                                    <img v-if="getSectionBackgroundPreviewUrl('content')" :src="getSectionBackgroundPreviewUrl('content')" class="w-full h-full object-cover" />
                                    <span v-else class="text-[10px] opacity-40 font-black uppercase tracking-widest">Click to upload content background</span>
                                    <input type="file" accept="image/*" @change="handleSectionBackgroundImageChange($event, 'content')" class="absolute inset-0 opacity-0 cursor-pointer" :disabled="uploading.contentBg">
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div class="form-control">
                                <label class="label"><span class="label-text font-bold text-[9px] uppercase tracking-widest opacity-40">Section Title</span></label>
                                <input type="text" v-model="form.landing_page_config.sections.content.title" class="input input-sm input-bordered rounded-xl" placeholder="Content title">
                            </div>
                            <div class="form-control">
                                <label class="label"><span class="label-text font-bold text-[9px] uppercase tracking-widest opacity-40">Section Subtitle</span></label>
                                <textarea v-model="form.landing_page_config.sections.content.subtitle" rows="2" class="textarea textarea-sm textarea-bordered rounded-xl" placeholder="Content subtitle"></textarea>
                            </div>
                        </div>

                        <div class="form-control">
                            <label class="label"><span class="label-text font-bold text-[9px] uppercase tracking-widest opacity-40">Content Image</span></label>
                            <div class="h-32 bg-base-200 rounded-2xl border border-dashed border-base-300 flex items-center justify-center relative group overflow-hidden" :class="{ 'pointer-events-none': uploading.content }">
                                <div v-if="uploading.content" class="absolute inset-0 flex items-center justify-center bg-base-200/80 z-10">
                                    <span class="loading loading-spinner text-primary"></span>
                                </div>
                                <img v-if="getPreviewUrl('content')" :src="getPreviewUrl('content')" class="w-full h-full object-cover">
                                <span v-else class="text-[10px] opacity-40 font-black uppercase tracking-widest">Click to upload image</span>
                                <input type="file" accept="image/*" @change="handleFileChange($event, 'content')" class="absolute inset-0 opacity-0 cursor-pointer" :disabled="uploading.content">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div class="form-control">
                                <label class="label"><span class="label-text font-bold text-[9px] uppercase tracking-widest opacity-40">Highlight 1</span></label>
                                <input type="text" v-model="form.landing_page_config.sections.content.highlights[0]" class="input input-sm input-bordered rounded-xl" placeholder="Highlight item 1">
                            </div>
                            <div class="form-control">
                                <label class="label"><span class="label-text font-bold text-[9px] uppercase tracking-widest opacity-40">Highlight 2</span></label>
                                <input type="text" v-model="form.landing_page_config.sections.content.highlights[1]" class="input input-sm input-bordered rounded-xl" placeholder="Highlight item 2">
                            </div>
                            <div class="form-control">
                                <label class="label"><span class="label-text font-bold text-[9px] uppercase tracking-widest opacity-40">Highlight 3</span></label>
                                <input type="text" v-model="form.landing_page_config.sections.content.highlights[2]" class="input input-sm input-bordered rounded-xl" placeholder="Highlight item 3">
                            </div>
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
                        <div class="sm:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div class="form-control">
                                <label class="label"><span class="label-text font-bold text-[9px] uppercase tracking-widest opacity-40">Section Title</span></label>
                                <input type="text" v-model="form.landing_page_config.sections.services.title" class="input input-sm input-bordered rounded-xl" placeholder="Services title">
                            </div>
                            <div class="form-control">
                                <label class="label"><span class="label-text font-bold text-[9px] uppercase tracking-widest opacity-40">Section Subtitle</span></label>
                                <input type="text" v-model="form.landing_page_config.sections.services.subtitle" class="input input-sm input-bordered rounded-xl" placeholder="Services subtitle">
                            </div>
                            <div class="form-control">
                                <label class="label"><span class="label-text font-bold text-[9px] uppercase tracking-widest opacity-40">Background Type</span></label>
                                <select v-model="form.landing_page_config.sections.services.background_type" class="select select-bordered rounded-xl w-full max-w-[220px] h-10 min-h-10 text-xs leading-tight bg-base-100 text-base-content">
                                    <option value="color">Color</option>
                                    <option value="image">Image</option>
                                </select>
                            </div>
                            <div class="form-control" v-if="form.landing_page_config.sections.services.background_type === 'color'">
                                <label class="label"><span class="label-text font-bold text-[9px] uppercase tracking-widest opacity-40">Background Color</span></label>
                                <div class="flex items-center gap-3 bg-base-200 p-2 rounded-xl border border-base-300">
                                    <input type="color" v-model="form.landing_page_config.sections.services.background_color" class="w-8 h-8 rounded border-none bg-transparent" />
                                    <input type="text" v-model="form.landing_page_config.sections.services.background_color" class="input input-xs border-none bg-transparent font-mono" />
                                </div>
                            </div>
                            <div class="form-control md:col-span-2" v-else>
                                <label class="label"><span class="label-text font-bold text-[9px] uppercase tracking-widest opacity-40">Background Image</span></label>
                                <div class="h-28 bg-base-200 rounded-2xl border border-dashed border-base-300 flex items-center justify-center relative overflow-hidden" :class="{ 'pointer-events-none': uploading.servicesBg }">
                                    <div v-if="uploading.servicesBg" class="absolute inset-0 flex items-center justify-center bg-base-200/80 z-10"><span class="loading loading-spinner text-primary"></span></div>
                                    <img v-if="getSectionBackgroundPreviewUrl('services')" :src="getSectionBackgroundPreviewUrl('services')" class="w-full h-full object-cover" />
                                    <span v-else class="text-[10px] opacity-40 font-black uppercase tracking-widest">Click to upload services background</span>
                                    <input type="file" accept="image/*" @change="handleSectionBackgroundImageChange($event, 'services')" class="absolute inset-0 opacity-0 cursor-pointer" :disabled="uploading.servicesBg">
                                </div>
                            </div>
                        </div>
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
                        <div class="sm:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div class="form-control">
                                <label class="label"><span class="label-text font-bold text-[9px] uppercase tracking-widest opacity-40">Section Title</span></label>
                                <input type="text" v-model="form.landing_page_config.sections.team.title" class="input input-sm input-bordered rounded-xl" placeholder="Team title">
                            </div>
                            <div class="form-control">
                                <label class="label"><span class="label-text font-bold text-[9px] uppercase tracking-widest opacity-40">Section Subtitle</span></label>
                                <input type="text" v-model="form.landing_page_config.sections.team.subtitle" class="input input-sm input-bordered rounded-xl" placeholder="Team subtitle">
                            </div>
                            <div class="form-control">
                                <label class="label"><span class="label-text font-bold text-[9px] uppercase tracking-widest opacity-40">Background Type</span></label>
                                <select v-model="form.landing_page_config.sections.team.background_type" class="select select-bordered rounded-xl w-full max-w-[220px] h-10 min-h-10 text-xs leading-tight bg-base-100 text-base-content">
                                    <option value="color">Color</option>
                                    <option value="image">Image</option>
                                </select>
                            </div>
                            <div class="form-control" v-if="form.landing_page_config.sections.team.background_type === 'color'">
                                <label class="label"><span class="label-text font-bold text-[9px] uppercase tracking-widest opacity-40">Background Color</span></label>
                                <div class="flex items-center gap-3 bg-base-200 p-2 rounded-xl border border-base-300">
                                    <input type="color" v-model="form.landing_page_config.sections.team.background_color" class="w-8 h-8 rounded border-none bg-transparent" />
                                    <input type="text" v-model="form.landing_page_config.sections.team.background_color" class="input input-xs border-none bg-transparent font-mono" />
                                </div>
                            </div>
                            <div class="form-control md:col-span-2" v-else>
                                <label class="label"><span class="label-text font-bold text-[9px] uppercase tracking-widest opacity-40">Background Image</span></label>
                                <div class="h-28 bg-base-200 rounded-2xl border border-dashed border-base-300 flex items-center justify-center relative overflow-hidden" :class="{ 'pointer-events-none': uploading.teamBg }">
                                    <div v-if="uploading.teamBg" class="absolute inset-0 flex items-center justify-center bg-base-200/80 z-10"><span class="loading loading-spinner text-primary"></span></div>
                                    <img v-if="getSectionBackgroundPreviewUrl('team')" :src="getSectionBackgroundPreviewUrl('team')" class="w-full h-full object-cover" />
                                    <span v-else class="text-[10px] opacity-40 font-black uppercase tracking-widest">Click to upload team background</span>
                                    <input type="file" accept="image/*" @change="handleSectionBackgroundImageChange($event, 'team')" class="absolute inset-0 opacity-0 cursor-pointer" :disabled="uploading.teamBg">
                                </div>
                            </div>
                        </div>
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

                    <div v-if="form.landing_page_config.sections.team.active" class="mt-4 p-4 rounded-2xl border border-base-300 bg-base-200/30 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text font-bold text-[10px] uppercase tracking-widest opacity-50">Team Source Mode</span>
                                </label>
                                <select v-model="form.landing_page_config.team.source_mode" class="select select-bordered rounded-xl bg-base-100 border-base-300">
                                    <option value="auto_staff">Auto from Staff</option>
                                    <option value="manual">Manual Cards Only</option>
                                    <option value="hybrid">Hybrid (Auto + Manual)</option>
                                </select>
                            </div>

                            <label class="form-control">
                                <span class="label-text font-bold text-[10px] uppercase tracking-widest opacity-50 mb-2">Include Clinic Owner (Auto Mode)</span>
                                <input type="checkbox" v-model="form.landing_page_config.team.include_owner" class="toggle toggle-primary toggle-md" />
                            </label>
                        </div>

                        <div class="text-[10px] text-base-content/60">
                            In Auto mode, staff profiles come from your users. In Manual mode, you can fully curate cards with custom name, role, bio, and image URL.
                        </div>

                        <div v-if="['manual', 'hybrid'].includes(form.landing_page_config.team.source_mode)" class="space-y-3">
                            <div class="flex items-center justify-between">
                                <h5 class="text-[11px] font-black uppercase tracking-wider text-base-content">Manual Team Cards</h5>
                                <button type="button" class="btn btn-xs btn-primary" @click="addManualTeamCard">Add Card</button>
                            </div>

                            <div v-if="form.landing_page_config.team.manual_cards.length === 0" class="text-[10px] text-base-content/50 italic p-3 rounded-xl bg-base-100 border border-base-300">
                                No manual cards yet. Click Add Card to create your team showcase cards.
                            </div>

                            <div
                                v-for="(card, index) in form.landing_page_config.team.manual_cards"
                                :key="card.id || index"
                                class="p-4 rounded-2xl border border-base-300 bg-base-100 space-y-3 cursor-move"
                                draggable="true"
                                @dragstart="onManualCardDragStart(index)"
                                @dragover.prevent
                                @drop="onManualCardDrop(index)"
                            >
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <input type="text" v-model="card.name" placeholder="Display Name" class="input input-sm input-bordered rounded-xl" />
                                    <input type="text" v-model="card.role" placeholder="Role / Title" class="input input-sm input-bordered rounded-xl" />
                                </div>
                                <textarea v-model="card.bio" rows="2" placeholder="Short bio" class="textarea textarea-sm textarea-bordered rounded-xl"></textarea>
                                <div class="space-y-2">
                                    <label class="text-[9px] font-black uppercase tracking-widest opacity-50">Card Photo</label>
                                    <div class="h-28 rounded-xl border border-dashed border-base-300 bg-base-200 flex items-center justify-center overflow-hidden relative">
                                        <img v-if="card.image_url" :src="card.image_url" class="w-full h-full object-cover" />
                                        <span v-else class="text-[10px] opacity-40">No image selected</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <label class="btn btn-xs btn-outline">
                                            Upload
                                            <input type="file" accept="image/*" class="hidden" @change="handleManualCardImageChange($event, index)">
                                        </label>
                                        <input type="text" v-model="card.image_url" placeholder="or paste image URL" class="input input-xs input-bordered rounded-xl flex-1" />
                                    </div>
                                </div>
                                <div class="flex justify-end">
                                    <button type="button" class="btn btn-xs btn-error btn-outline" @click="removeManualTeamCard(index)">Remove</button>
                                </div>
                            </div>
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
                        <div class="sm:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div class="form-control">
                                <label class="label"><span class="label-text font-bold text-[9px] uppercase tracking-widest opacity-40">Section Title</span></label>
                                <input type="text" v-model="form.landing_page_config.sections.contact.title" class="input input-sm input-bordered rounded-xl" placeholder="Contact title">
                            </div>
                            <div class="form-control">
                                <label class="label"><span class="label-text font-bold text-[9px] uppercase tracking-widest opacity-40">Section Subtitle</span></label>
                                <input type="text" v-model="form.landing_page_config.sections.contact.subtitle" class="input input-sm input-bordered rounded-xl" placeholder="Contact subtitle">
                            </div>
                            <div class="form-control">
                                <label class="label"><span class="label-text font-bold text-[9px] uppercase tracking-widest opacity-40">Background Type</span></label>
                                <select v-model="form.landing_page_config.sections.contact.background_type" class="select select-bordered rounded-xl w-full max-w-[220px] h-10 min-h-10 text-xs leading-tight bg-base-100 text-base-content">
                                    <option value="color">Color</option>
                                    <option value="image">Image</option>
                                </select>
                            </div>
                            <div class="form-control" v-if="form.landing_page_config.sections.contact.background_type === 'color'">
                                <label class="label"><span class="label-text font-bold text-[9px] uppercase tracking-widest opacity-40">Background Color</span></label>
                                <div class="flex items-center gap-3 bg-base-200 p-2 rounded-xl border border-base-300">
                                    <input type="color" v-model="form.landing_page_config.sections.contact.background_color" class="w-8 h-8 rounded border-none bg-transparent" />
                                    <input type="text" v-model="form.landing_page_config.sections.contact.background_color" class="input input-xs border-none bg-transparent font-mono" />
                                </div>
                            </div>
                            <div class="form-control md:col-span-2" v-else>
                                <label class="label"><span class="label-text font-bold text-[9px] uppercase tracking-widest opacity-40">Background Image</span></label>
                                <div class="h-28 bg-base-200 rounded-2xl border border-dashed border-base-300 flex items-center justify-center relative overflow-hidden" :class="{ 'pointer-events-none': uploading.contactBg }">
                                    <div v-if="uploading.contactBg" class="absolute inset-0 flex items-center justify-center bg-base-200/80 z-10"><span class="loading loading-spinner text-primary"></span></div>
                                    <img v-if="getSectionBackgroundPreviewUrl('contact')" :src="getSectionBackgroundPreviewUrl('contact')" class="w-full h-full object-cover" />
                                    <span v-else class="text-[10px] opacity-40 font-black uppercase tracking-widest">Click to upload contact background</span>
                                    <input type="file" accept="image/*" @change="handleSectionBackgroundImageChange($event, 'contact')" class="absolute inset-0 opacity-0 cursor-pointer" :disabled="uploading.contactBg">
                                </div>
                            </div>
                        </div>
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
