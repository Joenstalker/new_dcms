<script setup>
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';

const page = usePage();
const primaryColor = computed(() => page.props.branding?.primary_color || '#0ea5e9');
const canAccessSupportChat = computed(() => {
    const permissions = page.props.auth?.user?.permissions || [];
    const roles = page.props.auth?.user?.roles || [];

    return roles.includes('Owner') || permissions.includes('access support chat');
});

const clampOffset = (value, fallback = 56) => {
    if (!Number.isFinite(value)) {
        return fallback;
    }

    return Math.min(720, Math.max(16, Math.round(value)));
};

const bubbleBottomOffset = computed(() => clampOffset(page.props.branding?.support_chat_bottom_offset, 56));
const bubbleRightOffset = computed(() => clampOffset(page.props.branding?.support_chat_right_offset, 24));
const currentSidebarPosition = computed(() => {
    const side = page.props.branding?.sidebar_position;
    return side === 'right' ? 'right' : 'left';
});
const currentBottomOffset = ref(bubbleBottomOffset.value);
const currentRightOffset = ref(bubbleRightOffset.value);

const isOpen = ref(false);
const chatPanelWidthPx = ref(380);
const activeView = ref('list'); // 'list' | 'chat' | 'new'
const tickets = ref([]);
const activeTicket = ref(null);
const loading = ref(false);
const sending = ref(false);
const messageContent = ref('');
const attachments = ref([]);
const chatContainer = ref(null);
const fileInput = ref(null);
const unreadCount = ref(0);
const isModalOpen = ref(false);
const bubbleAnchor = ref(null);
const isDraggingBubble = ref(false);
const dragState = ref({
    pointerId: null,
    startX: 0,
    startY: 0,
    offsetX: 0,
    offsetY: 0,
    moved: false,
});
const suppressNextToggle = ref(false);
let pollInterval = null;
/** @type {Set<number|string>} */
const subscribedSupportTicketIds = new Set();
let bodyClassObserver = null;
let layoutResizeObserver = null;
let layoutMutationObserver = null;
let clampFrame = null;
const runtimePositionStoreKey = '__tenantSupportChatBubblePosition';

/** Laravel `ApiResponse` wraps payloads as `{ success, data: { ticket, ... } }`. */
const unwrapSupportApi = (axiosResponseData) => {
    if (axiosResponseData && typeof axiosResponseData.success === 'boolean' && axiosResponseData.data !== undefined) {
        return axiosResponseData.data;
    }
    return axiosResponseData || {};
};

const leaveAllSupportTicketEchoChannels = () => {
    if (typeof window === 'undefined' || !window.Echo) {
        subscribedSupportTicketIds.clear();
        return;
    }
    [...subscribedSupportTicketIds].forEach((id) => {
        window.Echo.leave(`support.ticket.${id}`);
    });
    subscribedSupportTicketIds.clear();
};

const syncSupportTicketEchoChannels = () => {
    if (typeof window === 'undefined' || !window.Echo || !canAccessSupportChat.value) {
        leaveAllSupportTicketEchoChannels();
        return;
    }

    const ids = new Set(tickets.value.map((t) => t.id));
    if (activeTicket.value?.id) {
        ids.add(activeTicket.value.id);
    }

    [...subscribedSupportTicketIds].forEach((id) => {
        if (!ids.has(id)) {
            window.Echo.leave(`support.ticket.${id}`);
            subscribedSupportTicketIds.delete(id);
        }
    });

    ids.forEach((id) => {
        if (subscribedSupportTicketIds.has(id)) {
            return;
        }
        window.Echo.private(`support.ticket.${id}`).listen('.SupportTicketUpdated', () => {
            fetchTickets(true);
            if (String(activeTicket.value?.id) === String(id)) {
                fetchTicketMessages(id);
            }
        });
        subscribedSupportTicketIds.add(id);
    });
};

const bubbleLayerStyle = computed(() => ({
    zIndex: isModalOpen.value ? 1 : 9999,
    pointerEvents: isModalOpen.value ? 'none' : 'auto',
    visibility: isModalOpen.value ? 'hidden' : 'visible',
    opacity: isModalOpen.value ? 0 : 1,
}));

const syncModalState = () => {
    if (typeof document === 'undefined') {
        isModalOpen.value = false;
        return;
    }

    const hasFrameworkModalClass = document.body.classList.contains('has-open-modal');
    const hasOpenDialog = Boolean(document.querySelector('dialog[open], .modal.modal-open'));
    const hasAriaModalOverlay = Boolean(
        document.querySelector('[role="dialog"][aria-modal="true"], [aria-modal="true"]')
    );
    const hasSweetAlert = Boolean(document.querySelector('.swal2-container'));

    isModalOpen.value = hasFrameworkModalClass || hasOpenDialog || hasAriaModalOverlay || hasSweetAlert;
};

const getPointerPosition = (event) => {
    if (event?.touches?.[0]) {
        return { x: event.touches[0].clientX, y: event.touches[0].clientY };
    }

    if (event?.changedTouches?.[0]) {
        return { x: event.changedTouches[0].clientX, y: event.changedTouches[0].clientY };
    }

    return { x: event.clientX, y: event.clientY };
};

const getElementHeight = (el) => {
    if (!el) {
        return 0;
    }

    const style = window.getComputedStyle(el);
    if (style.display === 'none' || style.visibility === 'hidden') {
        return 0;
    }

    return Math.max(0, Math.round(el.getBoundingClientRect().height));
};

const LG_BREAKPOINT = 1024;

/**
 * Bounding box used for viewport / sidebar clamping. When the chat surface is open,
 * it extends left of the FAB; use a wider effective rect so the whole panel clears the sidebar.
 */
const getEffectiveBubbleRect = () => {
    if (typeof window === 'undefined' || !bubbleAnchor.value) {
        return {
            width: 56,
            height: 56,
            left: 0,
            top: 0,
            right: 56,
            bottom: 56,
        };
    }

    const r = bubbleAnchor.value.getBoundingClientRect();
    const vw = window.innerWidth;
    const padding = 16;

    if (!isOpen.value) {
        return {
            width: r.width,
            height: r.height,
            left: r.left,
            top: r.top,
            right: r.right,
            bottom: r.bottom,
        };
    }

    const sidebar = document.getElementById('tenant-sidebar-panel');
    let panelMax = Math.min(380, Math.max(260, vw - padding * 2));

    if (vw >= LG_BREAKPOINT && sidebar) {
        const sb = sidebar.getBoundingClientRect();
        const visible = sb.width >= 40 && sb.right > 8 && sb.left < vw - 8;
        if (visible) {
            const centerSb = (sb.left + sb.right) / 2;
            if (centerSb < vw / 2) {
                panelMax = Math.min(panelMax, Math.max(240, vw - sb.right - padding - 12));
            } else {
                panelMax = Math.min(panelMax, Math.max(240, sb.left - padding - 12));
            }
        }
    }

    const effW = Math.max(r.width, panelMax);
    const effL = r.right - effW;

    return {
        width: effW,
        height: r.height,
        left: effL,
        top: r.top,
        right: r.right,
        bottom: r.bottom,
    };
};

const getLayoutSafeBounds = () => {
    const bubbleRect = getEffectiveBubbleRect();
    const padding = 16;
    const header = document.getElementById('tenant-top-header');
    const subnav = document.getElementById('tenant-subnav');
    const footer = document.getElementById('tenant-footer');
    const sidebar = document.getElementById('tenant-sidebar-panel');

    const headerHeight = getElementHeight(header);
    const subnavHeight = getElementHeight(subnav);
    const footerHeight = getElementHeight(footer);

    const vw = window.innerWidth;
    const vh = window.innerHeight;

    let minLeft = padding;
    let maxLeft = Math.round(vw - bubbleRect.width - padding);
    const minTop = Math.round(padding + headerHeight + subnavHeight);
    let maxTop = Math.round(vh - bubbleRect.height - footerHeight - padding);

    if (vw >= LG_BREAKPOINT && sidebar) {
        const sb = sidebar.getBoundingClientRect();
        const visible = sb.width >= 40 && sb.right > 8 && sb.left < vw - 8;

        if (visible) {
            const sideGap = 12;
            const centerSb = (sb.left + sb.right) / 2;

            if (centerSb < vw / 2) {
                minLeft = Math.max(minLeft, Math.round(sb.right + sideGap));
            } else {
                maxLeft = Math.min(maxLeft, Math.round(sb.left - bubbleRect.width - sideGap));
            }
        }
    }

    if (maxLeft < minLeft) {
        maxLeft = minLeft;
    }

    if (maxTop < minTop) {
        maxTop = minTop;
    }

    return {
        minLeft,
        maxLeft,
        minTop,
        maxTop,
    };
};

const clampOffsetsToViewport = (rightOffset, bottomOffset) => {
    if (typeof window === 'undefined' || !bubbleAnchor.value) {
        return {
            right: clampOffset(rightOffset, 24),
            bottom: clampOffset(bottomOffset, 56),
        };
    }

    const rect = bubbleAnchor.value.getBoundingClientRect();
    const eff = getEffectiveBubbleRect();
    const bounds = getLayoutSafeBounds();

    const requestedRight = Number.isFinite(rightOffset) ? Math.round(rightOffset) : 24;
    const requestedBottom = Number.isFinite(bottomOffset) ? Math.round(bottomOffset) : 56;

    const requestedLeft = Math.round(window.innerWidth - eff.width - requestedRight);
    const requestedTop = Math.round(window.innerHeight - rect.height - requestedBottom);

    const clampedLeft = Math.min(bounds.maxLeft, Math.max(bounds.minLeft, requestedLeft));
    const clampedTop = Math.min(bounds.maxTop, Math.max(bounds.minTop, requestedTop));

    const safeRight = Math.round(window.innerWidth - eff.width - clampedLeft);
    const safeBottom = Math.round(window.innerHeight - rect.height - clampedTop);

    return {
        right: Math.max(0, safeRight),
        bottom: Math.max(0, safeBottom),
    };
};

const scheduleBubbleAutoAdjust = () => {
    if (typeof window === 'undefined') {
        return;
    }

    if (clampFrame) {
        return;
    }

    clampFrame = window.requestAnimationFrame(() => {
        clampFrame = null;
        if (!isDraggingBubble.value) {
            clampCurrentBubblePosition();
        }
    });
};

const observeLayoutTargets = () => {
    if (!layoutResizeObserver || typeof document === 'undefined') {
        return;
    }

    ['tenant-top-header', 'tenant-subnav', 'tenant-footer', 'tenant-sidebar-panel', 'tenant-app-drawer'].forEach((id) => {
        const el = document.getElementById(id);
        if (el) {
            layoutResizeObserver.observe(el);
        }
    });
};

const saveRuntimeBubblePosition = (side = currentSidebarPosition.value) => {
    if (typeof window === 'undefined') {
        return;
    }

    const currentStore = window[runtimePositionStoreKey] && typeof window[runtimePositionStoreKey] === 'object'
        ? window[runtimePositionStoreKey]
        : {};

    currentStore[side] = {
        right: currentRightOffset.value,
        bottom: currentBottomOffset.value,
    };

    window[runtimePositionStoreKey] = currentStore;
};

const restoreRuntimeBubblePosition = (side = currentSidebarPosition.value) => {
    if (typeof window === 'undefined') {
        return false;
    }

    const runtimeStore = window[runtimePositionStoreKey];
    const runtimePosition = runtimeStore && typeof runtimeStore === 'object'
        ? runtimeStore[side]
        : null;

    if (!runtimePosition || typeof runtimePosition !== 'object') {
        return false;
    }

    currentRightOffset.value = clampOffset(runtimePosition.right, bubbleRightOffset.value);
    currentBottomOffset.value = clampOffset(runtimePosition.bottom, bubbleBottomOffset.value);

    return true;
};

const onBubblePointerMove = (event) => {
    if (!isDraggingBubble.value || !bubbleAnchor.value || isOpen.value) {
        return;
    }

    event.preventDefault();
    const point = getPointerPosition(event);
    const rect = bubbleAnchor.value.getBoundingClientRect();
    const nextLeft = point.x - dragState.value.offsetX;
    const nextTop = point.y - dragState.value.offsetY;

    const minLeft = 8;
    const minTop = 8;
    const maxLeft = Math.max(minLeft, window.innerWidth - rect.width - 8);
    const maxTop = Math.max(minTop, window.innerHeight - rect.height - 8);

    const clampedLeft = Math.min(maxLeft, Math.max(minLeft, nextLeft));
    const clampedTop = Math.min(maxTop, Math.max(minTop, nextTop));
    const nextRight = Math.round(window.innerWidth - (clampedLeft + rect.width));
    const nextBottom = Math.round(window.innerHeight - (clampedTop + rect.height));

    const clampedOffsets = clampOffsetsToViewport(nextRight, nextBottom);
    currentRightOffset.value = clampedOffsets.right;
    currentBottomOffset.value = clampedOffsets.bottom;

    if (!dragState.value.moved) {
        const deltaX = Math.abs(point.x - dragState.value.startX);
        const deltaY = Math.abs(point.y - dragState.value.startY);
        dragState.value.moved = deltaX > 4 || deltaY > 4;
    }
};

const onBubblePointerUp = () => {
    if (!isDraggingBubble.value) {
        return;
    }

    isDraggingBubble.value = false;
    window.removeEventListener('mousemove', onBubblePointerMove);
    window.removeEventListener('mouseup', onBubblePointerUp);
    window.removeEventListener('touchmove', onBubblePointerMove);
    window.removeEventListener('touchend', onBubblePointerUp);

    if (dragState.value.moved) {
        suppressNextToggle.value = true;
        saveRuntimeBubblePosition();
        setTimeout(() => {
            suppressNextToggle.value = false;
        }, 180);
    }

    dragState.value = {
        pointerId: null,
        startX: 0,
        startY: 0,
        offsetX: 0,
        offsetY: 0,
        moved: false,
    };
};

const startBubbleDrag = (event) => {
    if (isModalOpen.value || isOpen.value || !canAccessSupportChat.value || !bubbleAnchor.value) {
        return;
    }

    const point = getPointerPosition(event);
    const rect = bubbleAnchor.value.getBoundingClientRect();

    isDraggingBubble.value = true;
    dragState.value = {
        pointerId: event.pointerId ?? null,
        startX: point.x,
        startY: point.y,
        offsetX: point.x - rect.left,
        offsetY: point.y - rect.top,
        moved: false,
    };

    window.addEventListener('mousemove', onBubblePointerMove, { passive: false });
    window.addEventListener('mouseup', onBubblePointerUp);
    window.addEventListener('touchmove', onBubblePointerMove, { passive: false });
    window.addEventListener('touchend', onBubblePointerUp);
};

const handleToggle = () => {
    if (suppressNextToggle.value) {
        return;
    }

    toggle();
};

const clampCurrentBubblePosition = () => {
    const clamped = clampOffsetsToViewport(currentRightOffset.value, currentBottomOffset.value);
    currentRightOffset.value = clamped.right;
    currentBottomOffset.value = clamped.bottom;
    if (isOpen.value && typeof window !== 'undefined') {
        chatPanelWidthPx.value = Math.max(260, Math.min(380, Math.round(getEffectiveBubbleRect().width)));
    }
};

watch([bubbleBottomOffset, bubbleRightOffset], ([bottom, right]) => {
    if (isDraggingBubble.value) {
        return;
    }

    currentBottomOffset.value = bottom;
    currentRightOffset.value = right;
    restoreRuntimeBubblePosition();
    clampCurrentBubblePosition();
}, { immediate: true });

watch(currentSidebarPosition, (newSide, oldSide) => {
    if (newSide === oldSide || isDraggingBubble.value) {
        return;
    }

    saveRuntimeBubblePosition(oldSide);

    const restored = restoreRuntimeBubblePosition(newSide);
    if (!restored) {
        currentBottomOffset.value = bubbleBottomOffset.value;
        currentRightOffset.value = bubbleRightOffset.value;
    }

    clampCurrentBubblePosition();
});

watch([tickets, () => activeTicket.value?.id], () => {
    syncSupportTicketEchoChannels();
}, { deep: true });

const fetchTicketMessages = async (ticketId) => {
    try {
        const { data } = await axios.get(route('tenant.support.show', ticketId));
        const payload = unwrapSupportApi(data);
        const ticket = payload?.ticket;
        if (ticket && activeTicket.value && activeTicket.value.id === ticketId) {
            activeTicket.value.messages = ticket.messages;
            activeTicket.value.status = ticket.status;
        }
    } catch (e) {
        console.error('Failed to refresh messages', e);
    }
};

watch(() => activeTicket.value?.messages?.length, (newLen, oldLen) => {
    if (newLen > oldLen) {
        scrollToBottom();
    }
});

watch(activeView, (newView) => {
    if (newView === 'chat') {
        scrollToBottom();
    }
});

watch(isOpen, (newVal) => {
    if (newVal && activeView.value === 'chat') {
        scrollToBottom();
    }
    nextTick(() => {
        clampCurrentBubblePosition();
        scheduleBubbleAutoAdjust();
    });
});

// New ticket form
const newTicket = ref({
    subject: '',
    category: 'other',
    priority: 'medium',
    content: '',
});

const categories = [
    { value: 'billing', label: '💳 Billing' },
    { value: 'technical', label: '🐛 Technical / Bug' },
    { value: 'feature_request', label: '✨ Feature Request' },
    { value: 'account', label: '👤 Account' },
    { value: 'other', label: '📌 Other' },
];

const priorities = [
    { value: 'low', label: 'Low' },
    { value: 'medium', label: 'Medium' },
    { value: 'high', label: 'High' },
    { value: 'urgent', label: 'Urgent' },
];

const statusColors = {
    open: 'badge-info',
    in_progress: 'badge-warning',
    pending: 'badge-ghost',
    resolved: 'badge-success',
    closed: 'badge-neutral',
};

const toggle = () => {
    isOpen.value = !isOpen.value;
    if (isOpen.value && tickets.value.length === 0) {
        fetchTickets();
    }
};

const fetchTickets = async (silent = false) => {
    if (!silent) loading.value = true;
    try {
        const { data } = await axios.get(route('tenant.support.index'));
        const payload = unwrapSupportApi(data);
        tickets.value = payload.tickets || [];
        unreadCount.value = tickets.value.filter(t => t.status === 'open' || t.status === 'in_progress').length;
        syncSupportTicketEchoChannels();
    } catch (e) {
        console.error('Failed to fetch tickets', e);
    } finally {
        if (!silent) loading.value = false;
    }
};

const openTicket = async (ticket, silent = false) => {
    if (activeTicket.value && activeTicket.value.id !== ticket.id) {
        activeTicket.value = ticket;
    }
    activeView.value = 'chat';
    if (!silent) loading.value = true;
    try {
        const { data } = await axios.get(route('tenant.support.show', ticket.id));
        const payload = unwrapSupportApi(data);
        activeTicket.value = payload.ticket;
    } catch (e) {
        console.error('Failed to load ticket', e);
    } finally {
        if (!silent) loading.value = false;
        scrollToBottom();
    }
};

const sendMessage = async () => {
    if (!messageContent.value.trim() && attachments.value.length === 0) return;
    sending.value = true;

    const formData = new FormData();
    formData.append('content', messageContent.value);
    attachments.value.forEach(file => formData.append('attachments[]', file));

    try {
        const { data } = await axios.post(
            route('tenant.support.message', activeTicket.value.id),
            formData,
            { headers: { 'Content-Type': 'multipart/form-data' } }
        );
        const payload = unwrapSupportApi(data);
        if (payload.message) {
            activeTicket.value.messages.push(payload.message);
        }
        messageContent.value = '';
        attachments.value = [];
        scrollToBottom();
    } catch (e) {
        console.error('Failed to send message', e);
    } finally {
        sending.value = false;
    }
};

const createTicket = async () => {
    if (!newTicket.value.subject.trim() || !newTicket.value.content.trim()) return;
    sending.value = true;

    const formData = new FormData();
    formData.append('subject', newTicket.value.subject);
    formData.append('category', newTicket.value.category);
    formData.append('priority', newTicket.value.priority);
    formData.append('content', newTicket.value.content);
    attachments.value.forEach(file => formData.append('attachments[]', file));

    try {
        const { data } = await axios.post(route('tenant.support.store'), formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        const payload = unwrapSupportApi(data);
        const ticket = payload.ticket;
        if (ticket) {
            tickets.value.unshift(ticket);
            activeTicket.value = ticket;
            activeView.value = 'chat';
        }
        resetNewTicket();
    } catch (e) {
        console.error('Failed to create ticket', e);
    } finally {
        sending.value = false;
    }
};

const resetNewTicket = () => {
    newTicket.value = { subject: '', category: 'other', priority: 'medium', content: '' };
    attachments.value = [];
};

const handleFileSelect = (e) => {
    const files = Array.from(e.target.files);
    attachments.value.push(...files);
    if (fileInput.value) fileInput.value.value = '';
};

const removeAttachment = (index) => {
    attachments.value.splice(index, 1);
};

const scrollToBottom = () => {
    nextTick(() => {
        if (chatContainer.value) {
            chatContainer.value.scrollTop = chatContainer.value.scrollHeight;
            // Fallback for slower rendering/images
            setTimeout(() => {
                if (chatContainer.value) {
                    chatContainer.value.scrollTop = chatContainer.value.scrollHeight;
                }
            }, 100);
        }
    });
};

const goBack = () => {
    activeView.value = 'list';
    activeTicket.value = null;
    fetchTickets();
};

const formatTime = (dateStr) => {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    const now = new Date();
    const diffMs = now - d;
    const diffMins = Math.floor(diffMs / 60000);
    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins}m ago`;
    const diffHours = Math.floor(diffMins / 60);
    if (diffHours < 24) return `${diffHours}h ago`;
    const diffDays = Math.floor(diffHours / 24);
    if (diffDays < 7) return `${diffDays}d ago`;
    return d.toLocaleDateString();
};

const isImage = (type) => type && type.startsWith('image/');
const getAttachmentUrl = (msg, att) => {
    const fp = att?.file_path || '';
    if (!fp) return '';

    // Tenant-side messages are stored on tenant-scoped disks (Stancl filesystem bootstrapper),
    // so their physical files live under the tenant storage path and must be served via
    // the tenant storage route.
    if (msg?.sender_type === 'tenant') {
        return `/tenant-storage/${fp}`.replaceAll('//', '/');
    }

    const fallback = `/storage/${fp}`.replaceAll('//', '/');
    const raw = att?.url || '';
    if (!raw) return fallback;

    // Tenant portals run on per-tenant subdomains. If the backend generates absolute URLs
    // using APP_URL (central host), force same-origin by converting to a relative path.
    try {
        if (raw.startsWith('http://') || raw.startsWith('https://')) {
            const u = new URL(raw);
            return `${u.pathname}${u.search}${u.hash}`;
        }
    } catch {
        // ignore parsing issues and return raw below
    }

    return raw;
};

const getFileIcon = (type) => {
    if (type?.includes('pdf')) return '📄';
    if (type?.includes('doc')) return '📝';
    if (type?.includes('zip') || type?.includes('rar')) return '📦';
    return '📎';
};

const getMessageSenderName = (msg) => {
    if (msg?.sender_name) return msg.sender_name;
    if (msg?.sender?.name) return msg.sender.name;
    return msg?.sender_type === 'admin' ? 'Support Admin' : 'Clinic Staff';
};

const getMessageAvatar = (msg) => {
    if (msg?.sender_avatar_url) return msg.sender_avatar_url;
    if (msg?.sender?.profile_picture_url) return msg.sender.profile_picture_url;
    if (msg?.sender?.profile_photo_url) return msg.sender.profile_photo_url;

    const fallbackName = getMessageSenderName(msg);
    const bg = msg?.sender_type === 'admin' ? '334155' : '1F2937';
    return `https://ui-avatars.com/api/?name=${encodeURIComponent(fallbackName)}&color=FFFFFF&background=${bg}`;
};

onMounted(() => {
    syncModalState();
    restoreRuntimeBubblePosition();
    clampCurrentBubblePosition();

    if (typeof MutationObserver !== 'undefined' && typeof document !== 'undefined') {
        bodyClassObserver = new MutationObserver(() => {
            syncModalState();
        });

        bodyClassObserver.observe(document.body, {
            attributes: true,
            attributeFilter: ['class'],
        });
    }

    if (typeof window !== 'undefined') {
        window.addEventListener('resize', scheduleBubbleAutoAdjust);
        window.addEventListener('dcms-portal-layout-changed', scheduleBubbleAutoAdjust);
    }

    if (typeof ResizeObserver !== 'undefined') {
        layoutResizeObserver = new ResizeObserver(() => {
            scheduleBubbleAutoAdjust();
        });
        observeLayoutTargets();
    }

    if (typeof MutationObserver !== 'undefined' && typeof document !== 'undefined') {
        layoutMutationObserver = new MutationObserver(() => {
            observeLayoutTargets();
            scheduleBubbleAutoAdjust();
        });

        layoutMutationObserver.observe(document.body, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['class', 'style'],
        });
    }

    if (canAccessSupportChat.value) {
        fetchTickets(true);
    }
});

onUnmounted(() => {
    if (bodyClassObserver) {
        bodyClassObserver.disconnect();
        bodyClassObserver = null;
    }

    if (typeof window !== 'undefined') {
        window.removeEventListener('resize', scheduleBubbleAutoAdjust);
        window.removeEventListener('dcms-portal-layout-changed', scheduleBubbleAutoAdjust);
    }

    if (layoutResizeObserver) {
        layoutResizeObserver.disconnect();
        layoutResizeObserver = null;
    }

    if (layoutMutationObserver) {
        layoutMutationObserver.disconnect();
        layoutMutationObserver = null;
    }

    if (clampFrame && typeof window !== 'undefined') {
        window.cancelAnimationFrame(clampFrame);
        clampFrame = null;
    }

    onBubblePointerUp();

    leaveAllSupportTicketEchoChannels();
});
</script>

<template>
    <div v-if="canAccessSupportChat">
    <!-- Floating Chat Bubble -->
    <div
        ref="bubbleAnchor"
        class="fixed"
        :style="{
            right: `${currentRightOffset}px`,
            bottom: `${currentBottomOffset}px`,
            ...bubbleLayerStyle,
        }"
    >
        <!-- Unread Badge -->
        <div 
            v-if="unreadCount > 0 && !isOpen"
            class="absolute -top-2 -right-1 h-5 min-w-5 px-1 rounded-full bg-error text-white text-[10px] font-black flex items-center justify-center shadow-lg animate-bounce z-10"
        >
            {{ unreadCount }}
        </div>

        <!-- Chat Bubble Button -->
        <button
            @mousedown="startBubbleDrag"
            @touchstart="startBubbleDrag"
            @click="handleToggle"
            class="h-14 w-14 rounded-full shadow-2xl flex items-center justify-center transition-all duration-300 hover:scale-110 hover:shadow-primary/40"
            :class="!isOpen ? 'cursor-grab active:cursor-grabbing' : ''"
            :style="{ backgroundColor: primaryColor }"
        >
            <!-- Chat Icon -->
            <svg v-if="!isOpen" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="white" class="w-7 h-7">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />
            </svg>
            <!-- Close Icon -->
            <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="white" class="w-7 h-7">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>

        <!-- Chat Window -->
        <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 translate-y-4 scale-95"
            enter-to-class="opacity-100 translate-y-0 scale-100"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100 translate-y-0 scale-100"
            leave-to-class="opacity-0 translate-y-4 scale-95"
        >
            <div 
                v-if="isOpen"
                class="absolute bottom-20 right-0 max-h-[520px] bg-base-100 rounded-2xl shadow-2xl border border-base-300 overflow-hidden flex flex-col sm:min-w-[260px]"
                :style="{ width: `${chatPanelWidthPx}px`, maxWidth: `${chatPanelWidthPx}px` }"
            >
                <!-- Header -->
                <div 
                    class="px-5 py-4 flex items-center justify-between shrink-0"
                    :style="{ backgroundColor: primaryColor }"
                >
                    <div class="flex items-center gap-3">
                        <button 
                            v-if="activeView !== 'list'" 
                            @click="goBack"
                            class="text-white/80 hover:text-white transition-colors"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                            </svg>
                        </button>
                        <div>
                            <h3 class="text-white font-black text-sm tracking-wide">
                                {{ activeView === 'list' ? 'Support Chat' : activeView === 'new' ? 'New Ticket' : activeTicket?.subject }}
                            </h3>
                            <p v-if="activeView === 'chat' && activeTicket" class="text-white/60 text-[10px] uppercase tracking-widest font-bold">
                                {{ activeTicket.category }} · {{ activeTicket.status?.replace('_', ' ') }}
                            </p>
                            <p v-else-if="activeView === 'list'" class="text-white/60 text-[10px] uppercase tracking-widest font-bold">
                                We're here to help
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Ticket List View -->
                <div v-if="activeView === 'list'" class="flex-1 overflow-y-auto">
                    <div v-if="loading" class="flex items-center justify-center py-12">
                        <span class="loading loading-spinner loading-md" :style="{ color: primaryColor }"></span>
                    </div>

                    <div v-else-if="tickets.length === 0" class="flex flex-col items-center justify-center py-12 px-6 text-center">
                        <div class="w-16 h-16 rounded-full bg-base-200 flex items-center justify-center mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-base-content/30">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />
                            </svg>
                        </div>
                        <p class="text-base-content/50 text-xs font-semibold">No conversations yet</p>
                        <p class="text-base-content/30 text-[10px] mt-1">Start a new ticket below</p>
                    </div>

                    <div v-else class="divide-y divide-base-200">
                        <button
                            v-for="ticket in tickets"
                            :key="ticket.id"
                            @click="openTicket(ticket)"
                            class="w-full text-left px-5 py-3.5 hover:bg-base-200/50 transition-colors group"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-bold text-base-content truncate group-hover:text-primary transition-colors">{{ ticket.subject }}</p>
                                    <p class="text-xs text-base-content/40 mt-0.5 truncate">
                                        {{ ticket.latest_message?.content || ticket.description }}
                                    </p>
                                </div>
                                <div class="flex flex-col items-end gap-1 shrink-0">
                                    <span class="text-[10px] text-base-content/30 font-medium">{{ formatTime(ticket.updated_at) }}</span>
                                    <span :class="statusColors[ticket.status]" class="badge badge-xs text-[9px] font-bold uppercase">
                                        {{ ticket.status?.replace('_', ' ') }}
                                    </span>
                                </div>
                            </div>
                        </button>
                    </div>

                    <!-- New Ticket Button -->
                    <div class="p-4 border-t border-base-200">
                        <button 
                            @click="activeView = 'new'"
                            class="btn btn-sm w-full rounded-xl font-bold text-white border-0 shadow-lg"
                            :style="{ backgroundColor: primaryColor }"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            New Ticket
                        </button>
                    </div>
                </div>

                <!-- New Ticket Form -->
                <div v-if="activeView === 'new'" class="flex-1 overflow-y-auto p-4 space-y-3">
                    <div>
                        <label class="text-[10px] font-bold uppercase tracking-widest text-base-content/40 mb-1 block">Subject</label>
                        <input v-model="newTicket.subject" type="text" placeholder="Brief description..." class="input input-sm input-bordered w-full rounded-xl text-sm" />
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-[10px] font-bold uppercase tracking-widest text-base-content/40 mb-1 block">Category</label>
                            <select v-model="newTicket.category" class="select select-sm select-bordered w-full rounded-xl text-xs">
                                <option v-for="cat in categories" :key="cat.value" :value="cat.value">{{ cat.label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-[10px] font-bold uppercase tracking-widest text-base-content/40 mb-1 block">Priority</label>
                            <select v-model="newTicket.priority" class="select select-sm select-bordered w-full rounded-xl text-xs">
                                <option v-for="p in priorities" :key="p.value" :value="p.value">{{ p.label }}</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="text-[10px] font-bold uppercase tracking-widest text-base-content/40 mb-1 block">Message</label>
                        <textarea v-model="newTicket.content" rows="4" placeholder="Describe your issue..." class="textarea textarea-sm textarea-bordered w-full rounded-xl text-sm resize-none"></textarea>
                    </div>

                    <!-- Attachment Preview -->
                    <div v-if="attachments.length > 0" class="flex flex-wrap gap-2">
                        <div v-for="(file, i) in attachments" :key="i" class="badge badge-sm gap-1 bg-base-200 border-base-300">
                            <span class="truncate max-w-[100px] text-[10px]">{{ file.name }}</span>
                            <button @click="removeAttachment(i)" class="text-error hover:text-error/80">✕</button>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <button @click="$refs.fileInputNew.click()" class="btn btn-sm btn-ghost btn-circle">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 text-base-content/40">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13" />
                            </svg>
                        </button>
                        <input ref="fileInputNew" type="file" multiple accept="image/*,.pdf,.doc,.docx,.zip" @change="handleFileSelect" class="hidden" />
                        <button 
                            @click="createTicket"
                            :disabled="!newTicket.subject.trim() || !newTicket.content.trim() || sending"
                            class="btn btn-sm flex-1 rounded-xl font-bold text-white border-0"
                            :style="{ backgroundColor: primaryColor }"
                        >
                            <span v-if="sending" class="loading loading-spinner loading-xs"></span>
                            <span v-else>Submit Ticket</span>
                        </button>
                    </div>
                </div>

                <!-- Chat View -->
                <div v-if="activeView === 'chat'" class="flex-1 flex flex-col overflow-hidden">
                    <!-- Messages -->
                    <div ref="chatContainer" class="flex-1 overflow-y-auto p-4 space-y-3">
                        <div v-if="loading" class="flex items-center justify-center py-8">
                            <span class="loading loading-spinner loading-md" :style="{ color: primaryColor }"></span>
                        </div>

                        <template v-else-if="activeTicket?.messages">
                            <div 
                                v-for="msg in activeTicket.messages" 
                                :key="msg.id"
                                :class="msg.sender_type === 'tenant' ? 'items-end' : 'items-start'"
                                class="flex flex-col"
                            >
                                <div class="flex items-end gap-2" :class="msg.sender_type === 'tenant' ? 'flex-row-reverse' : ''">
                                    <!-- Avatar -->
                                    <div class="h-8 w-8 rounded-full shrink-0 overflow-hidden border border-base-300 bg-base-200">
                                        <img :src="getMessageAvatar(msg)" :alt="getMessageSenderName(msg)" class="h-full w-full object-cover" />
                                    </div>

                                    <!-- Bubble -->
                                    <div 
                                        class="max-w-[260px] px-4 py-2.5 text-sm rounded-2xl shadow-sm"
                                        :class="msg.sender_type === 'tenant' 
                                            ? 'rounded-br-md text-white' 
                                            : 'rounded-bl-md bg-base-200 text-base-content'"
                                        :style="msg.sender_type === 'tenant' ? { backgroundColor: primaryColor } : {}"
                                    >
                                        <p class="text-[10px] font-black mb-1.5 opacity-80 tracking-wide">
                                            {{ getMessageSenderName(msg) }}
                                        </p>
                                        <p class="whitespace-pre-wrap break-words text-[13px]">{{ msg.content }}</p>

                                        <!-- Attachments -->
                                        <div v-if="msg.attachments?.length" class="mt-2 space-y-1.5">
                                            <template v-for="att in msg.attachments" :key="att.id">
                                                <a 
                                                    v-if="isImage(att.file_type)" 
                                                    :href="getAttachmentUrl(msg, att)" 
                                                    target="_blank"
                                                    class="block rounded-lg overflow-hidden border border-white/20"
                                                >
                                                    <img :src="getAttachmentUrl(msg, att)" :alt="att.file_name" class="w-full max-h-32 object-cover" />
                                                </a>
                                                <a 
                                                    v-else 
                                                    :href="getAttachmentUrl(msg, att)" 
                                                    target="_blank"
                                                    class="flex items-center gap-1.5 text-[11px] px-2 py-1 rounded-lg"
                                                    :class="msg.sender_type === 'tenant' ? 'text-white/80 bg-white/10 hover:bg-white/20' : 'text-base-content/60 bg-base-300 hover:bg-base-300/80'"
                                                >
                                                    <span>{{ getFileIcon(att.file_type) }}</span>
                                                    <span class="truncate max-w-[150px]">{{ att.file_name }}</span>
                                                </a>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                                <span 
                                    class="text-[9px] mt-1 px-10 font-medium"
                                    :class="msg.sender_type === 'tenant' ? 'text-right text-base-content/25' : 'text-base-content/25'"
                                >
                                    {{ formatTime(msg.created_at) }}
                                </span>
                            </div>
                        </template>
                    </div>

                    <!-- Message Input -->
                    <div class="border-t border-base-200 p-3 shrink-0 bg-base-100">
                        <!-- Attachment Preview -->
                        <div v-if="attachments.length > 0" class="flex flex-wrap gap-1.5 mb-2">
                            <div v-for="(file, i) in attachments" :key="i" class="badge badge-sm gap-1 bg-base-200 border-base-300">
                                <span class="truncate max-w-[80px] text-[10px]">{{ file.name }}</span>
                                <button @click="removeAttachment(i)" class="text-error hover:text-error/80">✕</button>
                            </div>
                        </div>

                        <div class="flex items-end gap-2">
                            <button @click="$refs.fileInputChat.click()" class="btn btn-sm btn-ghost btn-circle shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 text-base-content/40">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13" />
                                </svg>
                            </button>
                            <input ref="fileInputChat" type="file" multiple accept="image/*,.pdf,.doc,.docx,.zip" @change="handleFileSelect" class="hidden" />

                            <textarea
                                v-model="messageContent"
                                @keydown.enter.exact.prevent="sendMessage"
                                placeholder="Type a message..."
                                rows="1"
                                class="textarea textarea-sm textarea-bordered flex-1 rounded-xl text-sm resize-none min-h-[36px] max-h-[80px]"
                            ></textarea>

                            <button 
                                @click="sendMessage"
                                :disabled="(!messageContent.trim() && attachments.length === 0) || sending"
                                class="btn btn-sm btn-circle shrink-0 border-0 text-white"
                                :style="{ backgroundColor: primaryColor }"
                            >
                                <span v-if="sending" class="loading loading-spinner loading-xs"></span>
                                <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
    </div>
</template>
