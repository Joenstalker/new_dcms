<script setup>
import { brandingState } from '@/States/brandingState';
import { ref, computed, watch } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

const props = defineProps({
    patients: { type: Array, default: () => [] },
    invoices: { type: Array, default: () => [] },
    services: { type: Array, default: () => [] },
});

const primaryColor = computed(() => brandingState.primary_color);

// Cart / POS state
const selectedPatient = ref('');
const cartItems = ref([]);
const paymentMethod = ref('cash');
const discount = ref(0);

// Watch for patient selection to auto-fetch treatments
watch(selectedPatient, (newId) => {
    if (!newId) {
        cartItems.value = [];
        return;
    }

    const patient = props.patients.find(p => p.id === newId);
    if (patient && patient.treatments && patient.treatments.length > 0) {
        // Map treatments to cart items
        cartItems.value = patient.treatments.map(t => ({
            id: `treatment-${t.id}`,
            description: t.procedure || t.service?.name || 'Dental Service',
            quantity: 1,
            unit_price: Number(t.total_amount_due || t.cost || 0),
            treatment_id: t.id
        }));
    } else {
        cartItems.value = [];
    }
});

// Add item to cart
const newItem = ref({ description: '', quantity: 1, unit_price: 0 });

const addToCart = () => {
    if (!newItem.value.description || newItem.value.unit_price <= 0) return;
    cartItems.value.push({ ...newItem.value, id: Date.now() });
    newItem.value = { description: '', quantity: 1, unit_price: 0 };
};

// Auto-fill price when matches seen in datalist or typed
watch(() => newItem.value.description, (newVal) => {
    const service = props.services.find(s => s.name.toLowerCase() === newVal.toLowerCase());
    if (service) {
        newItem.value.unit_price = Number(service.price);
    }
});

const removeFromCart = (id) => {
    cartItems.value = cartItems.value.filter(item => item.id !== id);
};

const subtotal = computed(() => 
    cartItems.value.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0)
);

const total = computed(() => Math.max(0, subtotal.value - discount.value));

const handleCreateInvoice = () => {
    if (!selectedPatient.value || cartItems.value.length === 0) {
        Swal.fire({ icon: 'warning', title: 'Missing Info', text: 'Please select a patient and add at least one item.' });
        return;
    }

    router.post('/billing', {
        patient_id: selectedPatient.value,
        total_amount: total.value,
        items: cartItems.value,
        payment_method: paymentMethod.value,
        discount: discount.value,
        status: paymentMethod.value === 'cash' ? 'paid' : 'unpaid',
    }, {
        preserveScroll: true,
        onSuccess: () => {
            cartItems.value = [];
            selectedPatient.value = '';
            discount.value = 0;
        }
    });
};
</script>

<template>
    <div class="flex flex-col lg:flex-row gap-6">
        <!-- POS Left: Item Entry -->
        <div class="flex-1 space-y-6">
            <!-- Patient Selection -->
            <div class="bg-base-100/95 backdrop-blur-md rounded-2xl border border-base-300 p-5 shadow-sm">
                <h3 class="text-[10px] font-black text-base-content/30 uppercase tracking-[0.2em] mb-3">Select Patient</h3>
                <select v-model="selectedPatient" class="select select-bordered w-full rounded-xl bg-base-200/50 text-sm focus:border-primary transition-all">
                    <option value="" disabled>Choose a patient...</option>
                    <option v-for="patient in patients" :key="patient.id" :value="patient.id">
                        {{ patient.first_name }} {{ patient.last_name }}
                    </option>
                </select>
            </div>

            <!-- Add Line Item -->
            <div class="bg-base-100/95 backdrop-blur-md rounded-2xl border border-base-300 p-5 space-y-4 shadow-sm">
                <h3 class="text-[10px] font-black text-base-content/30 uppercase tracking-[0.2em] mb-1">Add Item / Service</h3>
                <div class="flex flex-col md:flex-row gap-3">
                    <div class="flex-[3] relative">
                        <input v-model="newItem.description" type="text" placeholder="Description (e.g., Cleaning)" 
                            list="services-list"
                            class="input input-bordered w-full rounded-xl bg-base-200/50 text-sm focus:border-primary transition-all" />
                        <datalist id="services-list" class="bg-base-100">
                            <option v-for="service in services" :key="service.id" :value="service.name">
                                ₱{{ Number(service.price).toLocaleString() }}
                            </option>
                        </datalist>
                    </div>
                    <div class="flex flex-1 gap-3">
                        <input v-model.number="newItem.quantity" type="number" min="1" placeholder="Qty" 
                            class="input input-bordered w-full md:w-20 rounded-xl bg-base-200/50 text-sm text-center focus:border-primary transition-all" />
                        <input v-model.number="newItem.unit_price" type="number" min="0" step="0.01" placeholder="Price" 
                            class="input input-bordered w-full md:w-28 rounded-xl bg-base-200/50 text-sm focus:border-primary transition-all" />
                        <button 
                            @click="addToCart"
                            class="btn rounded-xl text-white text-xs font-black uppercase tracking-widest px-6"
                            :style="{ backgroundColor: primaryColor }"
                        >
                            + Add
                        </button>
                    </div>
                </div>
            </div>

            <!-- Cart Items -->
            <div class="bg-base-100/95 backdrop-blur-md rounded-2xl border border-base-300 shadow-md overflow-hidden">
                <table class="table w-full">
                    <thead>
                        <tr class="bg-base-200/50">
                            <th class="text-[10px] font-black uppercase tracking-widest text-base-content/30">Description</th>
                            <th class="text-[10px] font-black uppercase tracking-widest text-base-content/30 text-center">Qty</th>
                            <th class="text-[10px] font-black uppercase tracking-widest text-base-content/30 text-right">Price</th>
                            <th class="text-[10px] font-black uppercase tracking-widest text-base-content/30 text-right">Total</th>
                            <th class="w-10"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in cartItems" :key="item.id" class="hover">
                            <td class="text-sm font-medium text-base-content">{{ item.description }}</td>
                            <td class="text-sm text-center text-base-content/60">{{ item.quantity }}</td>
                            <td class="text-sm text-right text-base-content/60">₱{{ Number(item.unit_price).toFixed(2) }}</td>
                            <td class="text-sm text-right font-bold text-base-content">₱{{ (item.quantity * item.unit_price).toFixed(2) }}</td>
                            <td>
                                <button @click="removeFromCart(item.id)" class="btn btn-ghost btn-xs btn-circle text-error">✕</button>
                            </td>
                        </tr>
                        <tr v-if="cartItems.length === 0">
                            <td colspan="5" class="text-center py-12">
                                <p class="text-xs text-base-content/20 font-bold uppercase tracking-widest">No items added yet</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- POS Right: Summary -->
        <div class="w-full lg:w-80 space-y-4">
            <div class="bg-base-100 rounded-2xl border border-base-300 p-6 space-y-5 sticky top-4">
                <h3 class="text-[10px] font-black text-base-content/30 uppercase tracking-[0.2em]">Order Summary</h3>
                
                <div class="space-y-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-base-content/50">Subtotal</span>
                        <span class="font-bold text-base-content">₱{{ subtotal.toFixed(2) }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm text-base-content/50">Discount</span>
                        <input v-model.number="discount" type="number" min="0" class="input input-bordered input-sm w-28 rounded-lg text-right bg-base-200/50" />
                    </div>
                    <div class="border-t border-base-300 pt-3 flex justify-between">
                        <span class="text-sm font-black text-base-content uppercase tracking-wider">Total</span>
                        <span class="text-xl font-black" :style="{ color: primaryColor }">₱{{ total.toFixed(2) }}</span>
                    </div>
                </div>

                <!-- Payment Method -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="text-[10px] font-black text-base-content/30 uppercase tracking-[0.2em]">Payment Method</h4>
                        <span v-if="paymentMethod !== 'cash'" class="text-[9px] font-black text-error uppercase tracking-widest animate-pulse">Unavailable Yet</span>
                    </div>
                    <div class="flex gap-2">
                        <button v-for="method in ['cash', 'card', 'gcash']" :key="method"
                            @click="paymentMethod = method"
                            :class="['flex-1 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all',
                                paymentMethod === method ? 'text-white shadow-md' : 'bg-base-200 text-base-content/40']"
                            :style="paymentMethod === method ? { backgroundColor: primaryColor } : {}"
                        >
                            {{ method }}
                        </button>
                    </div>
                </div>

                <!-- Submit -->
                <button 
                    @click="handleCreateInvoice"
                    :disabled="cartItems.length === 0 || !selectedPatient || paymentMethod !== 'cash'"
                    class="w-full py-3.5 rounded-xl text-white text-xs font-black uppercase tracking-widest shadow-lg hover:shadow-xl hover:scale-[1.01] transition-all duration-300 disabled:opacity-30 disabled:cursor-not-allowed disabled:grayscale"
                    :style="{ backgroundColor: primaryColor }"
                >
                    Create Invoice & Pay
                </button>
            </div>
        </div>
    </div>
</template>
