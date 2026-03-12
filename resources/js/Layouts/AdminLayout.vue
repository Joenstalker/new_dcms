<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const user = computed(() => page.props.auth.user);

const isSidebarOpen = ref(false);

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
        route: null, 
        icon: 'cash',
        active: false
    },
    { 
        name: 'Platform Analytics', 
        route: null, 
        icon: 'chart',
        active: false
    },
    { 
        name: 'Feature Management', 
        route: null, 
        icon: 'toggle',
        active: false
    },
    { 
        name: 'Support & Tickets', 
        route: null, 
        icon: 'ticket',
        active: false
    },
    { 
        name: 'Notifications', 
        route: null, 
        icon: 'bell',
        active: false
    },
    { 
        name: 'Audit Logs', 
        route: null, 
        icon: 'shield',
        active: false
    },
    { 
        name: 'System Settings', 
        route: null, 
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

// Global SweetAlert2 Handler for Flash Messages
import Swal from 'sweetalert2';
import { watch } from 'vue';

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
            confirmButtonColor: '#0d9488',
        });
    }
    if (flash?.warning) {
        Swal.fire({
            target: target,
            title: 'Warning!',
            text: flash.warning,
            icon: 'warning',
            confirmButtonColor: '#0d9488',
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
    <div class="flex h-screen bg-gray-50 overflow-hidden font-sans">
        <!-- Sidebar -->
        <aside 
            :class="[isSidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0']"
            class="fixed inset-y-0 left-0 z-50 w-64 bg-slate-900 text-white transition-transform duration-300 ease-in-out lg:static lg:inset-0"
        >
            <div class="flex flex-col h-full">
                <!-- Sidebar Header -->
                <div class="flex items-center justify-center h-20 bg-slate-950 border-b border-slate-800">
                    <div class="flex items-center space-x-3">
                        <img src="/images/dcms-logo.png" alt="DCMS" class="h-9 w-9 rounded-lg" />
                        <div>
                            <span class="text-lg font-bold tracking-wider text-white">DCMS</span>
                            <p class="text-[10px] text-teal-400 uppercase tracking-widest font-semibold">Admin Portal</p>
                        </div>
                    </div>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 px-4 py-6 space-y-1 overflow-y-auto custom-scrollbar">
                    <template v-for="item in menuItems" :key="item.name">
                        <!-- Active link (has route) -->
                        <Link
                            v-if="item.route"
                            :href="route(item.route)"
                            :class="[
                                isCurrentRoute(item.route) 
                                    ? 'bg-teal-600 text-white shadow-lg shadow-teal-900/50' 
                                    : 'text-slate-400 hover:bg-slate-800 hover:text-white'
                            ]"
                            class="flex items-center px-4 py-2.5 rounded-xl group transition-all duration-200"
                        >
                            <svg 
                                class="h-5 w-5 mr-3 transition-colors duration-200" 
                                :class="[isCurrentRoute(item.route) ? 'text-white' : 'text-slate-500 group-hover:text-teal-400']"
                                fill="none" 
                                viewBox="0 0 24 24" 
                                stroke-width="1.5" 
                                stroke="currentColor"
                                v-html="getIcon(item.icon)"
                            ></svg>
                            <span class="font-medium text-sm">{{ item.name }}</span>
                        </Link>

                        <!-- Disabled link (coming soon) -->
                        <div
                            v-else
                            class="flex items-center px-4 py-2.5 rounded-xl group cursor-not-allowed relative"
                            :title="`${item.name} — Coming Soon`"
                        >
                            <svg 
                                class="h-5 w-5 mr-3 text-slate-600" 
                                fill="none" 
                                viewBox="0 0 24 24" 
                                stroke-width="1.5" 
                                stroke="currentColor"
                                v-html="getIcon(item.icon)"
                            ></svg>
                            <span class="font-medium text-sm text-slate-600">{{ item.name }}</span>
                            <span class="ml-auto text-[9px] bg-slate-800 text-slate-500 px-1.5 py-0.5 rounded-full uppercase tracking-wider font-semibold">Soon</span>
                        </div>
                    </template>
                </nav>

                <!-- User Footer -->
                <div class="p-4 bg-slate-950 border-t border-slate-800">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3 truncate">
                            <div class="h-10 w-10 rounded-full bg-gradient-to-tr from-teal-600 to-emerald-500 flex items-center justify-center text-white font-bold flex-shrink-0 shadow-inner">
                                {{ user?.name?.charAt(0) || 'A' }}
                            </div>
                            <div class="truncate">
                                <p class="text-sm font-semibold truncate text-white">{{ user?.name || 'Admin' }}</p>
                                <p class="text-[10px] text-teal-400 uppercase tracking-tighter font-semibold">SaaS Admin</p>
                            </div>
                        </div>
                        <Link 
                            :href="route('logout')" 
                            method="post" 
                            as="button"
                            class="p-2 text-slate-500 hover:text-red-400 transition-colors"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                            </svg>
                        </Link>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            <!-- Top Bar -->
            <header class="bg-white border-b border-gray-200 sticky top-0 z-40 h-16 flex items-center px-4 sm:px-6 lg:px-8 shrink-0">
                <div class="flex items-center lg:hidden mr-4">
                    <button 
                        @click="isSidebarOpen = !isSidebarOpen"
                        class="p-2 text-gray-400 hover:text-gray-500 hover:bg-gray-100 rounded-lg transition"
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
                    <span class="text-xs text-gray-400 hidden sm:inline">SaaS Provider Panel</span>
                    <div class="h-8 w-8 rounded-full bg-gradient-to-tr from-teal-500 to-emerald-400 flex items-center justify-center text-white text-xs font-bold">
                        {{ user?.name?.charAt(0) || 'A' }}
                    </div>
                </div>
            </header>

            <!-- Content Area -->
            <main class="flex-1 overflow-y-auto bg-gray-50 custom-scrollbar p-6">
                <slot />
            </main>
        </div>

        <!-- Overlay for Mobile Sidebar -->
        <div 
            v-if="isSidebarOpen" 
            @click="isSidebarOpen = false"
            class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-40 lg:hidden"
        ></div>
    </div>
</template>

<style>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(148, 163, 184, 0.2);
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(148, 163, 184, 0.4);
}
</style>
