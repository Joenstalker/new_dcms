<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import ThemeSwitcher from '@/Components/ThemeSwitcher.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center bg-base-200 pt-6 sm:justify-center sm:pt-0 relative"
    >
        <!-- Theme switcher in top-right corner -->
        <div class="absolute top-4 right-4">
            <ThemeSwitcher />
        </div>

        <div>
            <Link href="/">
                <ApplicationLogo class="h-20 w-20 fill-current text-base-content/50" />
            </Link>
        </div>

        <div
            class="mt-6 w-full overflow-hidden bg-base-100 px-6 py-4 shadow-md sm:max-w-md sm:rounded-lg"
        >
            <slot />
        </div>
    </div>
</template>

