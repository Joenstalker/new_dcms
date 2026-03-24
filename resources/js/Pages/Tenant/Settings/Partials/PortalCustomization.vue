<script setup>
import { computed } from 'vue';

const props = defineProps({
    form: {
        type: Object,
        required: true
    },
    staff: {
        type: Array,
        default: () => []
    },
    is_premium: {
        type: Boolean,
        default: false
    }
});

const isSelected = (staffId) => {
    return props.form.portal_config.selected_staff.includes(staffId);
};

const toggleStaff = (staffId) => {
    const index = props.form.portal_config.selected_staff.indexOf(staffId);
    if (index === -1) {
        props.form.portal_config.selected_staff.push(staffId);
    } else {
        props.form.portal_config.selected_staff.splice(index, 1);
    }
};

const selectAll = () => {
    props.form.portal_config.selected_staff = props.staff.map(s => s.id);
};

const clearAll = () => {
    props.form.portal_config.selected_staff = [];
};
</script>

<template>
    <div class="space-y-8 animate-fade-in" :class="{ 'opacity-60 pointer-events-none grayscale': !is_premium }">
        <section class="space-y-6">
            <h4 class="text-xs font-black text-primary uppercase tracking-[0.2em] flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-primary"></span>
                Portal Branding Scope
            </h4>

            <p class="text-sm text-gray-500 max-w-2xl">
                Choose how your clinic branding (colors, logos, fonts) is applied to the Dentist and Assistant portals. 
                Full branding application ensures a cohesive professional experience.
            </p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Apply to All -->
                <div 
                    @click="form.portal_config.apply_to = 'all'"
                    class="p-6 rounded-[2.5rem] border-2 transition-all cursor-pointer group flex flex-col items-center text-center gap-4"
                    :class="[form.portal_config.apply_to === 'all' ? 'bg-primary/5 border-primary shadow-xl shadow-primary/5' : 'bg-base-200 border-transparent opacity-60 hover:opacity-100']"
                >
                    <div class="w-16 h-16 rounded-3xl bg-white shadow-sm flex items-center justify-center text-2xl group-hover:scale-110 transition-transform">
                        🌍
                    </div>
                    <div>
                        <h5 class="font-black text-sm uppercase tracking-widest">Apply to All Staff</h5>
                        <p class="text-[10px] opacity-50 mt-1">Every staff member will see the clinic's custom branding.</p>
                    </div>
                    <input type="radio" class="radio radio-primary" :checked="form.portal_config.apply_to === 'all'">
                </div>

                <!-- Apply to Specific -->
                <div 
                    @click="form.portal_config.apply_to = 'specific'"
                    class="p-6 rounded-[2.5rem] border-2 transition-all cursor-pointer group flex flex-col items-center text-center gap-4"
                    :class="[form.portal_config.apply_to === 'specific' ? 'bg-primary/5 border-primary shadow-xl shadow-primary/5' : 'bg-base-200 border-transparent opacity-60 hover:opacity-100']"
                >
                    <div class="w-16 h-16 rounded-3xl bg-white shadow-sm flex items-center justify-center text-2xl group-hover:scale-110 transition-transform">
                        🎯
                    </div>
                    <div>
                        <h5 class="font-black text-sm uppercase tracking-widest">Specific Selection</h5>
                        <p class="text-[10px] opacity-50 mt-1">Only selected staff members will see the custom branding suite.</p>
                    </div>
                    <input type="radio" class="radio radio-primary" :checked="form.portal_config.apply_to === 'specific'">
                </div>
            </div>
        </section>

        <!-- Staff Selector (Only if Specific) -->
        <section v-if="form.portal_config.apply_to === 'specific'" class="space-y-6 pt-6 border-t border-base-200 animate-slide-up">
            <div class="flex items-center justify-between">
                <h4 class="text-xs font-black text-primary uppercase tracking-[0.2em] flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-primary"></span>
                    Select Staff Members
                </h4>
                <div class="flex gap-2">
                    <button type="button" @click="selectAll" class="btn btn-ghost btn-xs font-black uppercase text-[8px] tracking-widest">Select All</button>
                    <button type="button" @click="clearAll" class="btn btn-ghost btn-xs font-black uppercase text-[8px] tracking-widest text-error">Clear</button>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div 
                    v-for="s in staff" 
                    :key="s.id"
                    @click="toggleStaff(s.id)"
                    class="p-4 rounded-2xl border transition-all cursor-pointer flex items-center gap-4 group"
                    :class="[isSelected(s.id) ? 'bg-base-100 border-primary shadow-sm' : 'bg-base-200 border-transparent opacity-60 hover:opacity-100']"
                >
                    <div 
                        class="w-10 h-10 rounded-xl flex items-center justify-center text-white font-black text-xs shrink-0 transition-transform group-hover:scale-110"
                        :style="{ backgroundColor: isSelected(s.id) ? (form.branding_color || '#2563eb') : '#94a3b8' }"
                    >
                        {{ s.name.charAt(0) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-bold text-xs truncate">{{ s.name }}</p>
                        <p class="text-[9px] uppercase tracking-widest opacity-40">{{ isSelected(s.id) ? 'Custom Branding Active' : 'Default Styling' }}</p>
                    </div>
                    <input type="checkbox" :checked="isSelected(s.id)" class="checkbox checkbox-primary checkbox-sm rounded-lg">
                </div>
            </div>
            
            <p v-if="staff.length === 0" class="text-center py-10 opacity-30 italic text-sm font-medium">
                No dentists or assistants found. 
                <br><span class="text-[10px] uppercase font-black not-italic tracking-widest">Add staff members first in Staff Management</span>
            </p>
        </section>

        <!-- Non-Premium Banner -->
        <div v-if="!is_premium" class="bg-primary/5 p-10 rounded-[3rem] text-center border border-dashed border-primary/20 pointer-events-auto grayscale-0">
            <span class="text-5xl block mb-4">👑</span>
            <h3 class="text-lg font-black uppercase tracking-widest mb-2">Unlock Staff Portal Customization</h3>
            <p class="text-sm opacity-60 mb-6 max-w-md mx-auto italic">
                Impress your team with a portal that mirrors your clinic's luxury identity. 
                Available exclusively on Pro & Ultimate plans.
            </p>
            <button type="button" class="btn btn-primary rounded-2xl font-black uppercase tracking-widest text-xs px-10">Compare Plans</button>
        </div>
    </div>
</template>
