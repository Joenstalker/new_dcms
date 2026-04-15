<script setup>
import { ref, computed, provide, onMounted, onUnmounted } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import ThemeSwitcher from '@/Components/ThemeSwitcher.vue';
import NotificationBell from '@/Components/NotificationBell.vue';
import TenantProfileDropdown from '@/Components/TenantProfileDropdown.vue';
import MandatoryPasswordChangeModal from '@/Components/MandatoryPasswordChangeModal.vue';
import MaintenanceDisplay from '@/Components/MaintenanceDisplay.vue';
import SupportChatBubble from '@/Components/SupportChatBubble.vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { brandingState } from '@/States/brandingState';

const page = usePage();
const user = computed(() => page.props.auth.user);
const roles = computed(() => user.value?.roles || []);
const branding = computed(() => page.props.branding || {});
const pendingUpdatesCount = computed(() => page.props.pending_updates_count || 0);
const liveEnabledFeatures = ref([...(page.props.tenant?.enabled_features || [])]);
let tenantBrandingChannel = null;
let userAccessChannel = null;

// 1. Initial State from Server-Side Computation (Robust)
// Initialize immediately before first render to prevent flickering
brandingState.initialize(page.props);

// Sync live state when props change (e.g., after an Inertia page navigate/save)
import { watch } from 'vue';
watch(() => page.props.branding_computed, (newBranding) => {
    if (newBranding) {
        brandingState.primary_color = newBranding.primary_color;
        brandingState.contrast_color = newBranding.contrast_color;
    }
}, { deep: true });

watch(() => page.props.tenant, (tenant) => {
    if (!tenant) return;

    liveEnabledFeatures.value = [...(tenant.enabled_features || [])];

    brandingState.setPortalBackgroundType(tenant.portal_background_type || 'color');
    brandingState.setPortalBackgroundColor(tenant.portal_background_color || null);
    brandingState.setPortalBackgroundImage(tenant.portal_background_image || null);
    brandingState.setPortalBackgroundOverlayOpacity(tenant.portal_background_overlay_opacity ?? 0);
    brandingState.setUiTokens({
        ui_sidebar_text_color: tenant.ui_sidebar_text_color || null,
        ui_sidebar_text_size: tenant.ui_sidebar_text_size ?? 12,
        ui_sidebar_background_color: tenant.ui_sidebar_background_color || null,
        ui_subnav_background_color: tenant.ui_subnav_background_color || null,
        ui_header_title_color: tenant.ui_header_title_color || null,
        ui_header_title_size: tenant.ui_header_title_size ?? 20,
        ui_footer_text_color: tenant.ui_footer_text_color || null,
        ui_footer_text_size: tenant.ui_footer_text_size ?? 10,
        ui_footer_background_color: tenant.ui_footer_background_color || null,
        ui_main_text_color: tenant.ui_main_text_color || null,
        ui_main_text_size: tenant.ui_main_text_size ?? 14,
        ui_card_background_color: tenant.ui_card_background_color || null,
        ui_card_border_color: tenant.ui_card_border_color || null,
        ui_card_text_color: tenant.ui_card_text_color || null,
    });
}, { deep: true });

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
        return (config.selected_staff || []).includes(user.value?.id);
    }
    
    return true; // Default to true if config is missing
});

// Primary color: Prioritize live preview, then computed prop
const primaryColor = computed(() => {
    if (!shouldApplyBranding.value) return page.props.branding?.primary_color || '#0ea5e9';
    return brandingState.primary_color;
});

// Contrast color: Ensure text is always readable (matching AdminLayout logic)
const primaryTextColor = computed(() => {
    if (!shouldApplyBranding.value) return '#ffffff';
    return brandingState.contrast_color;
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

const portalBackgroundType = computed(() => {
    return shouldApplyBranding.value
        ? (brandingState.portal_background_type || page.props.tenant?.portal_background_type || 'color')
        : (page.props.tenant?.portal_background_type || 'color');
});

const portalBackgroundColor = computed(() => {
    return shouldApplyBranding.value
        ? (brandingState.portal_background_color ?? page.props.tenant?.portal_background_color ?? null)
        : (page.props.tenant?.portal_background_color ?? null);
});

const portalBackgroundImage = computed(() => {
    return shouldApplyBranding.value
        ? (brandingState.portal_background_image || page.props.tenant?.portal_background_image || null)
        : (page.props.tenant?.portal_background_image || null);
});
const hasImageBackground = computed(() => portalBackgroundType.value === 'image' && !!portalBackgroundImage.value);

const portalBackgroundOverlayOpacity = computed(() => {
    return shouldApplyBranding.value
        ? Number(brandingState.portal_background_overlay_opacity ?? page.props.tenant?.portal_background_overlay_opacity ?? 0)
        : Number(page.props.tenant?.portal_background_overlay_opacity ?? 0);
});

const uiSidebarTextColor = computed(() => {
    return shouldApplyBranding.value
        ? (brandingState.ui_sidebar_text_color ?? page.props.tenant?.ui_sidebar_text_color ?? null)
        : (page.props.tenant?.ui_sidebar_text_color ?? null);
});

const uiSidebarTextSize = computed(() => {
    return shouldApplyBranding.value
        ? Number(brandingState.ui_sidebar_text_size ?? page.props.tenant?.ui_sidebar_text_size ?? 12)
        : Number(page.props.tenant?.ui_sidebar_text_size ?? 12);
});

const uiSidebarBackgroundColor = computed(() => {
    return shouldApplyBranding.value
        ? (brandingState.ui_sidebar_background_color ?? page.props.tenant?.ui_sidebar_background_color ?? null)
        : (page.props.tenant?.ui_sidebar_background_color ?? null);
});

const uiSubnavBackgroundColor = computed(() => {
    return shouldApplyBranding.value
        ? (brandingState.ui_subnav_background_color ?? page.props.tenant?.ui_subnav_background_color ?? null)
        : (page.props.tenant?.ui_subnav_background_color ?? null);
});

const uiHeaderTitleColor = computed(() => {
    return shouldApplyBranding.value
        ? (brandingState.ui_header_title_color ?? page.props.tenant?.ui_header_title_color ?? null)
        : (page.props.tenant?.ui_header_title_color ?? null);
});

const uiHeaderTitleSize = computed(() => {
    return shouldApplyBranding.value
        ? Number(brandingState.ui_header_title_size ?? page.props.tenant?.ui_header_title_size ?? 20)
        : Number(page.props.tenant?.ui_header_title_size ?? 20);
});

const uiFooterTextColor = computed(() => {
    return shouldApplyBranding.value
        ? (brandingState.ui_footer_text_color ?? page.props.tenant?.ui_footer_text_color ?? null)
        : (page.props.tenant?.ui_footer_text_color ?? null);
});

const uiFooterTextSize = computed(() => {
    return shouldApplyBranding.value
        ? Number(brandingState.ui_footer_text_size ?? page.props.tenant?.ui_footer_text_size ?? 10)
        : Number(page.props.tenant?.ui_footer_text_size ?? 10);
});

const uiFooterBackgroundColor = computed(() => {
    return shouldApplyBranding.value
        ? (brandingState.ui_footer_background_color ?? page.props.tenant?.ui_footer_background_color ?? null)
        : (page.props.tenant?.ui_footer_background_color ?? null);
});

const uiMainTextColor = computed(() => {
    return shouldApplyBranding.value
        ? (brandingState.ui_main_text_color ?? page.props.tenant?.ui_main_text_color ?? null)
        : (page.props.tenant?.ui_main_text_color ?? null);
});

const uiMainTextSize = computed(() => {
    return shouldApplyBranding.value
        ? Number(brandingState.ui_main_text_size ?? page.props.tenant?.ui_main_text_size ?? 14)
        : Number(page.props.tenant?.ui_main_text_size ?? 14);
});

const uiCardBackgroundColor = computed(() => {
    return shouldApplyBranding.value
        ? (brandingState.ui_card_background_color ?? page.props.tenant?.ui_card_background_color ?? null)
        : (page.props.tenant?.ui_card_background_color ?? null);
});

const uiCardBorderColor = computed(() => {
    return shouldApplyBranding.value
        ? (brandingState.ui_card_border_color ?? page.props.tenant?.ui_card_border_color ?? null)
        : (page.props.tenant?.ui_card_border_color ?? null);
});

const uiCardTextColor = computed(() => {
    return shouldApplyBranding.value
        ? (brandingState.ui_card_text_color ?? page.props.tenant?.ui_card_text_color ?? null)
        : (page.props.tenant?.ui_card_text_color ?? null);
});

const contentBackgroundStyle = computed(() => {
    if (!shouldApplyBranding.value) {
        return {};
    }

    if (hasImageBackground.value) {
        const overlay = Math.min(40, Math.max(0, Number(portalBackgroundOverlayOpacity.value || 0))) / 100;
        return {
            backgroundImage: overlay > 0
                ? `linear-gradient(hsl(var(--b1) / ${overlay}), hsl(var(--b1) / ${overlay})), url(${portalBackgroundImage.value})`
                : `url(${portalBackgroundImage.value})`,
            backgroundSize: 'cover',
            backgroundPosition: 'center',
            backgroundRepeat: 'no-repeat',
        };
    }

    if (!portalBackgroundColor.value) {
        return {};
    }

    return {
        backgroundColor: portalBackgroundColor.value,
    };
});

// Platform info
const platformName = computed(() => page.props.tenant?.name || branding.value.platform_name || 'DCMS');
const platformLogo = computed(() => {
    // Priority: Binary URL from shared 'branding' prop (Server-resolved)
    if (branding.value.platform_logo) return branding.value.platform_logo;
    
    // Fallback: Tenant model path
    const logoFile = page.props.tenant?.logo_path;
    if (!logoFile) return null;

    // Handle Base64 data URLs directly
    if (logoFile.startsWith('data:image/')) return logoFile;
    
    // If it's a full URL (already resolved), use as-is
    if (logoFile.startsWith('http://') || logoFile.startsWith('https://')) return logoFile;

    // Local filesystem paths
    if (logoFile.startsWith('branding/') || logoFile.startsWith('logos/')) return '/tenant-storage/' + logoFile;
    
    return '/tenant-storage/logos/' + logoFile;
});
const footerText = computed(() => branding.value.footer_text || '© 2026 DCMS. All rights reserved.');

const isSidebarOpen = ref(false);
/** Desktop (lg+): when false, sidebar is fully hidden like central AdminLayout; main content uses full width. */
const isDesktopSidebarOpen = ref(true);

const desktopDrawerStorageKey = computed(() => {
    const tenant = page.props.tenant;
    const ctx = tenant?.id != null ? `tenant:${tenant.id}` : 'staff';
    return `portal-lg-drawer-open:${ctx}:${sidebarPosition.value}`;
});

const loadDesktopDrawerPreference = () => {
    if (typeof window === 'undefined') {
        return;
    }

    const saved = window.localStorage.getItem(desktopDrawerStorageKey.value);
    isDesktopSidebarOpen.value = saved !== '0';
};

const asideFrameClasses = computed(() => [
    'w-72 min-h-0 h-full max-h-[100dvh] flex flex-col shadow-2xl lg:shadow-none transition-all duration-300',
    'max-lg:overflow-y-auto max-lg:overscroll-y-contain lg:overflow-hidden lg:min-h-0',
    isRightSidebar.value ? 'border-l border-r-0' : 'border-r border-l-0',
]);

watch(desktopDrawerStorageKey, () => {
    loadDesktopDrawerPreference();
}, { immediate: true });

watch(isDesktopSidebarOpen, (open) => {
    if (typeof window === 'undefined') {
        return;
    }

    window.localStorage.setItem(desktopDrawerStorageKey.value, open ? '1' : '0');
    window.requestAnimationFrame(() => {
        window.dispatchEvent(new CustomEvent('dcms-portal-layout-changed'));
    });
});

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

// onMounted to check for updates and show toast
onMounted(() => {
    loadDesktopDrawerPreference();

    const tenantId = page.props.tenant?.id;
    if (window.Echo && tenantId) {
        tenantBrandingChannel = window.Echo.channel(`tenant.${tenantId}.branding`)
            .listen('.TenantBrandingUpdated', (event) => {
                if (Array.isArray(event?.enabled_features)) {
                    liveEnabledFeatures.value = [...event.enabled_features];
                }
            });
    }

    const userId = user.value?.id;
    if (window.Echo && userId) {
        userAccessChannel = window.Echo.private(`App.Models.User.${userId}`)
            .listen('.UserAccessChanged', async (event) => {
                const shouldLogout = Boolean(event?.should_logout);
                const message = event?.message || 'Your access changed. Updating your permissions now.';

                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: shouldLogout ? 'warning' : 'info',
                    title: 'Access updated',
                    text: message,
                    showConfirmButton: false,
                    timer: 4000,
                    timerProgressBar: true,
                });

                if (shouldLogout) {
                    router.post(route('logout'));
                    return;
                }

                // Refresh auth props so sidebar + guards reflect changes without a full page refresh.
                router.reload({ only: ['auth'], preserveScroll: true, preserveState: true });
            });
    }

    // Only show if user is Owner or Admin
    const canManageUpdates = roles.value.includes('Owner') || user.value?.permissions?.includes('manage system updates');
    
    if (canManageUpdates && pendingUpdatesCount.value > 0) {
        // Check session storage to avoid showing on every page navigation, just once per session
        if (!sessionStorage.getItem('notified_updates')) {
            setTimeout(() => {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'info',
                    title: `System Updates`,
                    text: `You have ${pendingUpdatesCount.value} new feature update(s) available.`,
                    showConfirmButton: true,
                    confirmButtonText: 'View',
                    showCancelButton: true,
                    cancelButtonText: 'Later',
                    timer: 10000,
                    timerProgressBar: true,
                    confirmButtonColor: primaryColor.value,
                }).then((result) => {
                    if (result.isConfirmed) {
                        router.visit(route('settings.updates'));
                    }
                });
                sessionStorage.setItem('notified_updates', 'true');
            }, 1000);
        }
    }
});

onUnmounted(() => {
    const tenantId = page.props.tenant?.id;
    if (window.Echo && tenantId) {
        window.Echo.leave(`tenant.${tenantId}.branding`);
    }

    tenantBrandingChannel = null;

    const userId = user.value?.id;
    if (window.Echo && userId) {
        window.Echo.leave(`App.Models.User.${userId}`);
    }

    userAccessChannel = null;
});

// Authorization Guard: Check if the current route is allowed for the user
const currentRouteName = computed(() => route().current() || '');
const isRouteAuthorized = computed(() => {
    const isOwner = roles.value.includes('Owner');
    
    // Explicitly block Owners from staff-only modules like personal settings
    const staffOnlyRoutes = ['staff-settings.index', 'staff-settings.calendar-color', 'staff-settings.notifications', 'staff-settings.working-hours'];
    if (isOwner && staffOnlyRoutes.includes(currentRouteName.value)) return false;

    if (isOwner) return true;

    // Check against the processed menu items to see if the current route is "reachable"
    // This automatically respects the exact rules used to show/hide sidebar items
    const allAuthorizedRoutes = menuCategories.value.flatMap(cat => 
        cat.items.flatMap(item => {
            const routes = [];
            if (item.route) routes.push(item.route);
            if (item.subItems) item.subItems.forEach(si => routes.push(si.route));
            return routes;
        })
    );

    // Dashboard and Profile routes are always authorized for base access
    const baseRoutes = [dashboardRoute.value, 'profile.edit', 'tenant.dashboard'];
    if (baseRoutes.includes(currentRouteName.value)) return true;

    // If the current route is not in the authorized list, block it
    return allAuthorizedRoutes.includes(currentRouteName.value);
});

// Maintenance Check: Determine if the current active feature is under maintenance
const currentFeatureStatus = computed(() => {
    // 1. Get the current route name
    const routeName = currentRouteName.value;
    if (!routeName) return { status: 'active', name: '' };

    // 2. Find the feature key from menuCategories
    let featureKey = null;
    let featureName = '';

    for (const category of menuCategories.value) {
        for (const item of category.items) {
            // Check top level item
            const isActive = item.route === routeName || (item.subItems && item.subItems.some(si => si.route === routeName));
            if (isActive) {
                featureKey = item.feature;
                featureName = item.name;
                break;
            }
        }
        if (featureKey) break;
    }

    if (!featureKey) return { status: 'active', name: '' };

    // 3. Map sidebar feature identifiers to actual database feature keys
    const sidebarToDbKeyMap = {
        'appointments': 'appointment_scheduling',
        'patients': 'patient_records',
        'billing': 'billing_pos',
        'branding': 'custom_branding',
        'settings': 'clinic_setup',
        'staff': 'max_users',
        'services': 'qr_booking', // partially related
        'logs' : 'role_based_access'
    };

    const dbKey = sidebarToDbKeyMap[featureKey] || featureKey;
    const globalStatus = page.props.tenant_plan?.global_feature_statuses?.[dbKey];
    
    // 4. Return the status (Prefer global status from DB over filtered subscription props)
    return {
        status: globalStatus || 'active',
        name: featureName
    };
});

const menuCategories = computed(() => {
    const categories = [
        {
            title: 'Main Navigation',
            items: [
                { name: 'Dashboard', route: dashboardRoute.value, icon: 'home', feature: 'dashboard', permissions: ['view dashboard'] },
                { name: 'Appointments', route: 'appointments.index', icon: 'calendar', feature: 'appointments', permissions: ['view appointments'] },
                { name: 'Patients', icon: 'users', route: 'patients.index', feature: 'patients', permissions: ['view patients'] },
                { 
                    name: 'Billing & POS', 
                    icon: 'cash', 
                    route: 'billing.index',
                    feature: 'billing', 
                    permissions: ['view billing'],
                    subItems: [
                        { name: 'POS Cashier', route: 'billing.index', routeParams: { tab: 'cashier' }, permissions: ['view billing'] },
                        { name: 'Transactions', route: 'billing.index', routeParams: { tab: 'transactions' }, permissions: ['view billing'] },
                        { name: 'Receipts', route: 'billing.index', routeParams: { tab: 'receipts' }, permissions: ['view billing'] },
                    ]
                },
                { name: 'Treatment Records', route: 'treatments.index', icon: 'calendar', feature: 'treatments', permissions: ['view treatments'] },
                { name: 'Medical Records', route: 'medical-records.index', icon: 'users', feature: 'medical_records', permissions: ['view medical records'] },
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
                    hideSubnav: true,
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
                    feature: 'reports',
                    permissions: ['view reports'],
                    subItems: [
                        { name: 'Revenue Report', route: 'reports.index', permissions: ['view reports'] },
                        { name: 'Enhanced Reports', route: 'reports.index', permissions: ['view reports'], featureKey: 'enhanced_reports' },
                        { name: 'Appointments Report', route: 'reports.index', permissions: ['view reports'] },
                    ]
                },
                { name: 'Analytics', route: 'analytics.index', icon: 'analytics', feature: 'analytics', permissions: ['view analytics'], featureKey: 'advanced_analytics' },
                { name: 'Notifications', route: 'notifications.index', icon: 'bell', feature: 'notifications', permissions: ['view notifications'], featureKey: 'sms_notifications' },
            ]
        },
        {
            title: 'System',
            items: [
                { 
                    name: 'My Settings', 
                    icon: 'user-cog', 
                    route: 'staff-settings.index', 
                    permissions: ['manage own calendar', 'manage own notifications', 'manage own working hours'],
                    staffOnly: true
                },
                { 
                    name: 'Activity Logs', 
                    icon: 'shield', 
                    route: 'activity-logs.index', 
                    feature: 'logs',
                    permissions: ['view activity logs']
                },
                { 
                    name: 'Custom Branding', 
                    icon: 'paint', 
                    route: 'settings.branding', 
                    feature: 'branding',
                    permissions: ['manage clinic branding'],
                    featureKey: 'custom_branding'
                },
                { 
                    name: 'Settings', 
                    icon: 'cog', 
                    feature: 'settings',
                    permissions: ['manage settings'],
                    subItems: [
                        { name: 'Billing & Plan', route: 'settings.index', permissions: ['manage settings'] },
                        { name: 'Premium Features', route: 'settings.features', permissions: ['manage system features'], featureKey: 'custom_system_features' },
                        { name: 'Updates', route: 'settings.updates', permissions: ['manage system updates'], icon: 'refresh', badge: pendingUpdatesCount.value },
                    ]
                },
                { name: 'Branches', route: 'branches.index', icon: 'branch', feature: 'branches', permissions: ['manage branches'], featureKey: 'multi_branch' },
            ]
        }
    ];

    const processItems = (categories) => {
        return categories.map(cat => ({
            ...cat,
            items: cat.items.filter(item => {
                const isOwner = roles.value.includes('Owner');
                const hasRole = item.roles ? item.roles.some(role => roles.value.includes(role)) : false;
                const hasPermission = item.permissions ? item.permissions.some(p => user.value?.permissions?.includes(p)) : false;
                const hasRoute = item.route ? route().has(item.route) : true;
                const noAuthDefined = !item.roles && !item.permissions;
                
                // Owner sees everything relevant to their role/route
                // Staff only sees if they have permission AND it's not locked by the plan
                const isLocked = item.featureKey ? !page.props.tenant_plan?.features?.[item.featureKey] : false;
                
                if (isOwner) {
                    // Hide items explicitly marked for Staff Only
                    if (item.staffOnly) return false;
                    
                    // Clinic Owner Toggle Logic (Hide/Show sidebar features)
                    // Dashboard, Custom Branding, and Settings are always mandatory for the Owner
                    if (item.feature && !liveEnabledFeatures.value.includes(item.feature)) {
                        const mandatoryFeatures = ['dashboard', 'branding', 'settings'];
                        if (!mandatoryFeatures.includes(item.feature)) return false;
                    }
                    
                    return hasRoute;
                }
                
                // For Staff: MUST have explicit Role or Permission AND must NOT be locked.
                // Maintenance Override: Features under maintenance are NOT considered locked for navigation.
                const featureStatus = item.featureKey ? page.props.subscription?.features?.[item.featureKey]?.implementation_status : null;
                const finalLocked = isLocked && featureStatus !== 'maintenance';

                return (hasRole || hasPermission) && !finalLocked && hasRoute;
            }).map(item => {
                const isOwner = roles.value.includes('Owner');
                const isLocked = item.featureKey ? !page.props.tenant_plan?.features?.[item.featureKey] : false;
                
                // Maintenance Override: If the feature is under maintenance, do not lock it.
                // This allows the user to click the item and see the MaintenanceDisplay component.
                const featureStatus = item.featureKey ? page.props.subscription?.features?.[item.featureKey]?.implementation_status : null;
                const finalLocked = isLocked && featureStatus !== 'maintenance';

                return {
                    ...item,
                    isLocked: finalLocked,
                    subItems: item.subItems?.filter(si => {
                        const hasRole = si.roles ? si.roles.some(role => roles.value.includes(role)) : false;
                        const hasPermission = si.permissions ? si.permissions.some(p => user.value?.permissions?.includes(p)) : false;
                        const hasRoute = si.route ? route().has(si.route) : true;
                        const noAuthDefined = !si.roles && !si.permissions;
                        
                        const isSubLocked = si.featureKey ? !page.props.tenant_plan?.features?.[si.featureKey] : false;

                        if (isOwner) {
                            return hasRoute;
                        }
                        
                        // For Staff: MUST have explicit Role or Permission.
                        const siStatus = si.featureKey ? page.props.subscription?.features?.[si.featureKey]?.implementation_status : null;
                        const finalSiLocked = isSubLocked && siStatus !== 'maintenance';

                        return (hasRole || hasPermission) && !finalSiLocked && hasRoute;
                    }).map(si => {
                        const siLocked = si.featureKey ? !page.props.tenant_plan?.features?.[si.featureKey] : false;
                        const siStatus = si.featureKey ? page.props.subscription?.features?.[si.featureKey]?.implementation_status : null;
                        const finalSiLocked = siLocked && siStatus !== 'maintenance';
                        return { ...si, isLocked: finalSiLocked };
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
        'user-cog': `<path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />`,
        staff: `<path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />`,
        shield: `<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />`,
    };
    return icons[name] || '';
};

const activeCategoryWithSubItems = computed(() => {
    for (const category of menuCategories.value) {
        for (const item of category.items) {
            if (!item.hideSubnav && item.subItems && item.subItems.some(si => route().current(si.route))) {
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
    const hsl = hexToHsl(color);
    const focus = hexToHsl(darkenColor(color, 10));
    const content = hexToHsl(primaryTextColor.value);
    
    // Inject CSS variables for primary color to handle bg-primary, text-primary, etc.
    // Target both :root and [data-theme] to override DaisyUI defaults
    return `
        :root, [data-theme], [data-theme="light"], [data-theme="dark"] {
            --p: ${hsl};
            --pf: ${focus};
            --pc: ${content};
            --tenant-sidebar-text-color: ${uiSidebarTextColor.value || 'inherit'};
            --tenant-sidebar-text-size: ${uiSidebarTextSize.value}px;
            --tenant-sidebar-bg-color: ${uiSidebarBackgroundColor.value || 'hsl(var(--b1))'};
            --tenant-subnav-bg-color: ${uiSubnavBackgroundColor.value || 'hsl(var(--b1))'};
            --tenant-header-title-color: ${uiHeaderTitleColor.value || 'inherit'};
            --tenant-header-title-size: ${uiHeaderTitleSize.value}px;
            --tenant-footer-text-color: ${uiFooterTextColor.value || 'inherit'};
            --tenant-footer-text-size: ${uiFooterTextSize.value}px;
            --tenant-footer-bg-color: ${uiFooterBackgroundColor.value || 'hsl(var(--b1))'};
            --tenant-main-text-color: ${uiMainTextColor.value || 'inherit'};
            --tenant-main-text-size: ${uiMainTextSize.value}px;
            --tenant-card-bg-color: ${uiCardBackgroundColor.value || 'hsl(var(--b1) / 0.86)'};
            --tenant-card-border-color: ${uiCardBorderColor.value || 'hsl(var(--b3) / 0.95)'};
            --tenant-card-text-color: ${uiCardTextColor.value || 'hsl(var(--bc))'};
            
            /* Success and other states can also be derived if needed */
            --s: ${hsl}; /* Primary as Secondary fallback */
        }
        
        .btn-primary {
            background-color: ${color} !important;
            border-color: ${color} !important;
            color: hsl(${content}) !important;
        }
        
        .btn-primary:hover {
            background-color: ${darkenColor(color, 10)} !important;
            border-color: ${darkenColor(color, 10)} !important;
        }

        .text-primary {
            color: ${color} !important;
        }
        
        .bg-primary {
            background-color: ${color} !important;
        }

        .tenant-sidebar-text {
            color: var(--tenant-sidebar-text-color) !important;
            font-size: var(--tenant-sidebar-text-size) !important;
        }

        .tenant-sidebar-panel {
            background-color: var(--tenant-sidebar-bg-color) !important;
        }

        .tenant-subnav-panel {
            background-color: transparent !important;
        }

        .tenant-subnav-pill {
            background-color: var(--tenant-subnav-bg-color) !important;
            border: 1px solid hsl(var(--b3) / 0.95) !important;
            color: hsl(var(--bc) / 0.68) !important;
        }

        .tenant-subnav-pill:hover {
            color: hsl(var(--bc) / 0.9) !important;
            border-color: hsl(var(--b3) / 0.8) !important;
        }

        .tenant-subnav-pill-active {
            color: hsl(var(--bc)) !important;
            border-color: ${color} !important;
            box-shadow: 0 1px 0 hsl(var(--bc) / 0.04), 0 0 0 1px hsl(var(--b1) / 0.35) inset;
            filter: brightness(0.94);
        }

        .tenant-footer-panel {
            background-color: var(--tenant-footer-bg-color) !important;
        }

        .tenant-header-title {
            color: var(--tenant-header-title-color) !important;
            font-size: var(--tenant-header-title-size) !important;
            line-height: 1.2 !important;
        }

        .tenant-footer-text {
            color: var(--tenant-footer-text-color) !important;
            font-size: var(--tenant-footer-text-size) !important;
        }

        .tenant-main {
            color: var(--tenant-main-text-color);
            font-size: var(--tenant-main-text-size);
        }

        .tenant-main .bg-base-100,
        .tenant-main .bg-white {
            background-color: var(--tenant-card-bg-color, transparent) !important;
            color: var(--tenant-card-text-color) !important;
        }

        .tenant-main .border-base-300,
        .tenant-main .border-gray-100,
        .tenant-main .border-gray-200 {
            border-color: var(--tenant-card-border-color, currentColor) !important;
        }

        .tenant-main .bg-base-100,
        .tenant-main .bg-white {
            backdrop-filter: saturate(120%) blur(1.5px);
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
    return luminance > 0.5 ? '#1f2937' : '#ffffff';
}
</script>

<template>
    <div 
        id="tenant-app-drawer"
        class="drawer h-screen overflow-hidden" 
        :class="[fonts.general, { 'drawer-end': isRightSidebar, 'lg:drawer-open': isDesktopSidebarOpen }]"
    >
        <component :is="'style'" v-if="brandStyle">{{ brandStyle }}</component>
        <input id="tenant-sidebar" type="checkbox" v-model="isSidebarOpen" class="drawer-toggle" />
        
        <!-- Main Content Area -->
        <div
            class="drawer-content flex flex-col h-screen overflow-hidden"
            :class="hasImageBackground ? 'bg-transparent' : 'bg-base-200'"
            :style="contentBackgroundStyle"
        >
            <!-- Top Navigation for Mobile & Title -->
            <header 
                id="tenant-top-header"
                class="bg-base-100 border-b border-base-300 sticky top-0 z-40 h-16 flex items-center justify-between px-4 sm:px-6 lg:px-8 shrink-0 shadow-sm"
                :style="{ borderTop: `4px solid ${primaryColor}` }"
            >
                <div class="flex items-center mr-4 min-w-0 flex-1">
                    <label 
                        for="tenant-sidebar"
                        class="btn btn-ghost btn-sm btn-square text-base-content/50 lg:hidden shrink-0"
                    >
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </label>
                    <button
                        type="button"
                        v-if="!isDesktopSidebarOpen"
                        class="btn btn-ghost btn-sm btn-square text-base-content/50 hidden lg:flex shrink-0"
                        title="Open sidebar"
                        @click="isDesktopSidebarOpen = true"
                    >
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <div v-if="$slots.header" class="flex-1 min-w-0 tenant-header-title" :class="fonts.header">
                        <slot name="header" />
                    </div>
                </div>

                <div class="flex items-center space-x-4">
                    <ThemeSwitcher />
                    <NotificationBell type="tenant" />
                    <TenantProfileDropdown />
                </div>
            </header>

            <!-- Tab Navigation (Dynamic Sub-menus) -->
            <nav 
                id="tenant-subnav"
                v-if="activeCategoryWithSubItems"
                class="tenant-subnav-panel bg-base-100 border-b border-base-300 sticky top-16 z-30 px-4 sm:px-6 lg:px-8 overflow-x-auto custom-scrollbar flex-shrink-0"
            >
                <div class="flex items-center gap-2 py-2">
                    <Link 
                        v-for="sub in activeCategoryWithSubItems.subItems" 
                        :key="sub.name"
                        :href="route(sub.route, sub.routeParams || {})"
                        class="tenant-subnav-pill px-4 py-2 rounded-full text-sm font-semibold transition-all whitespace-nowrap"
                        :class="[
                            isSubItemActive(sub)
                                ? 'tenant-subnav-pill-active' 
                                : '',
                            fonts.sidebar
                        ]"
                    >
                        {{ sub.name }}
                        <span v-if="roles.includes('Owner') && sub.featureKey && sub.isLocked" class="ml-2 px-2 py-0.5 text-[10px] font-bold text-amber-900 bg-amber-100 rounded-lg border border-amber-200">
                            {{ page.props.tenant_plan?.feature_requirements?.[sub.featureKey] || 'PREMIUM' }}
                        </span>
                    </Link>
                </div>
            </nav>

            <!-- Content Area -->
            <main class="flex-1 overflow-y-auto custom-scrollbar p-4 lg:p-8 tenant-main">
                <div class="max-w-[1600px] mx-auto">
                    <!-- Authorization Guard -->
                    <template v-if="isRouteAuthorized">
                        <!-- Maintenance Guard -->
                        <template v-if="currentFeatureStatus.status === 'maintenance'">
                            <MaintenanceDisplay :feature-name="currentFeatureStatus.name" />
                        </template>
                        <template v-else>
                            <slot />
                        </template>
                    </template>
                    <template v-else>
                        <div class="flex flex-col items-center justify-center h-[60vh] text-center px-4 animate-fade-in">
                            <div class="w-24 h-24 bg-red-50 rounded-full flex items-center justify-center mb-6 shadow-inner">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 text-red-500">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0-10.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.57-.598-3.75h-.152c-3.196 0-6.1-1.248-8.25-3.286Zm0 13.036h.008v.008H12v-.008Z" />
                                </svg>
                            </div>
                            <h2 class="text-2xl font-black text-gray-900 uppercase tracking-widest mb-2">Restricted Access</h2>
                            <p class="text-gray-500 max-w-md mx-auto font-medium">
                                You do not have permission to access the 
                                <span class="text-red-600 font-bold uppercase">{{ currentRouteName.split('.')[0] }}</span> 
                                module. Please contact your clinic owner to request access.
                            </p>
                            <Link 
                                :href="route('tenant.dashboard')"
                                class="mt-8 btn btn-outline btn-sm border-2 rounded-xl uppercase font-black tracking-widest px-8"
                            >
                                Back to Dashboard
                            </Link>
                        </div>
                    </template>
                </div>
            </main>

            <!-- Footer -->
            <footer id="tenant-footer" class="tenant-footer-panel bg-base-100 border-t border-base-300 py-4 px-8 mt-auto flex items-center justify-between">
                <p class="text-[10px] font-bold uppercase tracking-widest text-base-content/20 tenant-footer-text">{{ footerText }}</p>
                <span class="text-[10px] font-black tracking-widest text-base-content/30 hover:text-primary transition-colors cursor-default tenant-footer-text" :class="fonts.general">
                    {{ page.props.tenant ? 'DCMS ' : 'App ' }}{{ page.props.config?.version || 'v1.0.0' }}
                </span>
            </footer>
        </div>

        <!-- Sidebar -->
        <div class="drawer-side z-40 h-full min-h-0 lg:overflow-hidden">
            <label for="tenant-sidebar" aria-label="close sidebar" class="drawer-overlay"></label>
            <aside id="tenant-sidebar-panel" class="tenant-sidebar-panel bg-base-100 overflow-hidden" :class="asideFrameClasses">
                <!-- Sidebar Header -->
                <div class="flex items-center px-4 h-16 border-b border-base-300 shrink-0">
                    <Link :href="usePage().props.tenant ? route('tenant.dashboard') : route('dashboard')" class="flex items-center space-x-4 flex-1 min-w-0">
                        <div class="h-10 w-10 rounded-xl overflow-hidden shadow-inner border border-base-300 bg-base-200 flex items-center justify-center p-1 flex-shrink-0">
                            <img v-if="platformLogo" :src="platformLogo" :alt="platformName" class="h-full w-full object-contain object-center" />
                            <ApplicationLogo v-else class="h-7 w-7 fill-current" :style="{ color: primaryColor }" />
                        </div>
                        <div class="truncate">
                            <span class="text-sm font-black tracking-tight text-base-content block truncate tenant-sidebar-text" :class="fonts.header">{{ platformName }}</span>
                            <span class="text-[9px] uppercase tracking-[0.2em] font-black opacity-30 block" :class="fonts.header" :style="{ color: primaryColor }">Professional Portal</span>
                        </div>
                    </Link>
                    <button
                        type="button"
                        class="btn btn-ghost btn-sm btn-circle text-base-content/30 hover:text-base-content hidden lg:flex shrink-0"
                        title="Close sidebar"
                        @click="isDesktopSidebarOpen = false"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                        </svg>
                    </button>
                    <label
                        for="tenant-sidebar"
                        class="btn btn-ghost btn-sm btn-circle text-base-content/30 hover:text-base-content lg:hidden shrink-0"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </label>
                </div>

                <!-- Navigation Categories -->
                <nav class="shrink-0 px-3 py-4 space-y-1 lg:min-h-0 lg:flex-1 lg:overflow-y-auto lg:overscroll-y-contain">
                    <template v-for="(category, catIdx) in menuCategories" :key="category.title">
                        <div v-if="catIdx > 0 && category.items.length > 0" class="h-3"></div>

                        <div class="space-y-1">
                            <Link 
                                v-for="item in category.items" 
                                :key="item.name"
                                :href="item.isLocked ? '#' : route(getItemRoute(item))"
                                @click="item.isLocked ? ($event.preventDefault(), showUpgradeAlert(item.name)) : (isSidebarOpen = false)"
                                :class="[
                                    isItemActive(item) && !item.isLocked
                                        ? 'bg-primary' 
                                        : 'hover:bg-base-200/80',
                                    item.isLocked ? 'opacity-50' : '',
                                ]"
                                class="flex items-center px-5 py-2.5 rounded-xl transition-colors duration-200"
                                :style="isItemActive(item) && !item.isLocked ? { backgroundColor: primaryColor, color: primaryTextColor } : {}"
                                :title="item.name"
                            >
                                <svg 
                                    class="h-5 w-5 flex-shrink-0 mr-4" 
                                    :class="[
                                        isItemActive(item) && !item.isLocked ? 'text-white' : 'opacity-40'
                                    ]"
                                    fill="none" 
                                    viewBox="0 0 24 24" 
                                    stroke-width="2" 
                                    stroke="currentColor"
                                    v-html="item.isLocked ? getIcon('lock') : getIcon(item.icon)"
                                ></svg>
                                <span class="font-bold text-xs uppercase tracking-wider truncate tenant-sidebar-text" :class="fonts.sidebar">{{ item.name }}</span>
                                <div v-if="isItemActive(item) && !item.isLocked" class="ml-auto w-1.5 h-1.5 rounded-full bg-white flex-shrink-0"></div>
                            </Link>
                        </div>
                    </template>
                </nav>

                <!-- User Footer -->
                <div class="shrink-0 p-4 border-t border-base-300">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-4 min-w-0">
                            <div 
                                class="h-11 w-11 rounded-2xl flex items-center justify-center font-black flex-shrink-0 shadow-lg shadow-primary/10 overflow-hidden"
                                :style="{ backgroundColor: primaryColor, color: primaryTextColor }"
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
                                <p class="text-xs font-black truncate text-base-content tenant-sidebar-text" :class="fonts.names">{{ user.name }}</p>
                                <p class="text-[9px] uppercase tracking-[0.2em] font-black opacity-30 tenant-sidebar-text" :class="fonts.header">{{ roles[0] || 'Staff' }}</p>
                            </div>
                        </div>
                        <button 
                            @click="handleLogout"
                            class="btn btn-ghost btn-sm btn-circle text-base-content/20 hover:text-error hover:bg-error/10"
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
        
        <SupportChatBubble />
        <MandatoryPasswordChangeModal />
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
