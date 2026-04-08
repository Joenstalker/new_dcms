<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import ThemeSwitcher from '@/Components/ThemeSwitcher.vue';
import NotificationBell from '@/Components/NotificationBell.vue';
import ProfileDropdown from '@/Components/ProfileDropdown.vue';
import Dropdown from '@/Components/Dropdown.vue';
import { brandingState } from '@/States/brandingState';

const page = usePage();
const user = computed(() => page.props.auth.user);
const branding = computed(() => page.props.branding || {});
const adminPreviewMode = computed(() => page.props.admin_preview_mode || { active: false });
const previewCredentials = computed(() => page.props.preview_credentials || null);

// Initialize brandingState for admin context so ProfileDropdown uses admin system color
brandingState.initialize(page.props);

// Sidebar position (left or right)
const sidebarPosition = computed(() => branding.value.sidebar_position || 'left');
const isRightSidebar = computed(() => sidebarPosition.value === 'right');

// Primary color with auto contrast calculation
const primaryColor = computed(() => branding.value.primary_color || '#0ea5e9');

// Calculate text color based on primary color luminance
const primaryTextColor = computed(() => {
    const color = primaryColor.value.replace('#', '');
    const r = parseInt(color.substr(0, 2), 16);
    const g = parseInt(color.substr(2, 2), 16);
    const b = parseInt(color.substr(4, 2), 16);
    const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
    return luminance > 0.5 ? '#1f2937' : '#ffffff';
});

// Platform info
const platformName = computed(() => branding.value.platform_name || 'DCMS');
const platformLogo = computed(() => branding.value.platform_logo ? '/storage/logos/' + branding.value.platform_logo : '/images/dcms-logo.png');
const footerText = computed(() => branding.value.footer_text || '© 2026 DCMS. All rights reserved.');

const isSidebarOpen = ref(false);
const isDesktopSidebarOpen = ref(true);

const menuItems = [
    { 
        name: 'Dashboard', 
        route: 'admin.dashboard', 
        icon: 'home',
        active: true 
    },
    { 
        name: 'Tenants', 
        route: 'admin.tenants.index', 
        icon: 'building',
        badge: null,
        active: false
    },
    { 
        name: 'Subscription Plans', 
        route: 'admin.plans.index', 
        icon: 'tag',
        active: false
    },
    { 
        name: 'Subscriptions', 
        route: 'admin.subscriptions.index', 
        icon: 'credit-card',
        active: false
    },
    {
        name: 'Billing & Revenue',
        route: 'admin.revenue.index',
        icon: 'cash',
        active: false
    },
    {
        name: 'Platform Analytics',
        route: 'admin.analytics.index',
        icon: 'chart',
        active: false
    },
    {
        name: 'Feature Management',
        route: 'admin.features.index',
        icon: 'toggle',
        active: false
    },
    {
        name: 'Support & Tickets',
        route: 'admin.support.index',
        icon: 'ticket',
        active: false
    },
    {
        name: 'Notifications',
        route: 'admin.notifications.index',
        icon: 'bell',
        active: false
    },
    {
        name: 'Audit Logs',
        route: 'admin.audit-logs.index',
        icon: 'shield',
        active: false
    },
    {
        name: 'System Settings',
        route: 'admin.system-settings.index',
        icon: 'cog',
        active: false
    },
];

const getIcon = (name) => {
    const icons = {
        home: `<path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />`,
        building: `<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />`,
        tag: `<path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.659A2.25 2.25 0 0 0 9.568 3Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z" />`,
        'credit-card': `<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" />`,
        cash: `<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />`,
        chart: `<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />`,
        toggle: `<path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" />`,
        ticket: `<path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 0 1 0 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 0 1 0-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375Z" />`,
        bell: `<path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />`,
        shield: `<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />`,
        cog: `<path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 0 0-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 0 0-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 0 0-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 0 0-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 0 0 1.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />`,
    };
    return icons[name] || '';
};

const isCurrentRoute = (routeName) => {
    if (!routeName) return false;
    try {
        return route().current(routeName);
    } catch {
        return false;
    }
};

const copyToClipboard = async (value) => {
    if (!value) return;

    try {
        await navigator.clipboard.writeText(value);
    } catch {
        // No-op fallback: keep UX quiet if clipboard API is unavailable.
    }
};

// Global SweetAlert2 Handler for Flash Messages
import Swal from 'sweetalert2';
import { watch } from 'vue';

const handleLogout = () => {
    Swal.fire({
        title: 'Are you sure?',
        text: 'You will be logged out of the admin dashboard.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0d9488',
        cancelButtonColor: '#6b7280',
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

watch(() => page.props.flash, (flash) => {
    // Detect open native dialogs for proper layering
    const target = document.querySelector('dialog[open]') || 'body';

    if (flash?.success) {
        Swal.fire({
            target: target,
            title: 'Success!',
            text: flash.success,
            icon: 'success',
            timer: 3000,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
        });
    }
    if (flash?.error) {
        Swal.fire({
            target: target,
            title: 'Error!',
            text: flash.error,
            icon: 'error',
            confirmButtonColor: primaryColor.value,
        });
    }
    if (flash?.warning) {
        Swal.fire({
            target: target,
            title: 'Warning!',
            text: flash.warning,
            icon: 'warning',
            confirmButtonColor: primaryColor.value,
        });
    }
    if (flash?.info) {
        Swal.fire({
            target: target,
            title: 'Information',
            text: flash.info,
            icon: 'info',
            timer: 3000,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
        });
    }
}, { deep: true, immediate: true });
</script>

<template>
    <div class="drawer font-sans h-screen overflow-hidden" :class="{ 'drawer-end': isRightSidebar, 'lg:drawer-open': isDesktopSidebarOpen }">
        <input id="admin-sidebar" type="checkbox" v-model="isSidebarOpen" class="drawer-toggle" />
        
        <!-- Main Content Area -->
        <div class="drawer-content flex flex-col h-screen bg-base-200 overflow-hidden">
            <!-- Top Bar -->
            <header class="bg-base-100 border-b border-base-300 sticky top-0 z-30 h-16 flex items-center px-4 sm:px-6 lg:px-8 shrink-0 shadow-sm transition-all duration-300">
                <div class="flex items-center mr-4">
                    <!-- Mobile Hamburger -->
                    <label 
                        for="admin-sidebar"
                        class="btn btn-ghost btn-sm btn-square text-base-content/50 lg:hidden"
                    >
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </label>

                    <!-- Desktop Sidebar Toggle -->
                    <button 
                        @click="isDesktopSidebarOpen = !isDesktopSidebarOpen"
                        class="btn btn-ghost btn-sm btn-square text-base-content/50 hidden lg:flex"
                        v-if="!isDesktopSidebarOpen"
                        title="Open Sidebar"
                    >
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>

                <div v-if="$slots.header" class="flex-1 min-w-0">
                    <slot name="header" />
                </div>

                <div class="flex items-center space-x-3 ml-4">
                    <span class="text-[10px] uppercase font-bold tracking-widest text-base-content/30 hidden sm:inline">Admin System</span>

                    <Dropdown align="right" width="64" content-classes="py-1 bg-base-100">
                        <template #trigger>
                            <button
                                type="button"
                                class="inline-flex items-center px-3 py-1.5 rounded-lg border border-base-300 text-xs font-semibold text-base-content/70 hover:text-base-content hover:bg-base-200 transition-colors"
                            >
                                Preview Tools
                                <svg class="w-3.5 h-3.5 ml-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </template>

                        <template #content>
                            <div class="p-2 space-y-1 text-sm">
                                <Link
                                    :href="route('admin.dashboard')"
                                    class="flex w-full items-center rounded-md px-3 py-2 font-medium hover:bg-base-200"
                                >
                                    Admin System
                                </Link>

                                <a
                                    :href="route('admin.tenant-preview.open')"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="flex w-full items-center rounded-md px-3 py-2 font-medium text-emerald-700 hover:bg-emerald-50"
                                >
                                    Open Preview Sandbox
                                </a>

                                <Link
                                    v-if="adminPreviewMode.active && adminPreviewMode.is_isolated"
                                    :href="route('admin.tenant-preview.reset')"
                                    method="post"
                                    as="button"
                                    class="flex w-full items-center rounded-md px-3 py-2 font-medium text-amber-700 hover:bg-amber-50"
                                >
                                    Reset Preview Sandbox
                                </Link>

                                <Link
                                    v-if="adminPreviewMode.active"
                                    :href="route('central.preview-exit')"
                                    method="post"
                                    as="button"
                                    class="flex w-full items-center rounded-md px-3 py-2 font-medium text-red-600 hover:bg-red-50"
                                >
                                    Clear Preview State
                                </Link>

                                <div v-if="previewCredentials" class="mt-2 border-t border-base-300 pt-2">
                                    <p class="px-3 pb-1 text-[10px] uppercase tracking-wider text-base-content/50 font-semibold">Preview Credentials</p>
                                    <div class="flex items-center justify-between gap-2 px-3 py-1 text-xs">
                                        <span class="text-base-content/70 truncate">Email: {{ previewCredentials.email }}</span>
                                        <button type="button" @click="copyToClipboard(previewCredentials.email)" class="text-xs font-semibold text-emerald-700 hover:text-emerald-800">Copy</button>
                                    </div>
                                    <div class="flex items-center justify-between gap-2 px-3 py-1 text-xs">
                                        <span class="text-base-content/70 truncate">Pass: {{ previewCredentials.password }}</span>
                                        <button type="button" @click="copyToClipboard(previewCredentials.password)" class="text-xs font-semibold text-emerald-700 hover:text-emerald-800">Copy</button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Dropdown>

                    <ThemeSwitcher />
                    <NotificationBell type="admin" />
                    <ProfileDropdown />
                </div>
            </header>

            <!-- Content Area -->
            <main class="flex-1 overflow-y-auto custom-scrollbar p-4 lg:p-8">
                <div class="max-w-[1600px] mx-auto">
                    <slot />
                </div>
            </main>

            <!-- Footer -->
            <footer class="bg-base-100 border-t border-base-300 py-4 px-8">
                <p class="text-[10px] text-center font-bold uppercase tracking-widest text-base-content/30">{{ footerText }}</p>
            </footer>
        </div>

        <!-- Sidebar -->
        <!-- Reduced z-index from z-[100] to z-40 so standard z-50 modals overlay it -->
        <div class="drawer-side z-40 overflow-hidden custom-scrollbar">
            <label for="admin-sidebar" aria-label="close sidebar" class="drawer-overlay"></label>
            <aside class="w-72 h-full bg-base-100 border-r border-base-300 flex flex-col shadow-xl lg:shadow-none overflow-hidden custom-scrollbar">
                <!-- Sidebar Header -->
                <div class="flex items-center px-4 h-16 bg-base-100/50 backdrop-blur-md border-b border-base-300 shrink-0">
                    <div class="flex items-center space-x-4 flex-1">
                        <div class="h-10 w-10 rounded-xl overflow-hidden shadow-inner border border-base-300 bg-base-200 flex items-center justify-center p-1.5 flex-shrink-0">
                            <img :src="platformLogo" :alt="platformName" class="h-full w-full object-contain" />
                        </div>
                        <div class="truncate">
                            <span class="text-sm font-black tracking-tight text-base-content block truncate">{{ platformName }}</span>
                            <span class="text-[9px] uppercase tracking-[0.2em] font-black opacity-40 block" :style="{ color: primaryColor }">Core Infrastructure</span>
                        </div>
                    </div>
                    
                    <!-- Close button for desktop -->
                    <button 
                        @click="isDesktopSidebarOpen = false"
                        class="btn btn-ghost btn-sm btn-circle text-base-content/30 hover:text-base-content hidden lg:flex shrink-0"
                        title="Close Sidebar"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                        </svg>
                    </button>
                    <!-- Close button for mobile -->
                    <label 
                        for="admin-sidebar" 
                        class="btn btn-ghost btn-sm btn-circle text-base-content/30 hover:text-base-content lg:hidden shrink-0"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </label>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 px-4 py-4 space-y-1 overflow-hidden">
                    <template v-for="item in menuItems" :key="item.name">
                        <!-- Active link (has route) -->
                        <Link
                            v-if="item.route"
                            :href="route(item.route)"
                            @click="isSidebarOpen = false"
                            :class="[
                                isCurrentRoute(item.route) 
                                    ? 'shadow-lg shadow-primary/20 scale-[1.02]' 
                                    : 'text-base-content/60 hover:bg-base-200 hover:text-base-content'
                            ]"
                            class="flex items-center px-5 py-2.5 rounded-2xl group transition-all duration-300 transform"
                            :style="isCurrentRoute(item.route) ? { backgroundColor: primaryColor, color: primaryTextColor } : {}"
                        >
                            <svg 
                                class="h-5 w-5 mr-4 transition-transform duration-300 group-hover:scale-110" 
                                :class="[isCurrentRoute(item.route) ? '' : 'opacity-40 group-hover:opacity-100']"
                                fill="none" 
                                viewBox="0 0 24 24" 
                                stroke-width="2" 
                                stroke="currentColor"
                                v-html="getIcon(item.icon)"
                            ></svg>
                            <span class="font-bold text-xs uppercase tracking-wider">{{ item.name }}</span>
                            <div v-if="isCurrentRoute(item.route)" class="ml-auto w-1.5 h-1.5 rounded-full bg-white shadow-sm"></div>
                        </Link>

                        <!-- Disabled link (coming soon) -->
                        <div
                            v-else
                            class="flex items-center px-5 py-2.5 rounded-2xl opacity-20 cursor-not-allowed grayscale"
                            :title="`${item.name} — Coming Soon`"
                        >
                            <svg 
                                class="h-5 w-5 mr-4" 
                                fill="none" 
                                viewBox="0 0 24 24" 
                                stroke-width="2.5" 
                                stroke="currentColor"
                                v-html="getIcon(item.icon)"
                            ></svg>
                            <span class="font-bold text-xs uppercase tracking-widest">{{ item.name }}</span>
                        </div>
                    </template>
                </nav>

                <!-- User Footer -->
                <div class="p-4 bg-base-200/50 border-t border-base-300">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-4 min-w-0">
                            <div 
                                class="h-11 w-11 rounded-2xl flex items-center justify-center text-white font-black flex-shrink-0 shadow-lg overflow-hidden"
                                :style="{ backgroundColor: primaryColor }"
                            >
                                <img 
                                    v-if="user?.profile_picture_url && !user.profile_picture_url.includes('ui-avatars')" 
                                    :src="user.profile_picture_url" 
                                    class="h-full w-full object-cover"
                                    :alt="user?.name"
                                />
                                <span v-else>{{ user?.name?.charAt(0) || 'A' }}</span>
                            </div>
                            <div class="truncate">
                                <p class="text-xs font-black truncate text-base-content">{{ user?.name || 'Administrator' }}</p>
                                <p class="text-[9px] uppercase tracking-widest font-bold opacity-40">System Root</p>
                            </div>
                        </div>
                        <button 
                            @click="handleLogout"
                            class="btn btn-ghost btn-sm btn-circle text-base-content/30 hover:text-error hover:bg-error/10"
                            title="Logout"
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
