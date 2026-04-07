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
            if (data.success) {
                // If it's the same ticket, just update messages to avoid jumping if not needed
                if (this.selectedTicket && this.selectedTicket.id === data.ticket.id) {
                    this.selectedTicket.messages = data.ticket.messages;
                    this.selectedTicket.status = data.ticket.status;
                } else {
                    this.selectedTicket = data.ticket;
                }
            }
        } catch (e) {
            console.error('Failed to load ticket:', e);
        } finally {
            if (!silent) this.loading = false;
        }
    },

    close() {
        this.isOpen = false;
        this.selectedTicket = null;
    }
});
