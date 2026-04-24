<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import { router, usePage } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import ShowAppointments from './ShowAppointments.vue';
import EditAppointments from './EditAppointments.vue';
import DeleteAppointments from './DeleteAppointments.vue';
import CreateTreatment from '../Treatments/CreateTreatment.vue';

const props = defineProps({
    appointments: Array,
    dentists: Array,
    selectedAssociates: Array,
    selectedTypes: Array
});

const permissions = computed(() => usePage().props.auth.user.permissions);
const canEdit = computed(() => permissions.value.includes('edit appointments'));
const canDelete = computed(() => permissions.value.includes('delete appointments'));
const canCreateProgressNote = computed(() => permissions.value.includes('create progress notes'));
const canCancel = computed(() => canDelete.value || canEdit.value);

// Calendar day dropdown state
const showDayDropdown = ref(false);
const selectedDayData = ref([]);
const selectedDateString = ref('');
const dayDropdownStyle = ref({ top: '0px', left: '0px' });
const dayDropdownWidth = ref(360);
const calendarContainerRef = ref(null);

// Item Detail States
const showDetailModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const selectedAppointment = ref(null);
const showProgressNoteModal = ref(false);
const progressNoteInitialPatientId = ref(0);
const progressNotePatients = ref([]);
const treatmentServices = ref([]);
const treatmentDentists = ref([]);

const openDetail = (apt) => {
    selectedAppointment.value = apt;
    showDetailModal.value = true;
};

const openEdit = (apt) => {
    selectedAppointment.value = apt;
    showEditModal.value = true;
};

const openDelete = (apt) => {
    selectedAppointment.value = apt;
    showDeleteModal.value = true;
};

const loadTreatmentOptions = async () => {
    if (treatmentServices.value.length > 0 && treatmentDentists.value.length > 0) {
        return;
    }

    try {
        const response = await fetch('/treatments/options', {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) return;
        const payload = await response.json();
        treatmentServices.value = Array.isArray(payload?.services) ? payload.services : [];
        treatmentDentists.value = Array.isArray(payload?.dentists) ? payload.dentists : [];
    } catch (e) {
        // Silent fallback; modal will still open with available defaults.
    }
};

const openProgressNote = async (apt) => {
    const patientId = Number(apt?.patient_id || apt?.patient?.id || 0);
    if (!patientId) {
        Swal.fire({
            icon: 'warning',
            title: 'Patient Not Registered Yet',
            text: 'Approve or assign this booking to a patient first before adding a progress note.',
        });
        return;
    }

    progressNoteInitialPatientId.value = patientId;
    progressNotePatients.value = [{
        id: patientId,
        first_name: apt?.patient?.first_name || apt?.guest_first_name || 'Patient',
        last_name: apt?.patient?.last_name || apt?.guest_last_name || '',
    }];

    await loadTreatmentOptions();
    showProgressNoteModal.value = true;
};

const cancelAppointment = (apt) => {
    Swal.fire({
        title: 'Cancel Appointment?',
        text: 'This appointment will be marked as cancelled and removed from the booking queue.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Cancel',
        confirmButtonColor: '#dc2626',
    }).then((result) => {
        if (!result.isConfirmed) return;

        router.put(route('appointments.update', apt.id, false), {
            status: 'cancelled',
        }, {
            preserveScroll: true,
            onSuccess: () => {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Appointment cancelled',
                    showConfirmButton: false,
                    timer: 2200,
                });
                closeDayDropdown();
            },
        });
    });
};

const filteredAppointments = computed(() => {
    return props.appointments.filter(apt => {
        if (String(apt?.status || '').toLowerCase() === 'cancelled') {
            return false;
        }

        const matchesAssociate = !apt.dentist_id || props.selectedAssociates.includes(apt.dentist_id);
        const matchesType = props.selectedTypes.includes(apt.type || 'appointment');
        return matchesAssociate && matchesType;
    });
});

const getAppointmentName = (apt) => {
    if (apt?.patient) {
        return `${apt.patient.first_name || ''} ${apt.patient.last_name || ''}`.trim() || 'Patient';
    }

    return `${apt?.guest_first_name || ''} ${apt?.guest_last_name || ''}`.trim() || 'Guest';
};

const formatCellTime = (dateString) => {
    if (!dateString) return '--:--';

    const date = new Date(dateString);
    if (Number.isNaN(date.getTime())) return '--:--';

    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};

const getDateKey = (dateValue) => {
    const date = new Date(dateValue || 0);
    if (Number.isNaN(date.getTime())) return '';
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
};

const dayEntriesMap = computed(() => {
    const byDate = {};

    filteredAppointments.value.forEach((apt) => {
        const key = getDateKey(apt.appointment_date);
        if (!key) return;

        if (!byDate[key]) {
            byDate[key] = [];
        }

        byDate[key].push(apt);
    });

    Object.keys(byDate).forEach((key) => {
        byDate[key].sort((a, b) => {
            const left = new Date(a.appointment_date || 0).getTime();
            const right = new Date(b.appointment_date || 0).getTime();

            if (left === right) {
                return Number(a.id || 0) - Number(b.id || 0);
            }

            return left - right;
        });
    });

    return byDate;
});

const getDayEntriesForDate = (date) => {
    const key = getDateKey(date);
    return dayEntriesMap.value[key] || [];
};

const renderDayCellContent = (arg) => {
    const entries = getDayEntriesForDate(arg.date);
    const weekendClass = [0, 6].includes(arg.date.getDay()) ? 'is-weekend' : '';
    const otherClass = arg.isOther ? 'is-other' : '';

    const iconsHtml = entries.slice(0, 24).map((apt) => {
        const color = apt?.dentist?.calendar_color || '#3b82f6';
        return `<span class="calendar-day-cell__icon" style="color:${color}"><svg viewBox="0 0 24 24" aria-hidden="true" class="calendar-user-icon" fill="currentColor"><path d="M12 12a4.2 4.2 0 1 0 0-8.4 4.2 4.2 0 0 0 0 8.4z" /><path d="M4.8 20.4c0-3.1 2.5-5.6 5.6-5.6h3.2c3.1 0 5.6 2.5 5.6 5.6 0 .7-.5 1.2-1.2 1.2H6c-.7 0-1.2-.5-1.2-1.2z" /></svg></span>`;
    }).join('');

    const moreHtml = entries.length > 24
        ? `<span class="calendar-day-cell__more">+${entries.length - 24}</span>`
        : '';

    const badgeHtml = entries.length > 0
        ? `<span class="calendar-day-cell__badge">${entries.length}</span>`
        : '';

    return {
        html: `<div class="calendar-day-cell ${entries.length > 0 ? 'has-events' : ''}"><div class="calendar-day-cell__header">${badgeHtml}<span class="calendar-day-cell__number ${otherClass} ${weekendClass}">${arg.date.getDate()}</span></div>${entries.length > 0 ? `<div class="calendar-day-cell__icons">${iconsHtml}${moreHtml}</div>` : ''}</div>`,
    };
};

const placeDayDropdown = (event) => {
    const containerRect = calendarContainerRef.value?.getBoundingClientRect();

    if (!containerRect) {
        return;
    }

    const panelWidth = Math.max(260, Math.min(360, Math.floor(containerRect.width - 16)));
    const panelHeight = 320;
    const gap = 10;

    dayDropdownWidth.value = panelWidth;

    const clickX = event?.clientX || (containerRect.left + gap);
    const clickY = event?.clientY || (containerRect.top + gap);

    // Convert pointer position from viewport to calendar-container local coordinates.
    const localX = clickX - containerRect.left;
    const localY = clickY - containerRect.top;

    let left = localX + gap;
    let top = localY + gap;

    if (left + panelWidth > containerRect.width - gap) {
        left = localX - panelWidth - gap;
    }

    if (top + panelHeight > containerRect.height - gap) {
        top = localY - panelHeight - gap;
    }

    left = Math.max(gap, Math.min(left, containerRect.width - panelWidth - gap));
    top = Math.max(gap, Math.min(top, containerRect.height - panelHeight - gap));

    dayDropdownStyle.value = {
        left: `${left}px`,
        top: `${top}px`,
    };
};

const closeDayDropdown = () => {
    showDayDropdown.value = false;
};

const onDocumentClick = (event) => {
    if (!showDayDropdown.value) return;

    const target = event?.target;
    if (!target) return;

    if (target.closest('.calendar-day-dropdown') || target.closest('.fc-daygrid-day')) {
        return;
    }

    closeDayDropdown();
};

onMounted(() => {
    document.addEventListener('click', onDocumentClick);
});

onUnmounted(() => {
    document.removeEventListener('click', onDocumentClick);
});

const calendarOptions = computed(() => ({
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
    initialView: 'dayGridMonth',
    headerToolbar: {
        left: 'prev,today,next',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay'
    },
    dayMaxEvents: true,
    height: 'auto',
    dayCellContent: renderDayCellContent,
    events: filteredAppointments.value.map(apt => ({
        id: apt.id,
        title: apt.patient ? `${apt.patient.first_name} ${apt.patient.last_name}` : `${apt.guest_first_name} ${apt.guest_last_name}`,
        start: apt.appointment_date,
        extendedProps: apt,
        color: apt.dentist?.calendar_color || '#3b82f6'
    })),
    eventClick: (info) => {
        openDetail(info.event.extendedProps);
    },
    dateClick: (info) => {
        const dayAppointments = getDayEntriesForDate(info.dateStr);
        
        if (dayAppointments.length > 0) {
            selectedDayData.value = dayAppointments;
            selectedDateString.value = new Date(info.dateStr).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
            placeDayDropdown(info.jsEvent);
            showDayDropdown.value = true;
        }
    }
}));

const approveBooking = (id) => {
    Swal.fire({
        title: 'Approve Booking?',
        text: 'Approve this online booking and register the patient?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Approve',
        confirmButtonColor: '#0284c7',
        cancelButtonText: 'Cancel',
    }).then((result) => {
        if (!result.isConfirmed) return;

        router.post(`/appointments/${id}/approve`, {}, {
            preserveScroll: true,
            onSuccess: () => {
                showDayDropdown.value = false;
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Booking approved',
                    showConfirmButton: false,
                    timer: 2200,
                });
            },
        });
    });
};

</script>

<template>
    <div ref="calendarContainerRef" class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 relative h-full">
        <FullCalendar 
            :options="calendarOptions"
        >
            <template #eventContent="arg">
                <template v-if="arg.view.type === 'dayGridMonth'">
                    <span class="hidden"></span>
                </template>
                <template v-else>
                    <div class="calendar-time-event">
                        <span>{{ arg.timeText }}</span>
                        <span class="ml-1">{{ arg.event.title }}</span>
                    </div>
                </template>
            </template>
        </FullCalendar>

        <!-- Day Detail Dropdown -->
        <div
            v-if="showDayDropdown"
            class="calendar-day-dropdown absolute z-[120] rounded-xl border border-base-300 bg-base-100 shadow-2xl"
            :style="{ ...dayDropdownStyle, width: `${dayDropdownWidth}px`, maxWidth: '92vw' }"
        >
            <div class="px-4 py-3 border-b border-base-300 flex items-center justify-between">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-widest text-base-content/50">Booked Patients</p>
                    <p class="text-sm font-black text-base-content">{{ selectedDateString }}</p>
                </div>
                <button @click="closeDayDropdown" class="btn btn-ghost btn-xs">Close</button>
            </div>

            <div class="max-h-[320px] overflow-y-auto p-2">
                <div
                    v-for="apt in selectedDayData"
                    :key="`dropdown-apt-${apt.id}`"
                    class="rounded-lg p-2 hover:bg-base-200/70"
                >
                    <div class="flex items-start gap-2">
                        <span class="inline-flex shrink-0" :style="{ color: apt.dentist?.calendar_color || '#3b82f6' }">
                            <svg viewBox="0 0 24 24" aria-hidden="true" class="calendar-overlay-user-icon" fill="currentColor">
                                <path d="M12 12a4.2 4.2 0 1 0 0-8.4 4.2 4.2 0 0 0 0 8.4z" />
                                <path d="M4.8 20.4c0-3.1 2.5-5.6 5.6-5.6h3.2c3.1 0 5.6 2.5 5.6 5.6 0 .7-.5 1.2-1.2 1.2H6c-.7 0-1.2-.5-1.2-1.2z" />
                            </svg>
                        </span>

                        <div class="min-w-0 flex-1 cursor-pointer" @click="openDetail(apt)">
                            <p class="text-xs font-black text-base-content truncate">{{ getAppointmentName(apt) }}</p>
                            <p class="text-[10px] font-bold text-base-content/55 uppercase tracking-wider break-words">
                                {{ formatCellTime(apt.appointment_date) }} · {{ apt.service || 'General' }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-2 flex flex-wrap gap-1.5 pl-7">
                        <button type="button" class="btn btn-xs" @click="openDetail(apt)">View</button>
                        <button v-if="canEdit" type="button" class="btn btn-xs" @click="openEdit(apt)">Edit</button>
                        <button
                            v-if="canCreateProgressNote"
                            type="button"
                            class="btn btn-xs"
                            @click="openProgressNote(apt)"
                        >
                            Add Progress Note
                        </button>
                        <button
                            v-if="canCancel"
                            type="button"
                            class="btn btn-xs btn-error btn-outline"
                            @click="cancelAppointment(apt)"
                        >
                            Cancel Appointment
                        </button>
                        <button
                            v-if="!apt.patient && (apt.type === 'online_booking' || !apt.patient_id)"
                            type="button"
                            class="btn btn-xs btn-primary"
                            @click="approveBooking(apt.id)"
                        >
                            Approve
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Granular Component Modals -->
        <ShowAppointments 
            :appointment="selectedAppointment"
            :show="showDetailModal"
            @close="showDetailModal = false"
        />

        <EditAppointments 
            :appointment="selectedAppointment"
            :show="showEditModal"
            @close="showEditModal = false"
        />

        <DeleteAppointments 
            :appointment-id="selectedAppointment?.id"
            :show="showDeleteModal"
            @close="showDeleteModal = false"
        />

        <CreateTreatment
            :show="showProgressNoteModal"
            :patients="progressNotePatients"
            :services="treatmentServices"
            :dentists="treatmentDentists.length ? treatmentDentists : dentists"
            :initial-patient-id="progressNoteInitialPatientId"
            :render-in-place="false"
            @saved="showProgressNoteModal = false"
            @close="showProgressNoteModal = false"
        />
    </div>
</template>

<style scoped>
/* Scoped overrides for the nested component */
:deep(.fc-theme-standard td), :deep(.fc-theme-standard th) {
    border-color: #f3f4f6;
}
:deep(.fc-day-today) {
    background-color: #eff6ff !important;
}
:deep(.fc-col-header-cell) {
    background-color: #f9fafb;
    padding: 10px 0 !important;
}
:deep(.fc-toolbar-title) {
    font-size: 1.25rem !important;
    font-weight: 700 !important;
    color: #111827;
}
:deep(.fc-button-primary) {
    background-color: #ffffff !important;
    border-color: #d1d5db !important;
    color: #374151 !important;
    text-transform: capitalize !important;
    font-weight: 600 !important;
}
:deep(.fc-button-active) {
    background-color: #3b82f6 !important;
    border-color: #3b82f6 !important;
    color: white !important;
}
:deep(.fc-daygrid-day-frame) {
    min-height: 115px;
}

:deep(.calendar-day-cell) {
    display: flex;
    flex-direction: column;
    height: 100%;
    padding: 3px 4px;
}

:deep(.calendar-day-cell__header) {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 2px;
}

:deep(.calendar-day-cell__number) {
    font-size: 12px;
    font-weight: 700;
    color: #6b7280;
}

:deep(.calendar-day-cell__number.is-other) {
    color: #c2c6cc;
}

:deep(.calendar-day-cell__number.is-weekend) {
    color: #b86b6b;
}

:deep(.calendar-day-cell__badge) {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
    height: 20px;
    padding: 0 6px;
    border-radius: 999px;
    background: #b44a4a;
    color: #fff;
    font-size: 11px;
    font-weight: 900;
}

:deep(.calendar-day-cell__icons) {
    margin-top: auto;
    display: flex;
    flex-wrap: wrap;
    gap: 1px;
    align-items: center;
}

:deep(.calendar-day-cell__icon) {
    display: inline-flex;
    line-height: 1;
}

:deep(.calendar-user-icon) {
    width: 10px;
    height: 10px;
    display: block;
}

:deep(.calendar-day-cell__more) {
    font-size: 10px;
    font-weight: 900;
    color: #6b7280;
    margin-left: 2px;
}

:deep(.calendar-overlay-user-icon) {
    width: 20px;
    height: 20px;
    display: block;
}

:deep(.calendar-time-event) {
    font-size: 11px;
    font-weight: 700;
}

/* Month grid uses custom icon cluster content only. */
:deep(.fc-dayGridMonth-view .fc-daygrid-event-harness) {
    display: none !important;
}
</style>
