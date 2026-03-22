<script setup>
import { ref, computed } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import ThemeSwitcher from '@/Components/ThemeSwitcher.vue';
import NotificationBell from '@/Components/NotificationBell.vue';
import { Link, usePage } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

const page = usePage();
const user = computed(() => page.props.auth.user);
const roles = computed(() => user.value?.roles || []);
const branding = computed(() => page.props.branding || {});

// Sidebar position (left or right)
const sidebarPosition = computed(() => branding.value.sidebar_position || 'left');
const isRightSidebar = computed(() => sidebarPosition.value === 'right');

// Primary color with auto contrast calculation
const primaryColor = computed(() => branding.value.primary_color || '#0ea5e9');

// Platform info
const platformName = computed(() => branding.value.platform_name || 'DCMS');
const platformLogo = computed(() => branding.value.platform_logo ? '/storage/logos/' + branding.value.platform_logo : null);
const footerText = computed(() => branding.value.footer_text || '© 2026 DCMS. All rights reserved.');

const isSidebarOpen = ref(false);
const openSubMenus = ref({});

const dashboardRoute = computed(() => usePage().props.tenant ? 'tenant.dashboard' : 'dashboard');

const toggleSubMenu = (name) => {
    openSubMenus.value[name] = !openSubMenus.value[name];
};

const menuCategories = computed(() => {
    const categories = [
        {
            title: 'Main Navigation',
            items: [
                { name: 'Dashboard', route: dashboardRoute.value, icon: 'home' },
                { 
                    name: 'Appointments', 
                    icon: 'calendar', 
                    permissions: ['view appointments'],
                    subItems: [
                        { name: 'Calendar View', route: 'appointments.index', permissions: ['view appointments'] },
                        { name: 'Booking Queue', route: 'appointments.index', permissions: ['view appointments'] },
                        { name: 'Walk-in', route: 'appointments.index', permissions: ['create appointments'] },
                    ]
                },
                { 
                    name: 'Patients', 
                    icon: 'users', 
                    permissions: ['view patients'],
                    subItems: [
                        { name: 'Patient List', route: 'patients.index', permissions: ['view patients'] },
                        { name: 'Medical History', route: 'patients.index', permissions: ['view treatments'] },
                    ]
                },
                { 
                    name: 'Billing & POS', 
                    icon: 'cash', 
                    permissions: ['view billing'],
                    subItems: [
                        { name: 'Cashier / New Invoice', route: 'billing.index', permissions: ['create billing'] },
                        { name: 'Transactions', route: 'billing.index', permissions: ['view billing'] },
                        { name: 'Receipts', route: 'billing.index', permissions: ['view billing'] },
                    ]
                },
                { 
                    name: 'Treatment Records', 
                    route: 'treatments.index', 
                    icon: 'calendar', 
                    permissions: ['view treatments'] 
                },
            ]
        },
        {
            title: 'Management',
            items: [
                { 
                    name: 'Staff Management', 
                    icon: 'staff', 
                    permissions: ['view staff'],
                    subItems: [
                        { name: 'Staff List', route: 'staff.index', permissions: ['view staff'] },
                        { name: 'Roles & Permissions', route: 'staff.index', routeParams: { tab: 'permissions' }, permissions: ['edit staff'] },
                        { name: 'Add Staff', route: 'staff.create', permissions: ['create staff'] },
                        { name: 'Schedules', route: 'staff.schedules', permissions: ['view staff'] },
                    ]
                },
                { name: 'Service & Pricing', route: 'services.index', icon: 'tag', permissions: ['view services'] },
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
                        { name: 'Patient Report', route: 'reports.index', permissions: ['view reports'] },
                        { name: 'Appointments Report', route: 'reports.index', permissions: ['view reports'] },
                    ]
                },
            ]
        },
        {
            title: 'System',
            items: [
                { 
                    name: 'Settings', 
                    icon: 'cog', 
                    permissions: ['manage settings'],
                    subItems: [
                        { name: 'Clinic Profile', route: 'settings.index', permissions: ['manage settings'] },
                        { name: 'Operating Hours', route: 'settings.index', permissions: ['manage settings'] },
                        { name: 'QR Code Setup', route: 'settings.index', permissions: ['manage settings'] },
                        { name: 'Your Features', route: 'settings.features', permissions: ['manage settings'] },
                        { name: 'Updates', route: 'settings.updates', permissions: ['manage settings'], icon: 'refresh' },
                    ]
                },
                { name: 'Subscription', route: dashboardRoute.value, icon: 'diamond', permissions: ['manage subscription'] },
            ]
        }
    ];

    return categories.map(cat => ({
        ...cat,
        items: cat.items.filter(item => {
            const hasRole = item.roles ? item.roles.some(role => roles.value.includes(role)) : false;
            const hasPermission = item.permissions ? item.permissions.some(p => user.value.permissions.includes(p)) : false;
            const isOwner = roles.value.includes('Owner');
            const hasRoute = item.route ? route().has(item.route) : true;
            
            // If no roles/permissions defined, show to everyone (like Dashboard)
            const noAuthDefined = !item.roles && !item.permissions;
            
            return (isOwner || hasRole || hasPermission || noAuthDefined) && hasRoute;
        }).map(item => ({
            ...item,
            subItems: item.subItems?.filter(si => {
                const hasRole = si.roles ? si.roles.some(role => roles.value.includes(role)) : false;
                const hasPermission = si.permissions ? si.permissions.some(p => user.value.permissions.includes(p)) : false;
                const isOwner = roles.value.includes('Owner');
                const hasRoute = si.route ? route().has(si.route) : true;
                
                const noAuthDefined = !si.roles && !si.permissions;
                
                return (isOwner || hasRole || hasPermission || noAuthDefined) && hasRoute;
            })
        })).filter(item => item.subItems ? item.subItems.length > 0 : true)
    })).filter(cat => cat.items.length > 0 && (cat.roles ? cat.roles.some(role => roles.value.includes(role)) : true));
});

const getIcon = (name) => {
    const icons = {
        home: `<path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />`,
        calendar: `<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />`,
        users: `<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />`,
        cash: `<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75m0 1.5v.75m0 1.5v.75m0 1.5V15m1.5 1.5h15m-15-1.5a1.5 1.5 0 0 0-1.5-1.5V6a1.5 1.5 0 0 0 1.5-1.5H20.25a1.5 1.5 0 0 1 1.5 1.5v9a1.5 1.5 0 0 1-1.5 1.5H5.25Zm1.5-1.5h15M5.25 15h15" />`,
        staff: `<path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />`,
        tag: `<path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.659A2.25 2.25 0 0 0 9.568 3Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z" />`,
        chart: `<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />`,
        cog: `<path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 0 0-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 0 0-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 0 0-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 0 0-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 0 0 1.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />`,
        diamond: `<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />`,
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
            form.action = route('logout');
            
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
</script>

<template>
    <div class="drawer lg:drawer-open font-sans h-screen overflow-hidden" :class="{ 'drawer-end': isRightSidebar }">
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
                    <div v-if="$slots.header" class="flex-1 min-w-0">
                        <slot name="header" />
                    </div>
                </div>

                <div class="flex items-center space-x-4">
                    <ThemeSwitcher />
                    <NotificationBell type="tenant" />
                    <Link 
                        :href="route('profile.edit')"
                        class="flex items-center space-x-2 p-1.5 rounded-xl hover:bg-base-200 transition-all duration-200 border border-transparent hover:border-base-300"
                    >
                        <div 
                            class="h-8 w-8 rounded-lg flex items-center justify-center text-white font-black text-xs shadow-sm"
                            :style="{ backgroundColor: primaryColor }"
                        >
                            {{ user.name.charAt(0) }}
                        </div>
                        <span class="hidden sm:inline text-sm font-bold text-base-content/70">Account</span>
                    </Link>
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
                        :href="route(sub.route)"
                        :class="[
                            route().current(sub.route)
                                ? 'font-black border-primary' 
                                : 'border-transparent text-base-content/40 hover:text-base-content/70 hover:border-base-300'
                        ]"
                        :style="route().current(sub.route) ? { borderColor: primaryColor, color: primaryColor } : {}"
                        class="whitespace-nowrap py-4 px-1 border-b-2 text-[10px] uppercase tracking-widest transition-all duration-300"
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
                            <span class="text-sm font-black tracking-tight text-base-content block truncate">{{ platformName }}</span>
                            <span class="text-[9px] uppercase tracking-[0.2em] font-black opacity-30 block" :style="{ color: primaryColor }">Professional Portal</span>
                        </div>
                    </Link>
                </div>

                <!-- Navigation Categories -->
                <nav class="flex-1 px-4 py-4 space-y-4 overflow-hidden">
                    <div v-for="category in menuCategories" :key="category.title">
                        <h3 class="px-5 mb-2 text-[10px] font-black text-base-content/20 uppercase tracking-[0.25em]">{{ category.title }}</h3>
                        <div class="space-y-1.5">
                            <div v-for="item in category.items" :key="item.name">
                                <!-- Regular Link -->
                                <Link 
                                    v-if="!item.subItems"
                                    :href="route(item.route)"
                                    @click="isSidebarOpen = false"
                                    :class="[
                                        route().current(item.route) 
                                            ? 'shadow-lg shadow-primary/20 scale-[1.02]' 
                                            : 'text-base-content/60 hover:bg-base-200 hover:text-base-content'
                                    ]"
                                    class="flex items-center px-5 py-2.5 rounded-2xl group transition-all duration-300 transform"
                                    :style="route().current(item.route) ? { backgroundColor: primaryColor, color: '#ffffff' } : {}"
                                >
                                    <svg 
                                        class="h-5 w-5 mr-4 transition-transform duration-300 group-hover:scale-110" 
                                        :class="[route().current(item.route) ? 'text-white' : 'opacity-40 group-hover:opacity-100']"
                                        fill="none" 
                                        viewBox="0 0 24 24" 
                                        stroke-width="2" 
                                        stroke="currentColor"
                                        v-html="getIcon(item.icon)"
                                    ></svg>
                                    <span class="font-bold text-xs uppercase tracking-wider">{{ item.name }}</span>
                                </Link>

                                <!-- Collapsible Link -->
                                <div v-else>
                                    <button 
                                        @click="toggleSubMenu(item.name)"
                                        :class="[
                                            openSubMenus[item.name] ? 'bg-base-200/50 text-base-content' : 'text-base-content/60 hover:bg-base-200 hover:text-base-content'
                                        ]"
                                        class="w-full flex items-center justify-between px-5 py-2.5 rounded-2xl transition-all duration-300 group"
                                    >
                                        <div class="flex items-center">
                                            <svg 
                                                class="h-5 w-5 mr-4 opacity-40 group-hover:opacity-100 transition-all duration-300" 
                                                fill="none" 
                                                viewBox="0 0 24 24" 
                                                stroke-width="2" 
                                                stroke="currentColor"
                                                v-html="getIcon(item.icon)"
                                            ></svg>
                                            <span class="font-bold text-xs uppercase tracking-wider">{{ item.name }}</span>
                                        </div>
                                        <svg 
                                            :class="[openSubMenus[item.name] ? 'rotate-180' : '']"
                                            class="w-4 h-4 transition-transform duration-300 opacity-20" 
                                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                        </svg>
                                    </button>
                                    
                                    <!-- Sub-items -->
                                    <div 
                                        v-show="openSubMenus[item.name]"
                                        class="mt-1.5 ml-8 pl-4 space-y-1 border-l-2 border-base-200"
                                    >
                                        <Link 
                                            v-for="sub in item.subItems" 
                                            :key="sub.name"
                                            @click="isSidebarOpen = false"
                                            :href="route(sub.route, sub.routeParams || {})"
                                            :class="[
                                                route().current(sub.route, sub.routeParams || {}) ? 'font-black opacity-100' : 'text-base-content/50 hover:text-base-content hover:bg-base-200'
                                            ]"
                                            :style="route().current(sub.route, sub.routeParams || {}) ? { color: primaryColor } : {}"
                                            class="block py-2 px-4 rounded-xl text-[11px] uppercase tracking-widest transition-all duration-300"
                                        >
                                            {{ sub.name }}
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </nav>

                <!-- User Footer -->
                <div class="p-4 bg-base-200/50 border-t border-base-300">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-4 min-w-0">
                            <div 
                                class="h-11 w-11 rounded-2xl flex items-center justify-center text-white font-black flex-shrink-0 shadow-lg shadow-primary/10"
                                :style="{ backgroundColor: primaryColor }"
                            >
                                {{ user.name.charAt(0) }}
                            </div>
                            <div class="truncate">
                                <p class="text-xs font-black truncate text-base-content">{{ user.name }}</p>
                                <p class="text-[9px] uppercase tracking-[0.2em] font-black opacity-30">{{ roles[0] || 'Staff' }}</p>
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
