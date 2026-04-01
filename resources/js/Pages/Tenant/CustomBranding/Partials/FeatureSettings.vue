<script setup>
const props = defineProps({
    form: {
        type: Object,
        required: true
    }
});

const featuresList = [
    { id: 'appointments', name: 'Appointment Management', icon: '📅', description: 'Manage bookings, calendar, and walk-ins.' },
    { id: 'patients', name: 'Patient Records', icon: '👥', description: 'Database of patient profiles and medical history.' },
    { id: 'billing', name: 'Billing & POS', icon: '💳', description: 'Invoices, transactions, and payment management.' },
    { id: 'treatments', name: 'Treatment Records', icon: '🦷', description: 'Clinical notes and procedure tracking.' },
    { id: 'staff', name: 'Staff Management', icon: '👩‍⚕️', description: 'Employee profiles, schedules, and permissions.' },
    { id: 'services', name: 'Service & Pricing', icon: '🏷️', description: 'Manage your clinics offerings and price list.' },
    { id: 'reports', name: 'Revenue Reports', icon: '📈', description: 'Detailed financial and patient growth reports.' },
    { id: 'analytics', name: 'Advanced Analytics', icon: '📊', description: 'Deep business metrics and performance tracking.' },
    { id: 'notifications', name: 'SMS & Notifications', icon: '🔔', description: 'Manage patient communications and SMS logs.' },
    { id: 'logs', name: 'Activity Logs', icon: '🛡️', description: 'Audit trail of staff actions and system events.' },
    { id: 'branches', name: 'Multi-Branch Support', icon: '🏢', description: 'Manage and switch between different clinic branches.' },
];

const toggleFeature = (id) => {
    const index = props.form.enabled_features.indexOf(id);
    if (index === -1) {
        props.form.enabled_features.push(id);
    } else {
        // Prevent disabling mandatory features to avoid lockouts
        const mandatory = ['dashboard', 'settings', 'branding'];
        if (mandatory.includes(id)) return;
        props.form.enabled_features.splice(index, 1);
    }
};

const isEnabled = (id) => props.form.enabled_features.includes(id);
</script>

<template>
    <div class="space-y-8 animate-fade-in">
        <section class="space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-xs font-black text-primary uppercase tracking-[0.2em] flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-primary"></span>
                    Module Management
                </h1>
                <div class="badge badge-primary badge-sm font-black uppercase tracking-widest text-[8px] p-2 text-white">Dashboard & Settings are always enabled</div>
            </div>

            <p class="text-sm text-gray-500 max-w-2xl">
                Select which modules you want to display in your clinic's sidebar. Disabling a module will hide it for all users but will not delete any data.
            </p>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div 
                    v-for="feature in featuresList" 
                    :key="feature.id"
                    @click="toggleFeature(feature.id)"
                    class="p-5 rounded-3xl border transition-all cursor-pointer flex items-center gap-4 group"
                    :class="[
                        isEnabled(feature.id) 
                            ? 'bg-primary/5 border-primary shadow-sm' 
                            : 'bg-base-200 border-base-300 opacity-60 grayscale hover:grayscale-0 hover:opacity-100 hover:border-primary'
                    ]"
                >
                    <div class="w-12 h-12 rounded-2xl bg-white shadow-sm flex items-center justify-center text-xl shrink-0 group-hover:scale-110 transition-transform">
                        {{ feature.icon }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between font-black text-xs uppercase tracking-wider text-base-content">
                            {{ feature.name }}
                            <input 
                                type="checkbox" 
                                :checked="isEnabled(feature.id)" 
                                class="toggle toggle-primary toggle-sm"
                            >
                        </div>
                        <p class="text-[10px] text-base-content/50 mt-1 truncate">{{ feature.description }}</p>
                    </div>
                </div>
            </div>
        </section>

        <div class="bg-blue-50 p-4 rounded-2xl border border-blue-100 flex items-start gap-4">
            <span class="text-xl">💡</span>
            <div>
                <p class="text-xs font-bold text-blue-800">Pro Tip: Streamline your Workflow</p>
                <p class="text-[10px] text-blue-600 mt-1">If you don't use Billing or Staff Management yet, disable them to keep your sidebar clean and focused on patient care.</p>
            </div>
        </div>
    </div>
</template>
