<script setup lang="ts">
// Lucide-style Cow/Cattle Icon (24x24, stroke-width 2, round joins/caps)
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        width="24"
        height="24"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
        v-bind="$attrs"
    >
        <!-- Horns -->
        <path d="M4 3c0 3.5 2.5 5 5 5" />
        <path d="M20 3c0 3.5-2.5 5-5 5" />
        <!-- Head Crown -->
        <path d="M9 8h6" />
        <!-- Ears -->
        <path d="M7 9c-2.5 0-4 1.5-4 3s2 1.5 4 0" />
        <path d="M17 9c2.5 0 4 1.5 4 3s-2 1.5-4 0" />
        <!-- Cheeks -->
        <path d="M7 11v3" />
        <path d="M17 11v3" />
        <!-- Snout / Muzzle -->
        <rect x="6" y="14" width="12" height="7" rx="3" />
        <!-- Eyes -->
        <path d="M9.5 11h.01" />
        <path d="M14.5 11h.01" />
        <!-- Nostrils -->
        <path d="M9.5 17.5h.01" />
        <path d="M14.5 17.5h.01" />
    </svg>
</template>
