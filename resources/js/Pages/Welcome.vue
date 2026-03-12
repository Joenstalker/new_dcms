<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Checkbox from '@/Components/Checkbox.vue';

import logoImage from '../../../public/images/dcms-logo.png';
import heroImage from '../../../public/images/OneTop.png';

const props = defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
    plans: {
        type: Array,
        default: () => [],
    }
});

const isLoginModalOpen = ref(false);

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submitLogin = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

const openLoginModal = () => {
    isLoginModalOpen.value = true;
};

const closeLoginModal = () => {
    isLoginModalOpen.value = false;
    form.reset();
    form.clearErrors();
};

const features = [
    {
        title: 'Patient Records',
        description: 'Access your patient records anywhere, anytime!',
        icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />`
    },
    {
        title: 'Appointments',
        description: 'Never miss an appointment and easily manage your clinic schedule.',
        icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />`
    },
    {
        title: 'Financial Reports',
        description: 'Know where your clinic profit goes! Track your clinic income and expenses with our financial reports.',
        icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />`
    },
    {
        title: 'Dashboard',
        description: 'Get an overview of your clinic\'s performance without a hassle.',
        icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />`
    },
    {
        title: 'Multi-Role Staff',
        description: 'Dedicated access for Dentists and Assistants to streamline your workflow.',
        icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />`
    },
    {
        title: 'QR Code Booking',
        description: 'Provide a contactless online booking experience for your patients.',
        icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 3.75 9.375v-4.5ZM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5ZM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 13.5 9.375v-4.5Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 6.75h.75v.75h-.75v-.75ZM6.75 16.5h.75v.75h-.75v-.75ZM16.5 6.75h.75v.75h-.75v-.75ZM13.5 13.5h.75v.75h-.75v-.75ZM13.5 19.5h.75v.75h-.75v-.75ZM19.5 13.5h.75v.75h-.75v-.75ZM19.5 19.5h.75v.75h-.75v-.75ZM16.5 16.5h.75v.75h-.75v-.75Z" />`
    }
];

const formatFeatures = (plan) => {
    const featureList = [
        `${plan.max_users} Users (Staff Access)`,
        plan.max_patients ? `${plan.max_patients.toLocaleString()} Patients` : 'Unlimited Patients',
        plan.max_appointments ? `${plan.max_appointments.toLocaleString()} Appointments` : 'Unlimited Appointments',
    ];

    if (plan.has_qr_booking) featureList.push('QR Online Booking');
    featureList.push('Scheduling & Calendar', 'Patient Records', 'Billing & POS');
    
    // Reports
    if (plan.report_level === 'basic') featureList.push('Basic Reports');
    else if (plan.report_level === 'enhanced') featureList.push('Enhanced Reports & Summaries');
    else if (plan.report_level === 'advanced') featureList.push('Advanced Analytics & Reports');

    if (plan.has_sms) featureList.push('SMS Notifications');
    if (plan.has_branding) featureList.push('Custom Clinic Branding');
    if (plan.has_priority_support) featureList.push('Priority Support');
    if (plan.has_multi_branch) featureList.push('Multi-branch Readiness');
    
    return featureList;
};

const isPopular = (planName) => {
    return planName.toLowerCase().includes('pro') || planName.toLowerCase().includes('popular');
};
</script>

<template>
    <Head title="Dental Clinic Management System" />

    <div class="min-h-screen bg-gray-50 font-sans selection:bg-teal-500 selection:text-white pb-20">
        
        <!-- Navbar -->
        <nav class="bg-[#2E4A62] text-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center">
                        <img :src="logoImage" alt="DCMS Logo" class="h-8 w-8 rounded mr-3">
                        <span class="font-bold text-xl tracking-wider">DCMS</span>
                    </div>
                    <div class="hidden md:flex space-x-8 items-center text-sm font-medium">
                        <a href="#home" class="hover:text-teal-300 transition-colors">Home</a>
                        <a href="#features" class="hover:text-teal-300 transition-colors">Features</a>
                        <a href="#pricing" class="hover:text-teal-300 transition-colors">Pricing</a>
                        
                        <Link v-if="$page.props.auth.user" :href="route('admin.dashboard')" class="bg-teal-500 hover:bg-teal-400 text-white px-5 py-2 rounded-md transition-colors font-semibold shadow-sm">
                            Dashboard
                        </Link>
                        <button v-else @click="openLoginModal" class="bg-teal-500 hover:bg-teal-400 text-white px-5 py-2 rounded-md transition-colors font-semibold shadow-sm">
                            LOGIN
                        </button>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <main id="home" class="relative overflow-hidden bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-20 pb-40 lg:pt-32 lg:pb-48 text-center lg:text-left flex flex-col lg:flex-row items-center">
                <!-- Text Content -->
                <div class="lg:w-1/2 lg:pr-12 z-10">
                    <h1 class="text-4xl sm:text-5xl font-extrabold text-gray-900 leading-tight tracking-tight">
                        Managing your clinic doesn't <br class="hidden sm:block"> 
                        have to be stressful!
                    </h1>
                    <p class="mt-6 text-lg text-gray-600 font-medium">
                        Dental Practice Management App <br>
                        Made by Filipino Dentist, for every Filipino Dentist.
                    </p>
                    <div class="mt-10">
                        <button @click="openLoginModal" class="bg-[#FF6B53] hover:bg-[#ff563b] text-white text-lg font-bold px-8 py-3.5 rounded-md shadow-lg shadow-[#FF6B53]/30 transition-all hover:-translate-y-1">
                            GET STARTED
                        </button>
                        <p class="mt-3 text-xs text-gray-400 italic">The app is now live!</p>
                    </div>
                </div>

                <!-- Hero Image -->
                <div class="lg:w-1/2 mt-16 lg:mt-0 relative z-10">
                    <img :src="heroImage" alt="DCMS App Preview on Desktop and Mobile" class="w-full max-w-2xl mx-auto drop-shadow-2xl object-contain h-auto max-h-[450px]" onerror="this.src='https://placehold.co/800x600/e2e8f0/475569?text=App+Preview'">
                </div>
            </div>
            
            <!-- Curved Wave Graphic -->
            <div class="absolute bottom-0 w-full leading-none z-0">
                <svg viewBox="0 0 1440 250" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-auto text-[#42A1C9] block">
                    <path d="M0 120L48 135C96 150 192 180 288 185C384 190 480 170 576 135C672 100 768 50 864 35C960 20 1056 40 1152 65C1248 90 1344 120 1392 135L1440 150V250H1392C1344 250 1248 250 1152 250C1056 250 960 250 864 250C768 250 672 250 576 250C480 250 384 250 288 250C192 250 96 250 48 250H0V120Z" fill="currentColor"/>
                </svg>
            </div>
        </main>

        <!-- Features Section (Teal/Blue Background) -->
        <section id="features" class="bg-[#59A5D8] py-20 text-white pb-32">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <p class="text-sm font-semibold tracking-wider uppercase text-blue-100 mb-2">Why Choose DCMS?</p>
                    <h2 class="text-3xl font-bold">Take control of your dental clinic<br>without being stressed out!</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-16 mt-16 px-4">
                    <div v-for="(feature, index) in features" :key="index" class="text-center">
                        <div class="w-20 h-20 mx-auto bg-[#00AEEF] rounded-full flex items-center justify-center mb-6 shadow-lg shadow-black/10 border-4 border-white/20">
                            <svg class="w-10 h-10 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" v-html="feature.icon"></svg>
                        </div>
                        <h3 class="text-xl font-bold mb-3">{{ feature.title }}</h3>
                        <p class="text-blue-50 text-sm leading-relaxed max-w-xs mx-auto text-balance">{{ feature.description }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Curved Wave Bottom -->
        <div class="w-full bg-gray-50 leading-none -mt-1 border-0">
             <svg viewBox="0 0 1440 120" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-auto text-[#59A5D8] block rotate-180 transform translate-y-[1px]">
                  <path d="M0 60L48 55C96 50 192 40 288 45C384 50 480 70 576 85C672 100 768 110 864 105C960 100 1056 80 1152 65C1248 50 1344 40 1392 35L1440 30V120H1392C1344 120 1248 120 1152 120C1056 120 960 120 864 120C768 120 672 120 576 120C480 120 384 120 288 120C192 120 96 120 48 120H0V60Z" fill="currentColor"/>
            </svg>
        </div>

        <!-- Pricing Section -->
        <section id="pricing" class="py-20 bg-gray-50 pt-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-3xl font-bold text-gray-900">Check our affordable plans!</h2>
                    <p class="mt-3 text-gray-500">Get started without breaking the bank!</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 items-start justify-center max-w-6xl mx-auto">
                    <div v-for="plan in plans" :key="plan.id" 
                        :class="['bg-white rounded-xl overflow-hidden shadow-xl shadow-gray-200/50 border-t-8 transition-transform hover:-translate-y-2', 
                        isPopular(plan.name) ? 'border-[#FF6B53]' : 'border-teal-400']"
                    >
                        <div class="p-8 text-center border-b border-gray-100">
                            <h3 :class="['text-xs font-bold tracking-widest uppercase mb-4', isPopular(plan.name) ? 'text-[#FF6B53]' : 'text-teal-500']">{{ plan.name }}</h3>
                            <div class="flex items-baseline justify-center text-gray-900">
                                <span class="text-4xl font-extrabold tracking-tight">₱{{ Number(plan.price_monthly).toLocaleString() }}</span>
                                <span class="text-sm font-semibold text-gray-500 ml-1">/mo</span>
                            </div>
                        </div>
                        <div class="p-8">
                            <ul class="space-y-4 text-sm text-gray-600 text-center font-medium">
                                <li v-for="feature in formatFeatures(plan)" :key="feature">{{ feature }}</li>
                                <li v-if="isPopular(plan.name)" class="font-bold text-[#FF6B53]">Custom System Features ⭐</li>
                            </ul>
                            <div class="mt-8">
                                <button @click="openLoginModal" :class="['w-full py-3 px-4 rounded-md font-bold transition-colors', isPopular(plan.name) ? 'bg-[#FF6B53] hover:bg-[#ff563b] text-white shadow-md shadow-[#FF6B53]/20' : 'bg-gray-100 text-gray-900 hover:bg-gray-200']">
                                    CHOOSE PLAN
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <p class="text-center mt-8 text-xs text-gray-400">
                    * Billed monthly or annually. <br>
                    ** Prices may change without prior notice.
                </p>
            </div>
        </section>

        <!-- Footer / Contact -->
        <footer class="bg-[#2E4A62] text-white py-16 mt-10">
            <div class="max-w-4xl mx-auto px-4 text-center">
                <h2 class="text-3xl font-bold mb-6">Contact Us</h2>
                <p class="mb-10 text-gray-300">We know that you have some questions in mind regarding how the app works.<br>Let us help you get through it by clicking the button below.</p>
                <a href="mailto:admin@dcms.com" class="inline-block bg-[#FF6B53] hover:bg-[#ff563b] text-white font-bold px-10 py-3 rounded-md transition-colors shadow-lg shadow-black/20">
                    CONTACT US
                </a>
            </div>
            <div class="mt-20 text-center text-xs text-gray-400 border-t border-gray-600/50 pt-8">
                Copyright © 2026 DCMS. All Rights Reserved.<br>
                <a href="#" class="hover:text-white transition-colors">Terms</a> | <a href="#" class="hover:text-white transition-colors">Privacy Policy</a>
            </div>
        </footer>

        <!-- Login Modal -->
        <Modal :show="isLoginModalOpen" @close="closeLoginModal" maxWidth="md">
            <div class="p-8">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-900">Admin Login</h2>
                    <button @click="closeLoginModal" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div v-if="$page.props.flash?.error" class="mb-4 text-sm font-medium text-red-600 bg-red-50 p-3 rounded-md">
                    {{ $page.props.flash.error }}
                </div>

                <form @submit.prevent="submitLogin" class="space-y-5">
                    <div>
                        <InputLabel for="email" value="Email Address" />
                        <TextInput
                            id="email"
                            type="email"
                            class="mt-1 block w-full py-2.5"
                            v-model="form.email"
                            required
                            autofocus
                            autocomplete="username"
                        />
                        <InputError class="mt-1.5" :message="form.errors.email" />
                    </div>

                    <div>
                        <InputLabel for="password" value="Password" />
                        <TextInput
                            id="password"
                            type="password"
                            class="mt-1 block w-full py-2.5"
                            v-model="form.password"
                            required
                            autocomplete="current-password"
                        />
                        <InputError class="mt-1.5" :message="form.errors.password" />
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center">
                            <Checkbox name="remember" v-model:checked="form.remember" />
                            <span class="ms-2 text-sm text-gray-600">Remember me</span>
                        </label>
                        
                        <Link
                            v-if="canResetPassword"
                            :href="route('password.request')"
                            class="text-sm font-semibold text-teal-600 hover:text-teal-500"
                        >
                            Forgot password?
                        </Link>
                    </div>

                    <div class="pt-2">
                        <PrimaryButton
                            class="w-full justify-center py-3 text-sm font-bold bg-[#FF6B53] hover:bg-[#ff563b] focus:bg-[#e04f38] active:bg-[#e04f38]"
                            :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                            :disabled="form.processing"
                        >
                            Log into Dashboard
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

    </div>
</template>
