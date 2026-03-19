<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import BookingModal from '@/Pages/Tenant/Booking/Partials/BookingModal.vue';

const props = defineProps({
    tenant: Object,
    services: Array,
    dentists: Array,
});

const showBookingModal = ref(false);

const form = useForm({
    name: '',
    email: '',
    phone: '',
    subject: '',
    message: '',
});

const submit = () => {
    form.post(route('tenant.concern.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};

const brandingColor = computed(() => props.tenant.branding_color || '#3b82f6'); // Default blue-500

const heroTitle = computed(() => props.tenant.hero_title || `Welcome to ${props.tenant.name}`);
const heroSubtitle = computed(() => props.tenant.hero_subtitle || 'Providing high-quality dental care with a gentle touch and modern technology.');
const aboutDescription = computed(() => props.tenant.about_us_description || `${props.tenant.name} is dedicated to providing the best dental care in the region. Our team of experienced professionals is here to ensure your smile remains healthy and beautiful.`);

const formattedAddress = computed(() => {
    const parts = [props.tenant.street, props.tenant.barangay, props.tenant.city, props.tenant.province];
    return parts.filter(p => p).join(', ');
});
</script>

<template>
    <Head :title="tenant.name" />

    <div class="min-h-screen bg-white font-sans text-gray-900">
        <!-- Navigation -->
        <nav class="sticky top-0 z-50 bg-white/80 backdrop-blur-md border-b border-gray-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-20 items-center">
                    <div class="flex items-center">
                        <div class="text-2xl font-black tracking-tight" :style="{ color: brandingColor }">
                            {{ tenant.name }}
                        </div>
                    </div>
                    <div class="hidden md:flex items-center space-x-8">
                        <a href="#services" class="text-sm font-medium hover:text-gray-600 transition-colors">Services</a>
                        <a href="#team" class="text-sm font-medium hover:text-gray-600 transition-colors">Our team</a>
                        <a href="#contact" class="text-sm font-medium hover:text-gray-600 transition-colors">Contact</a>
                        <Link :href="route('login')" class="text-sm font-medium text-gray-500 hover:text-gray-900">Staff Login</Link>
                        <button @click="showBookingModal = true" 
                           class="inline-flex items-center px-6 py-3 border border-transparent text-sm font-bold rounded-full shadow-sm text-white transition-all transform hover:scale-105 active:scale-95"
                           :style="{ backgroundColor: brandingColor }">
                            Book Appointment
                        </button>
                    </div>
                    <!-- Mobile Button (Simplified) -->
                    <div class="md:hidden">
                        <button @click="showBookingModal = true" 
                           class="inline-flex items-center px-4 py-2 border border-transparent text-xs font-bold rounded-full text-white"
                           :style="{ backgroundColor: brandingColor }">
                            Book Now
                        </button>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <header class="relative py-20 lg:py-32 overflow-hidden bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                <div class="lg:w-2/3">
                    <span class="inline-block px-4 py-1.5 mb-6 text-xs font-bold uppercase tracking-widest text-white rounded-full bg-blue-600/10" :style="{ color: brandingColor, backgroundColor: brandingColor + '15' }">
                        Expert Dental Care
                    </span>
                    <h1 class="text-5xl lg:text-7xl font-black text-gray-900 leading-tight mb-8">
                        {{ heroTitle }}
                    </h1>
                    <p class="text-xl text-gray-600 mb-10 leading-relaxed max-w-2xl">
                        {{ heroSubtitle }}
                    </p>
                    <div class="flex flex-col sm:flex-row items-center gap-6">
                        <button @click="showBookingModal = true" 
                           class="inline-flex justify-center items-center px-8 py-4 border border-transparent text-lg font-bold rounded-full shadow-xl text-white transition-all hover:shadow-2xl hover:scale-105 active:scale-95"
                           :style="{ backgroundColor: brandingColor }">
                            Schedule Your Visit
                        </button>
                        
                        <!-- QR Code Section -->
                        <div class="flex items-center gap-4 p-3 bg-white rounded-3xl shadow-sm border border-gray-100">
                            <div class="w-16 h-16 bg-gray-100 rounded-xl flex items-center justify-center overflow-hidden">
                                <img v-if="tenant.qr_code_path" :src="'/storage/' + tenant.qr_code_path" class="w-full h-full object-cover">
                                <div v-else class="text-2xl opacity-20">QR</div>
                            </div>
                            <div class="text-left">
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none mb-1">Mobile Booking</p>
                                <p class="text-xs font-bold text-gray-700">Scan to Book</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Decorative Elements -->
            <div class="absolute top-0 right-0 w-1/3 h-full bg-blue-50/50 hidden lg:block -skew-x-12 transform translate-x-20"></div>
        </header>

        <!-- Services Section -->
        <section id="services" class="py-24 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-3xl lg:text-4xl font-black text-gray-900 mb-4">Our Specialized Services</h2>
                    <p class="text-gray-500 max-w-2xl mx-auto">We offer a wide range of dental treatments to keep your clinic healthy and your smile glowing.</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div v-for="service in services" :key="service.id" class="p-8 bg-gray-50 rounded-3xl border border-transparent hover:border-blue-100 transition-all group">
                        <div class="w-14 h-14 rounded-2xl flex items-center justify-center mb-6 text-2xl group-hover:scale-110 transition-transform" :style="{ backgroundColor: brandingColor + '15', color: brandingColor }">
                            🦷
                        </div>
                        <h3 class="text-xl font-bold mb-3">{{ service.name }}</h3>
                        <p class="text-gray-600 text-sm leading-relaxed mb-6">
                            {{ service.description || 'Professional dental care tailored to your needs.' }}
                        </p>
                        <p class="text-lg font-black" :style="{ color: brandingColor }">₱ {{ service.price }}</p>
                    </div>
                    <!-- Empty State for Services -->
                    <div v-if="services.length === 0" class="col-span-full text-center py-10 opacity-50 italic">
                        Check back soon for our list of services.
                    </div>
                </div>
            </div>
        </section>

        <!-- About Us -->
        <section class="py-24 bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col lg:flex-row items-center gap-16">
                <div class="lg:w-1/2">
                    <div class="relative">
                        <div class="w-full aspect-square bg-blue-100 rounded-[3rem] overflow-hidden">
                            <!-- Image would go here -->
                            <div class="w-full h-full flex items-center justify-center text-8xl opacity-20">🏥</div>
                        </div>
                        <div class="absolute -bottom-8 -right-8 w-48 h-48 bg-white p-6 rounded-3xl shadow-xl flex flex-col justify-center text-center">
                            <span class="text-4xl font-black block" :style="{ color: brandingColor }">10+</span>
                            <span class="text-xs font-bold text-gray-500 uppercase tracking-widest">Years Experience</span>
                        </div>
                    </div>
                </div>
                <div class="lg:w-1/2">
                    <h2 class="text-3xl lg:text-4xl font-black mb-8">Committed to Excellence in Dental Care</h2>
                    <p class="text-gray-600 text-lg leading-relaxed mb-8">
                        {{ aboutDescription }}
                    </p>
                    <ul class="space-y-4 mb-10">
                        <li class="flex items-center gap-3">
                            <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs text-white" :style="{ backgroundColor: brandingColor }">✓</span>
                            <span class="font-medium">Modern Technology</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs text-white" :style="{ backgroundColor: brandingColor }">✓</span>
                            <span class="font-medium">Sterilized Environment</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs text-white" :style="{ backgroundColor: brandingColor }">✓</span>
                            <span class="font-medium">Compassionate Experts</span>
                        </li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- Team Section -->
        <section id="team" class="py-24 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-3xl lg:text-4xl font-black mb-4">Meet Our Specialist Team</h2>
                    <p class="text-gray-500 max-w-2xl mx-auto">Expert dentists dedicated to provide world-class dental treatments with care.</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                    <div v-for="dentist in dentists" :key="dentist.id" class="text-center group">
                        <div class="w-full aspect-[4/5] bg-gray-100 rounded-3xl mb-6 overflow-hidden relative">
                             <div class="w-full h-full flex items-center justify-center text-7xl opacity-20 group-hover:scale-110 transition-transform">👨‍⚕️</div>
                        </div>
                        <h3 class="text-xl font-bold">{{ dentist.name }}</h3>
                        <p class="text-gray-500 text-sm">Professional Dentist</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Contact Us Section -->
        <section id="contact-form" class="py-24 bg-white border-t border-gray-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col lg:flex-row gap-16">
                    <div class="lg:w-1/2">
                        <span class="text-xs font-bold uppercase tracking-widest" :style="{ color: brandingColor }">Get in touch</span>
                        <h2 class="text-3xl lg:text-4xl font-black mb-6 mt-2">Have a Concern? We're Here to Help.</h2>
                        <p class="text-gray-600 text-lg mb-10 leading-relaxed">
                            Whether you're looking for an appointment or have a general inquiry, feel free to send us a message. Our team will respond as quickly as possible.
                        </p>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-8">
                            <div class="flex items-start gap-4">
                                <span class="text-2xl">📞</span>
                                <div>
                                    <p class="font-bold">Call Us</p>
                                    <p class="text-gray-500 text-sm">{{ tenant.phone || 'Phone not provided' }}</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-4">
                                <span class="text-2xl">✉️</span>
                                <div>
                                    <p class="font-bold">Email Us</p>
                                    <p class="text-gray-500 text-sm">{{ tenant.email || 'Email not provided' }}</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-4">
                                <span class="text-2xl">📍</span>
                                <div>
                                    <p class="font-bold">Visit Us</p>
                                    <p class="text-gray-500 text-sm">{{ formattedAddress }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="lg:w-1/2 bg-gray-50 p-8 lg:p-12 rounded-[2rem] shadow-sm relative overflow-hidden">
                        <!-- Flash Message -->
                        <div v-if="$page.props.flash && $page.props.flash.success" class="mb-6 p-4 bg-green-100 border border-green-200 text-green-700 rounded-xl flex items-center gap-3">
                            <span>✅</span>
                            <span>{{ $page.props.flash.success }}</span>
                        </div>

                        <form @submit.prevent="submit" class="space-y-6 relative z-10">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-1">Your Name</label>
                                    <input type="text" v-model="form.name" required class="w-full bg-white border-none rounded-xl shadow-sm focus:ring-2 p-4" :style="{ '--tw-ring-color': brandingColor }" placeholder="John Doe">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-1">Email Address</label>
                                    <input type="email" v-model="form.email" required class="w-full bg-white border-none rounded-xl shadow-sm focus:ring-2 p-4" :style="{ '--tw-ring-color': brandingColor }" placeholder="john@example.com">
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-1">Phone Number</label>
                                    <input type="text" v-model="form.phone" class="w-full bg-white border-none rounded-xl shadow-sm focus:ring-2 p-4" :style="{ '--tw-ring-color': brandingColor }" placeholder="Optional">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-1">Subject</label>
                                    <input type="text" v-model="form.subject" class="w-full bg-white border-none rounded-xl shadow-sm focus:ring-2 p-4" :style="{ '--tw-ring-color': brandingColor }" placeholder="How can we help?">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Message</label>
                                <textarea v-model="form.message" required rows="4" class="w-full bg-white border-none rounded-xl shadow-sm focus:ring-2 p-4" :style="{ '--tw-ring-color': brandingColor }" placeholder="Type your message here..."></textarea>
                            </div>
                            <button type="submit" :disabled="form.processing" class="w-full py-4 text-white font-black text-lg rounded-xl shadow-lg hover:shadow-xl transition-all disabled:opacity-50" :style="{ backgroundColor: brandingColor }">
                                {{ form.processing ? 'Sending...' : 'Send Message' }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer / Contact -->
        <footer id="contact" class="bg-gray-900 text-white py-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 mb-16">
                    <div class="lg:col-span-2">
                        <h2 class="text-3xl font-black mb-6">{{ tenant.name }}</h2>
                        <p class="text-gray-400 max-w-sm mb-8">
                            Your health and smile are our top priorities. Join our community of happy patients today.
                        </p>
                    </div>
                    <div>
                        <h4 class="text-lg font-bold mb-6">Contact Us</h4>
                        <ul class="space-y-4 text-gray-400">
                            <li class="flex items-start gap-3">
                                <span class="text-lg">📍</span>
                                <span>{{ formattedAddress }}</span>
                            </li>
                            <li class="flex items-center gap-3">
                                <span class="text-lg">📞</span>
                                <span>{{ tenant.phone || '(Contact Not Provided)' }}</span>
                            </li>
                            <li class="flex items-center gap-3">
                                <span class="text-lg">✉️</span>
                                <span>{{ tenant.email || '(Email Not Provided)' }}</span>
                            </li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-lg font-bold mb-6">Quick Links</h4>
                        <ul class="space-y-4 text-gray-400">
                            <li><button @click="showBookingModal = true" class="hover:text-white transition-colors">Book Now</button></li>
                           <li><Link :href="route('login')" class="hover:text-white transition-colors">Staff Login</Link></li>
                        </ul>
                    </div>
                </div>
                <div class="border-t border-gray-800 pt-8 flex flex-col md:flex-row justify-between items-center text-sm text-gray-500">
                    <p>&copy; 2026 {{ tenant.name }}. All rights reserved.</p>
                </div>
            </div>
        </footer>

        <!-- Booking Modal -->
        <BookingModal 
            :show="showBookingModal" 
            :tenant="tenant" 
            :services="services" 
            :dentists="dentists"
            @close="showBookingModal = false" 
        />
    </div>
</template>

<style scoped>
html {
    scroll-behavior: smooth;
}
</style>
