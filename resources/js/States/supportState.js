import { reactive } from 'vue';
import axios from 'axios';

export const supportState = reactive({
    isOpen: false,
    selectedTicket: null,
    loading: false,

    async viewTicket(ticketId, silent = false) {
        if (!silent) {
            this.loading = true;
            this.isOpen = true;
        }
        try {
            const { data } = await axios.get(route('admin.support.show', ticketId));
            const payload = data?.success && data.data !== undefined ? data.data : data;
            const ticket = payload?.ticket;
            if (data?.success && ticket) {
                if (this.selectedTicket && this.selectedTicket.id === ticket.id) {
                    this.selectedTicket.messages = ticket.messages;
                    this.selectedTicket.status = ticket.status;
                } else {
                    this.selectedTicket = ticket;
                }
            }
        } catch (e) {
            console.error('Failed to load ticket:', e);
        } finally {
            if (!silent) {
                this.loading = false;
            }
        }
    },

    close() {
        this.isOpen = false;
        this.selectedTicket = null;
    }
});
