<script setup lang="ts">
import AdminGovernanceTab from './governance/AdminGovernanceTab.vue';

const emit = defineEmits<{
    (e: 'showToast', message: string): void;
}>();
</script>

<template>
    <AdminGovernanceTab @showToast="(msg: string) => emit('showToast', msg)" />
</template>
