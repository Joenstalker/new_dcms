<script setup>
import { ref, computed } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const user = computed(() => page.props.auth.user);
const roles = computed(() => user.value?.roles || []);

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
</script>

<template>
    <div class="flex h-screen bg-gray-50 overflow-hidden font-sans">
        <!-- Sidebar for Desktop -->
        <aside 
            :class="[isSidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0']"
            class="fixed inset-y-0 left-0 z-50 w-64 bg-slate-900 text-white transition-transform duration-300 ease-in-out lg:static lg:inset-0"
        >
            <div class="flex flex-col h-full">
                <!-- Sidebar Header -->
                <div class="flex items-center justify-center h-20 bg-slate-950 border-b border-slate-800">
                    <Link :href="usePage().props.tenant ? route('tenant.dashboard') : route('dashboard')" class="flex items-center space-x-3">
                        <ApplicationLogo class="h-8 w-auto fill-current text-blue-400" />
                        <span class="text-xl font-bold tracking-wider text-white">DCMS</span>
                    </Link>
                </div>

                <!-- Navigation Categories -->
                <nav class="flex-1 px-4 py-6 space-y-6 overflow-y-auto custom-scrollbar">
                    <div v-for="category in menuCategories" :key="category.title">
                        <h3 class="px-4 mb-3 text-xs font-semibold text-slate-500 uppercase tracking-widest">{{ category.title }}</h3>
                        <div class="space-y-1">
                            <div v-for="item in category.items" :key="item.name">
                                <!-- Regular Link -->
                                <Link 
                                    v-if="!item.subItems"
                                    :href="route(item.route)"
                                    :class="[
                                        route().current(item.route) 
                                            ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/50' 
                                            : 'text-slate-400 hover:bg-slate-800 hover:text-white transition-all duration-200'
                                    ]"
                                    class="flex items-center px-4 py-2.5 rounded-xl group"
                                >
                                    <svg 
                                        class="h-5 w-5 mr-3 transition-colors duration-200" 
                                        :class="[route().current(item.route) ? 'text-white' : 'text-slate-500 group-hover:text-blue-400']"
                                        fill="none" 
                                        viewBox="0 0 24 24" 
                                        stroke-width="1.5" 
                                        stroke="currentColor"
                                        v-html="getIcon(item.icon)"
                                    ></svg>
                                    <span class="font-medium text-sm">{{ item.name }}</span>
                                </Link>

                                <!-- Collapsible Link -->
                                <div v-else>
                                    <button 
                                        @click="toggleSubMenu(item.name)"
                                        :class="[
                                            openSubMenus[item.name] ? 'text-white bg-slate-800/50' : 'text-slate-400 hover:bg-slate-800 hover:text-white'
                                        ]"
                                        class="w-full flex items-center justify-between px-4 py-2.5 rounded-xl transition-all duration-200 group"
                                    >
                                        <div class="flex items-center">
                                            <svg 
                                                class="h-5 w-5 mr-3 transition-colors duration-200 text-slate-500 group-hover:text-blue-400" 
                                                fill="none" 
                                                viewBox="0 0 24 24" 
                                                stroke-width="1.5" 
                                                stroke="currentColor"
                                                v-html="getIcon(item.icon)"
                                            ></svg>
                                            <span class="font-medium text-sm">{{ item.name }}</span>
                                        </div>
                                        <svg 
                                            :class="[openSubMenus[item.name] ? 'rotate-180' : '']"
                                            class="w-4 h-4 transition-transform duration-200 text-slate-600" 
                                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                        </svg>
                                    </button>
                                    
                                    <!-- Sub-items -->
                                    <div 
                                        v-show="openSubMenus[item.name]"
                                        class="mt-1 ml-9 space-y-1 overflow-hidden transition-all duration-300"
                                    >
                                        <Link 
                                            v-for="sub in item.subItems" 
                                            :key="sub.name"
                                            :href="route(sub.route, sub.routeParams || {})"
                                            :class="[
                                                route().current(sub.route, sub.routeParams || {}) ? 'text-blue-400 font-semibold' : 'text-slate-500 hover:text-white'
                                            ]"
                                            class="block py-2 text-xs transition-colors duration-200 relative pl-4 border-l border-slate-700 hover:border-blue-400"
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
                <div class="p-4 bg-slate-950 border-t border-slate-800">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3 truncate">
                            <div class="h-10 w-10 rounded-full bg-gradient-to-tr from-blue-600 to-indigo-600 flex items-center justify-center text-white font-bold flex-shrink-0 shadow-inner">
                                {{ user.name.charAt(0) }}
                            </div>
                            <div class="truncate">
                                <p class="text-sm font-semibold truncate text-white">{{ user.name }}</p>
                                <p class="text-[10px] text-slate-500 uppercase tracking-tighter">{{ roles[0] || 'Staff' }}</p>
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
            <!-- Top Navigation for Mobile & Title -->
            <header class="bg-white border-b border-gray-200 sticky top-0 z-40 h-16 flex items-center justify-between px-4 sm:px-6 lg:px-8 shrink-0">
                <div class="flex items-center space-x-4">
                    <button 
                        @click="isSidebarOpen = !isSidebarOpen"
                        class="lg:hidden p-2 text-gray-400 hover:text-gray-500 hover:bg-gray-100 rounded-lg transition"
                    >
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <div v-if="$slots.header" class="flex-1 min-w-0">
                        <slot name="header" />
                    </div>
                </div>

                <div class="flex items-center space-x-4">
                    <!-- Right Actions (e.g., Profile link) -->
                    <Link 
                        :href="route('profile.edit')"
                        class="flex items-center space-x-2 text-sm text-gray-600 hover:text-gray-900 transition"
                    >
                        <span class="hidden sm:inline">Profile</span>
                        <div class="h-8 w-8 rounded-full border border-gray-200 bg-gray-50 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                    </Link>
                </div>
            </header>

            <!-- Tab Navigation (Dynamic Sub-menus) -->
            <nav 
                v-if="activeCategoryWithSubItems"
                class="bg-white border-b border-gray-200 sticky top-16 z-30 px-4 sm:px-6 lg:px-8 overflow-x-auto custom-scrollbar flex-shrink-0"
            >
                <div class="flex space-x-8">
                    <Link 
                        v-for="sub in activeCategoryWithSubItems.subItems" 
                        :key="sub.name"
                        :href="route(sub.route)"
                        :class="[
                            route().current(sub.route)
                                ? 'border-blue-500 text-blue-600' 
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        ]"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-all duration-200"
                    >
                        {{ sub.name }}
                    </Link>
                </div>
            </nav>

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
