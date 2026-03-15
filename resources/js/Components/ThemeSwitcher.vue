<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';

const themes = [
    { name: 'light', label: 'Light', colors: ['#570df8', '#f000b8', '#37cdbe', '#ffffff'] },
    { name: 'dark', label: 'Dark', colors: ['#661AE6', '#D926AA', '#1FB2A5', '#2A303C'] },
    { name: 'cupcake', label: 'Cupcake', colors: ['#65c3c8', '#ef9fbc', '#eeaf3a', '#faf7f5'] },
    { name: 'bumblebee', label: 'Bumblebee', colors: ['#e0a82e', '#f9d72f', '#181830', '#ffffff'] },
    { name: 'emerald', label: 'Emerald', colors: ['#66cc8a', '#377cfb', '#f68067', '#ffffff'] },
    { name: 'corporate', label: 'Corporate', colors: ['#4b6bfb', '#7b92b2', '#67cba0', '#ffffff'] },
    { name: 'synthwave', label: 'Synthwave', colors: ['#e779c1', '#58c7f3', '#f3cc30', '#1a103d'] },
    { name: 'retro', label: 'Retro', colors: ['#ef9995', '#a4cbb4', '#DC8850', '#e4d8b4'] },
    { name: 'cyberpunk', label: 'Cyberpunk', colors: ['#ff7598', '#75d1f0', '#c07eec', '#ffee00'] },
    { name: 'valentine', label: 'Valentine', colors: ['#e96d7b', '#a991f7', '#88dbdd', '#f0d6e8'] },
    { name: 'halloween', label: 'Halloween', colors: ['#f28c18', '#6d3a9c', '#51a800', '#212121'] },
    { name: 'garden', label: 'Garden', colors: ['#5c7f67', '#ecad29', '#f16533', '#e9e7e7'] },
    { name: 'forest', label: 'Forest', colors: ['#1eb854', '#1fd65f', '#d99330', '#171212'] },
    { name: 'aqua', label: 'Aqua', colors: ['#09ecf3', '#966fb3', '#ffe999', '#345da7'] },
    { name: 'dracula', label: 'Dracula', colors: ['#ff79c6', '#bd93f9', '#ffb86c', '#282a36'] },
    { name: 'night', label: 'Night', colors: ['#38bdf8', '#818cf8', '#f471b5', '#0f172a'] },
    { name: 'winter', label: 'Winter', colors: ['#047AFF', '#463AA2', '#C148AC', '#ffffff'] },
    { name: 'nord', label: 'Nord', colors: ['#5E81AC', '#81A1C1', '#BF616A', '#ECEFF4'] },
    { name: 'sunset', label: 'Sunset', colors: ['#ff865b', '#fd6f9c', '#b387fa', '#1a103d'] },
];

const currentTheme = ref('light');
const isOpen = ref(false);
const dropdownRef = ref(null);

const setTheme = (theme) => {
    currentTheme.value = theme;
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('dcms-theme', theme);
    isOpen.value = false;
};

const handleClickOutside = (e) => {
    if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
        isOpen.value = false;
    }
};

onMounted(() => {
    const saved = localStorage.getItem('dcms-theme');
    if (saved) {
        currentTheme.value = saved;
        document.documentElement.setAttribute('data-theme', saved);
    }
    document.addEventListener('click', handleClickOutside);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside);
});

const currentLabel = () => {
    return themes.find(t => t.name === currentTheme.value)?.label || 'Light';
};
</script>

<template>
    <div class="relative" ref="dropdownRef">
        <!-- Trigger Button -->
        <button
            @click="isOpen = !isOpen"
            class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm font-medium transition-all duration-200 border border-base-300 bg-base-100 text-base-content hover:bg-base-200"
            title="Change theme"
        >
            <!-- Paint palette icon -->
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.098 19.902a3.75 3.75 0 005.304 0l6.401-6.402M6.75 21A3.75 3.75 0 013 17.25V4.125C3 3.504 3.504 3 4.125 3h5.25c.621 0 1.125.504 1.125 1.125v4.072M6.75 21a3.75 3.75 0 003.75-3.75V8.197M6.75 21h13.125c.621 0 1.125-.504 1.125-1.125v-5.25c0-.621-.504-1.125-1.125-1.125h-4.072M10.5 8.197l2.88-2.88c.438-.439 1.15-.439 1.59 0l3.712 3.713c.44.44.44 1.152 0 1.59l-2.879 2.88M6.75 17.25h.008v.008H6.75v-.008z" />
            </svg>
            <span class="hidden sm:inline">{{ currentLabel() }}</span>
            <svg class="w-3 h-3 opacity-60" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
            </svg>
        </button>

        <!-- Dropdown -->
        <Transition
            enter-active-class="transition ease-out duration-150"
            enter-from-class="opacity-0 scale-95 -translate-y-1"
            enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100 scale-100 translate-y-0"
            leave-to-class="opacity-0 scale-95 -translate-y-1"
        >
            <div
                v-if="isOpen"
                class="absolute right-0 mt-2 w-56 max-h-80 overflow-y-auto rounded-xl shadow-xl border border-base-300 bg-base-100 z-[100] py-2 scrollbar-thin"
            >
                <div class="px-3 py-1.5 text-xs font-semibold text-base-content/50 uppercase tracking-wider">
                    Theme
                </div>
                <button
                    v-for="theme in themes"
                    :key="theme.name"
                    @click="setTheme(theme.name)"
                    class="w-full flex items-center gap-3 px-3 py-2 text-sm transition-colors duration-150 hover:bg-base-200"
                    :class="{ 'bg-base-200 font-semibold': currentTheme === theme.name }"
                >
                    <!-- Color swatch -->
                    <div class="flex gap-0.5 shrink-0">
                        <span
                            v-for="(color, i) in theme.colors"
                            :key="i"
                            class="w-2 h-4 first:rounded-l last:rounded-r"
                            :style="{ backgroundColor: color }"
                        ></span>
                    </div>
                    <!-- Label -->
                    <span class="text-base-content">{{ theme.label }}</span>
                    <!-- Check mark -->
                    <svg
                        v-if="currentTheme === theme.name"
                        class="w-4 h-4 ml-auto text-success shrink-0"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2.5"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                </button>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
.scrollbar-thin::-webkit-scrollbar {
    width: 4px;
}
.scrollbar-thin::-webkit-scrollbar-track {
    background: transparent;
}
.scrollbar-thin::-webkit-scrollbar-thumb {
    background: rgba(128, 128, 128, 0.3);
    border-radius: 10px;
}
.scrollbar-thin::-webkit-scrollbar-thumb:hover {
    background: rgba(128, 128, 128, 0.5);
}
</style>
