<script setup>
import { ref, computed } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import ThemeSwitcher from '@/Components/ThemeSwitcher.vue';
import NotificationBell from '@/Components/NotificationBell.vue';
import ProfileDropdown from '@/Components/ProfileDropdown.vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

const page = usePage();
const user = computed(() => page.props.auth.user);
const roles = computed(() => user.value?.roles || []);
const branding = computed(() => page.props.branding || {});

const showUpgradeAlert = (featureName) => {
    Swal.fire({
        title: 'Premium Feature',
        text: `The ${featureName} feature is not included in your current plan. Upgrade your subscription to unlock this tool and grow your clinic!`,
        icon: 'info',
        showCancelButton: true,
        confirmButtonColor: primaryColor.value,
        cancelButtonColor: '#94a3b8',
        confirmButtonText: 'View Upgrades',
        cancelButtonText: 'Cancel',
    }).then((result) => {
        if (result.isConfirmed) {
            router.get(route('settings.features'));
        }
    });
};

// Sidebar position (left or right)
const sidebarPosition = computed(() => branding.value.sidebar_position || 'left');
const isRightSidebar = computed(() => sidebarPosition.value === 'right');

// Check if custom branding should be applied to the current user
const shouldApplyBranding = computed(() => {
    // If Admin/Owner, always apply branding
    if (roles.value.includes('Owner') || roles.value.includes('Admin')) return true;
    
    const config = page.props.tenant?.portal_config || { apply_to: 'all', selected_staff: [] };
    
    if (config.apply_to === 'all') return true;
    
    if (config.apply_to === 'specific') {
        return config.selected_staff.includes(user.value.id);
    }
    
    return true; // Default to true if config is missing
});

// Primary color: Use tenant's branding_color if allowed, otherwise fallback to a standard tenant default
const primaryColor = computed(() => {
    // Standard platform default (e.g., Sky 500) for all tenants who haven't set their own
    const tenantDefault = '#0ea5e9';
    
    if (!shouldApplyBranding.value) return tenantDefault;
    return page.props.tenant?.branding_color || tenantDefault;
});

// Typography: Use tenant's granular font_family if allowed
const fonts = computed(() => {
    const defaults = {
        header: 'font-sans',
        sidebar: 'font-sans',
        names: 'font-sans',
        general: 'font-sans'
    };
    
    const sysFont = branding.value.font_family || 'font-sans';
    const sysDefaults = {
        header: sysFont,
        sidebar: sysFont,
        names: sysFont,
        general: sysFont
    };

    if (!shouldApplyBranding.value) return sysDefaults;
    
    const tenantFonts = page.props.tenant?.font_family;
    
    // Handle old string format or missing fonts
    if (typeof tenantFonts === 'string') {
        return { ...defaults, general: tenantFonts };
    }
    
    return { ...defaults, ...tenantFonts };
});

// Platform info
const platformName = computed(() => page.props.tenant?.name || branding.value.platform_name || 'DCMS');
const platformLogo = computed(() => {
    if (page.props.tenant?.logo_path) return '/tenancy/assets/' + page.props.tenant.logo_path;
    return branding.value.platform_logo ? '/storage/logos/' + branding.value.platform_logo : null;
});
const footerText = computed(() => branding.value.footer_text || '© 2026 DCMS. All rights reserved.');

const isSidebarOpen = ref(false);

const dashboardRoute = computed(() => usePage().props.tenant ? 'tenant.dashboard' : 'dashboard');

// Check if a sidebar item (with sub-items) is active
const isItemActive = (item) => {
    if (!item.subItems) return item.route ? route().current(item.route) : false;
    return item.subItems.some(si => route().current(si.route));
};

// Get the target route for a sidebar item
const getItemRoute = (item) => {
    if (item.route) return item.route;
    if (item.subItems && item.subItems.length > 0) return item.subItems[0].route;
    return dashboardRoute.value;
};

// Safely read tab query param (SSR-safe)
const currentTabParam = computed(() => {
    if (typeof window === 'undefined') return null;
    return new URLSearchParams(window.location.search).get('tab');
});

// Check if a sub-item tab is active
const isSubItemActive = (sub) => {
    if (sub.routeParams?.tab) return currentTabParam.value === sub.routeParams.tab;
    return route().current(sub.route) && !currentTabParam.value;
};

const menuCategories = computed(() => {
    const categories = [
        {
            title: 'Main Navigation',
            items: [
                { name: 'Dashboard', route: dashboardRoute.value, icon: 'home', feature: 'dashboard' },
                { name: 'Appointments', route: 'appointments.index', icon: 'calendar', feature: 'appointments', permissions: ['view appointments'] },
                { name: 'Patients', icon: 'users', route: 'patients.index', feature: 'patients', permissions: ['view patients'] },
                { name: 'Billing & POS', icon: 'cash', feature: 'billing', permissions: ['view billing'] },
                { name: 'Treatment Records', route: 'treatments.index', icon: 'calendar', feature: 'treatments', permissions: ['view treatments'] },
            ]
        },
        {
            title: 'Management',
            items: [
                { 
                    name: 'Staff Management', 
                    icon: 'staff', 
                    feature: 'staff', 
                    permissions: ['view staff'],
                    subItems: [
                        { name: 'Staff Overview', route: 'staff.index', permissions: ['view staff'] },
                        { name: 'Team Schedules', route: 'staff.index', routeParams: { tab: 'schedules' }, permissions: ['view staff'] },
                    ]
                },
                { name: 'Service & Pricing', route: 'services.index', icon: 'tag', feature: 'services', permissions: ['view services'] },
            ]
        },
        {
            title: 'Reports',
            items: [
                { 
                    name: 'Reports', 
                    icon: 'chart', 
                    permissions: ['view reports'],
                    subItems: [
                        { name: 'Revenue Report', route: 'reports.index', permissions: ['view reports'] },
                        { name: 'Enhanced Reports', route: 'reports.index', permissions: ['view reports'], featureKey: 'enhanced_reports' },
                        { name: 'Appointments Report', route: 'reports.index', permissions: ['view reports'] },
                    ]
                },
                { name: 'Analytics', route: 'analytics.index', icon: 'analytics', permissions: ['view reports'], featureKey: 'advanced_analytics' },
                { name: 'Notifications', route: 'notifications.index', icon: 'bell', permissions: ['view notifications'], featureKey: 'sms_notifications' },
            ]
        },
        {
            title: 'System',
            items: [
                { 
                    name: 'Custom Branding', 
                    icon: 'paint', 
                    route: 'custom-branding.index', 
                    permissions: ['manage settings'],
                    featureKey: 'custom_branding'
                },
                { 
                    name: 'Settings', 
                    icon: 'cog', 
                    permissions: ['manage settings'],
                    subItems: [
                        { name: 'Billing & Plan', route: 'settings.index', permissions: ['manage settings'] },
                        { name: 'Premium Features', route: 'settings.features', permissions: ['manage settings'], featureKey: 'custom_system_features' },
                        { name: 'Updates', route: 'settings.updates', permissions: ['manage settings'], icon: 'refresh' },
                    ]
                },
                { name: 'Branches', route: 'branches.index', icon: 'branch', permissions: ['manage settings'], featureKey: 'multi_branch' },
            ]
        }
    ];

    const processItems = (categories) => {
        return categories.map(cat => ({
            ...cat,
            items: cat.items.filter(item => {
                const hasRole = item.roles ? item.roles.some(role => roles.value.includes(role)) : false;
                const hasPermission = item.permissions ? item.permissions.some(p => user.value.permissions.includes(p)) : false;
                const isOwner = roles.value.includes('Owner');
                const hasRoute = item.route ? route().has(item.route) : true;
                const noAuthDefined = !item.roles && !item.permissions;
                
                return (isOwner || hasRole || hasPermission || noAuthDefined) && hasRoute;
            }).map(item => {
                const isLocked = item.featureKey ? !page.props.tenant_plan?.features?.[item.featureKey] : false;
                return {
                    ...item,
                    isLocked,
                    subItems: item.subItems?.filter(si => {
                        const hasRole = si.roles ? si.roles.some(role => roles.value.includes(role)) : false;
                        const hasPermission = si.permissions ? si.permissions.some(p => user.value.permissions.includes(p)) : false;
                        const isOwner = roles.value.includes('Owner');
                        const hasRoute = si.route ? route().has(si.route) : true;
                        const noAuthDefined = !si.roles && !si.permissions;
                        
                        return (isOwner || hasRole || hasPermission || noAuthDefined) && hasRoute;
                    }).map(si => {
                        const isSubLocked = si.featureKey ? !page.props.tenant_plan?.features?.[si.featureKey] : false;
                        return { ...si, isLocked: isSubLocked };
                    })
                };
            }).filter(item => item.subItems ? item.subItems.length > 0 : true)
        })).filter(cat => cat.items.length > 0 && (cat.roles ? cat.roles.some(role => roles.value.includes(role)) : true));
    };

    return processItems(categories);
});

const getIcon = (name) => {
    const icons = {
        lock: `<path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />`,
        home: `<path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />`,
        calendar: `<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />`,
        users: `<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />`,
        cash: `<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75m0 1.5v.75m0 1.5v.75m0 1.5V15m1.5 1.5h15m-15-1.5a1.5 1.5 0 0 0-1.5-1.5V6a1.5 1.5 0 0 0 1.5-1.5H20.25a1.5 1.5 0 0 1 1.5 1.5v9a1.5 1.5 0 0 1-1.5 1.5H5.25Zm1.5-1.5h15M5.25 15h15" />`,
        tag: `<path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.659A2.25 2.25 0 0 0 9.568 3Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z" />`,
        chart: `<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />`,
        cog: `<path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 0 0-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 0 0-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 0 0-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 0 0-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 0 0 1.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />`,
        diamond: `<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />`,
        analytics: `<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5M9 11.25v-5.5m3 5.5V8.75m3 2.5V10.5" />`,
        branch: `<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3H21m-3.75 3H21" />`,
        paint: `<path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 00-5.78 1.128 2.25 2.25 0 01-2.4 2.245 4.5 4.5 0 008.4-2.245c0-.399-.078-.78-.22-1.128zm0 0a15.998 15.998 0 003.388-1.62m-5.043-.025a15.994 15.994 0 011.622-3.395m3.42 3.42a15.995 15.995 0 004.764-4.648l3.876-5.814a1.151 1.151 0 00-1.597-1.597L14.146 6.32a15.996 15.996 0 00-4.649 4.763m3.42 3.42a6.776 6.776 0 00-3.42-3.42" />`,
        bell: `<path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />`,
    };
    return icons[name] || '';
};

const activeCategoryWithSubItems = computed(() => {
    for (const category of menuCategories.value) {
        for (const item of category.items) {
            if (item.subItems && item.subItems.some(si => route().current(si.route))) {
                return item;
            }
        }
    }
    return null;
});

const handleLogout = () => {
    Swal.fire({
        title: 'Are you sure?',
        text: 'You will be logged out of your account.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: primaryColor.value,
        cancelButtonColor: '#94a3b8',
        confirmButtonText: 'Yes, Log out',
        cancelButtonText: 'Cancel',
    }).then((result) => {
        if (result.isConfirmed) {
            // Perform logout via POST request
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/logout';
            
            const token = document.querySelector('meta[name="csrf-token"]');
            if (token) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = '_token';
                input.value = token.content;
                form.appendChild(input);
            }
            
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        }
    });
};

// Update CSS variables for DaisyUI integration
const brandStyle = computed(() => {
    const color = primaryColor.value;
    
    // Inject CSS variables for primary color to handle bg-primary, text-primary, etc.
    return `
        :root {
            --p: ${hexToHsl(color)};
            --pf: ${hexToHsl(darkenColor(color, 20))};
            --pc: ${getContrastColor(color)};
        }
    `;
});

// Helper functions for color conversion
function hexToHsl(hex) {
    let r = 0, g = 0, b = 0;
    if (hex.length === 4) {
        r = "0x" + hex[1] + hex[1];
        g = "0x" + hex[2] + hex[2];
        b = "0x" + hex[3] + hex[3];
    } else if (hex.length === 7) {
        r = "0x" + hex[1] + hex[2];
        g = "0x" + hex[3] + hex[4];
        b = "0x" + hex[5] + hex[6];
    }
    r /= 255; g /= 255; b /= 255;
    let cmin = Math.min(r, g, b), cmax = Math.max(r, g, b), delta = cmax - cmin, h = 0, s = 0, l = 0;
    if (delta == 0) h = 0;
    else if (cmax == r) h = ((g - b) / delta) % 6;
    else if (cmax == g) h = (b - r) / delta + 2;
    else h = (r - g) / delta + 4;
    h = Math.round(h * 60);
    if (h < 0) h += 360;
    l = (cmax + cmin) / 2;
    s = delta == 0 ? 0 : delta / (1 - Math.abs(2 * l - 1));
    s = +(s * 100).toFixed(1);
    l = +(l * 100).toFixed(1);
    return `${h} ${s}% ${l}%`;
}

function darkenColor(hex, percent) {
    const num = parseInt(hex.replace("#",""),16),
    amt = Math.round(2.55 * percent),
    R = (num >> 16) - amt,
    G = (num >> 8 & 0x00FF) - amt,
    B = (num & 0x0000FF) - amt;
    return "#" + (0x1000000 + (R<255?R<0?0:R:255)*0x10000 + (G<255?G<0?0:G:255)*0x100 + (B<255?B<0?0:B:255)).toString(16).slice(1);
}

function getContrastColor(hex) {
    const color = hex.replace('#', '');
    const r = parseInt(color.substr(0, 2), 16);
    const g = parseInt(color.substr(2, 2), 16);
    const b = parseInt(color.substr(4, 2), 16);
    const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
    return luminance > 0.5 ? '0 0% 12%' : '0 0% 100%'; // HSL format for DaisyUI
}
</script>

<template>
    <div 
        class="drawer lg:drawer-open h-screen overflow-hidden" 
        :class="[fonts.general, { 'drawer-end': isRightSidebar }]"
    >
        <component :is="'style'" v-if="brandStyle">{{ brandStyle }}</component>
        <input id="tenant-sidebar" type="checkbox" v-model="isSidebarOpen" class="drawer-toggle" />
        
        <!-- Main Content Area -->
        <div class="drawer-content flex flex-col h-screen bg-base-200 overflow-hidden">
            <!-- Top Navigation for Mobile & Title -->
            <header class="bg-base-100 border-b border-base-300 sticky top-0 z-40 h-16 flex items-center justify-between px-4 sm:px-6 lg:px-8 shrink-0 shadow-sm">
                <div class="flex items-center space-x-4">
                    <label 
                        for="tenant-sidebar"
                        class="btn btn-ghost btn-sm btn-square lg:hidden text-base-content/50"
                    >
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </label>
                    <div v-if="$slots.header" class="flex-1 min-w-0" :class="fonts.header">
                        <slot name="header" />
                    </div>
                </div>

                <div class="flex items-center space-x-4">
                    <ThemeSwitcher />
                    <NotificationBell type="tenant" />
                    <ProfileDropdown />
                </div>
            </header>

            <!-- Tab Navigation (Dynamic Sub-menus) -->
            <nav 
                v-if="activeCategoryWithSubItems"
                class="bg-base-100 border-b border-base-300 sticky top-16 z-30 px-4 sm:px-6 lg:px-8 overflow-x-auto custom-scrollbar flex-shrink-0"
            >
                <div class="flex space-x-8">
                    <Link 
                        v-for="sub in activeCategoryWithSubItems.subItems" 
                        :key="sub.name"
                        :href="route(sub.route, sub.routeParams || {})"
                        :class="[
                            isSubItemActive(sub)
                                ? 'font-black border-primary' 
                                : 'border-transparent text-base-content/40 hover:text-base-content/70 hover:border-base-300',
                            fonts.sidebar
                        ]"
                    >
                        {{ sub.name }}
                    </Link>
                </div>
            </nav>

            <!-- Content Area -->
            <main class="flex-1 overflow-y-auto custom-scrollbar p-4 lg:p-8">
                <div class="max-w-[1600px] mx-auto">
                    <slot />
                </div>
            </main>

            <!-- Footer -->
            <footer class="bg-base-100 border-t border-base-300 py-4 px-8 mt-auto">
                <p class="text-[10px] text-center font-bold uppercase tracking-widest text-base-content/20">{{ footerText }}</p>
            </footer>
        </div>

        <!-- Sidebar -->
        <div class="drawer-side z-[100] overflow-hidden custom-scrollbar">
            <label for="tenant-sidebar" aria-label="close sidebar" class="drawer-overlay"></label>
            <aside class="w-72 h-full bg-base-100 border-r border-base-300 flex flex-col shadow-2xl lg:shadow-none transition-colors duration-300 overflow-hidden custom-scrollbar">
                <!-- Sidebar Header -->
                <div class="flex items-center px-6 h-16 bg-base-100 border-b border-base-300">
                    <Link :href="usePage().props.tenant ? route('tenant.dashboard') : route('dashboard')" class="flex items-center space-x-4">
                        <div class="h-10 w-10 rounded-xl overflow-hidden shadow-inner border border-base-300 bg-base-200 flex items-center justify-center p-1.5">
                            <img v-if="platformLogo" :src="platformLogo" :alt="platformName" class="h-full w-full object-contain" />
                            <ApplicationLogo v-else class="h-7 w-7 fill-current" :style="{ color: primaryColor }" />
                        </div>
                        <div class="truncate">
                            <span class="text-sm font-black tracking-tight text-base-content block truncate" :class="fonts.header">{{ platformName }}</span>
                            <span class="text-[9px] uppercase tracking-[0.2em] font-black opacity-30 block" :class="fonts.header" :style="{ color: primaryColor }">Professional Portal</span>
                        </div>
                    </Link>
                </div>

                <!-- Navigation Categories -->
                <nav class="flex-1 px-4 py-4 space-y-4 overflow-y-auto custom-scrollbar">
                    <div v-for="category in menuCategories" :key="category.title">
                        <h3 class="px-5 mb-2 text-[10px] font-black text-base-content/20 uppercase tracking-[0.25em]">{{ category.title }}</h3>
                        <div class="space-y-1.5">
                            <Link 
                                v-for="item in category.items" 
                                :key="item.name"
                                :href="item.isLocked ? '#' : route(getItemRoute(item))"
                                @click="item.isLocked ? ($event.preventDefault(), showUpgradeAlert(item.name)) : (isSidebarOpen = false)"
                                :class="[
                                    isItemActive(item) && !item.isLocked
                                        ? 'shadow-lg shadow-primary/20 scale-[1.02]' 
                                        : 'text-base-content/60 hover:bg-base-200 hover:text-base-content',
                                    item.isLocked ? 'opacity-70 hover:opacity-100 bg-base-100/50' : ''
                                ]"
                                class="flex items-center px-5 py-2.5 rounded-2xl group transition-all duration-300 transform"
                                :style="isItemActive(item) && !item.isLocked ? { backgroundColor: primaryColor, color: '#ffffff' } : {}"
                            >
                                <svg 
                                    class="h-5 w-5 mr-4 transition-transform duration-300 group-hover:scale-110 flex-shrink-0" 
                                    :class="[isItemActive(item) && !item.isLocked ? 'text-white' : (item.isLocked ? 'text-warning opacity-100' : 'opacity-40 group-hover:opacity-100')]"
                                    fill="none" 
                                    viewBox="0 0 24 24" 
                                    stroke-width="2" 
                                    stroke="currentColor"
                                    v-html="item.isLocked ? getIcon('lock') : getIcon(item.icon)"
                                ></svg>
                                <span class="font-bold text-xs uppercase tracking-wider truncate" :class="fonts.sidebar">{{ item.name }}</span>
                                <div v-if="isItemActive(item) && !item.isLocked" class="ml-auto w-1.5 h-1.5 rounded-full bg-white shadow-sm flex-shrink-0"></div>
                                <div v-if="item.isLocked" class="ml-auto inline-flex items-center justify-center px-1.5 py-0.5 rounded text-[8px] font-black uppercase tracking-widest bg-warning/20 text-warning flex-shrink-0">
                                    PRO
                                </div>
                            </Link>
                        </div>
                    </div>
                </nav>

                <!-- User Footer -->
                <div class="p-4 bg-base-200/50 border-t border-base-300">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-4 min-w-0">
                            <div 
                                class="h-11 w-11 rounded-2xl flex items-center justify-center text-white font-black flex-shrink-0 shadow-lg shadow-primary/10 overflow-hidden"
                                :style="{ backgroundColor: primaryColor }"
                            >
                                <img 
                                    v-if="user?.profile_picture_url" 
                                    :src="user.profile_picture_url" 
                                    class="h-full w-full object-cover"
                                    :alt="user?.name"
                                />
                                <span v-else>{{ user.name.charAt(0) }}</span>
                            </div>
                            <div class="truncate">
                                <p class="text-xs font-black truncate text-base-content" :class="fonts.names">{{ user.name }}</p>
                                <p class="text-[9px] uppercase tracking-[0.2em] font-black opacity-30" :class="fonts.header">{{ roles[0] || 'Staff' }}</p>
                            </div>
                        </div>
                        <button 
                            @click="handleLogout"
                            class="btn btn-ghost btn-sm btn-circle text-base-content/20 hover:text-error hover:bg-error/10"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                            </svg>
                        </button>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</template>

<style>
.custom-scrollbar::-webkit-scrollbar {
    width: 0px;
    background: transparent; /* Chrome/Safari/Webkit */
}
.custom-scrollbar {
    scrollbar-width: none; /* Firefox */
    -ms-overflow-style: none; /* IE/Edge */
}
</style>
