# Apply Admin Branding Primary Color PLAN

## Objective
Harmonize the Admin UI by applying the customizable Admin Branding `primaryColor` to four new key sections: Notifications, Support Tickets, Subscriptions, and Subscription Plans.

## Approach (Phase 1)
For each section, we will follow the established dynamic styling pattern:

1. **State Fetching (`Index.vue`)**:
   - Import `usePage` from `@inertiajs/vue3`.
   - Calculate the primary color: `const primaryColor = computed(() => usePage().props.branding?.primary_color || '#0ea5e9');`
   - Apply inline styles to top-level "Create/Add" buttons (e.g., "Create Plan", "New Ticket").

2. **Component Delegation**:
   - Pass `:primary-color="primaryColor"` as a prop to deeply nested layout components or tables (e.g., `<TicketList>`, `<PlanCards>`).
   - Define `primaryColor: { type: String, default: '#0ea5e9' }` in the children.

3. **Dynamic Inline Styles**:
   - **Primary Buttons:** `:style="{ backgroundColor: primaryColor }"`
   - **Secondary/Action Buttons (Tables):** `:style="{ color: primaryColor, backgroundColor: primaryColor + '15', borderColor: primaryColor + '30' }"` (Matching the Tenant table 'Manage Clinic' style).
   - **Text Highlights/Links:** `:style="{ color: primaryColor }"`

## Targeted Sections & Expected Files
- **Notifications**: `resources/js/Pages/Admin/Notifications/Index.vue` (and dropdowns)
- **Support Tickets**: `resources/js/Pages/Admin/Tickets/Index.vue`
- **Subscriptions**: `resources/js/Pages/Admin/Subscriptions/Index.vue`
- **Subscription Plans**: The Plans management page (whether standalone or combined with Subscriptions).

## Orchestration Phase 2 (Implementation)
Upon approval, the orchestrator will coordinate the following agents:
- 🕵️ **`explorer-agent`**: Verify the exact file paths for these four sections in the codebase.
- 🎨 **`frontend-specialist`**: Inject the `usePage` logic and apply the dynamic styles to all interactive elements across the 4 sections.
- 🧪 **`test-engineer`**: Validate prop propagation, test template compilation, and perform final syntax checks.
