<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
</script>

<template>
    <!-- The mark on its brand tile, as used for the app icon. Size it with a class, e.g. size-8. -->
    <span
        class="inline-flex shrink-0 items-center justify-center rounded-[28%] bg-linear-135 from-[oklch(0.58_0.2_285)] to-[oklch(0.46_0.22_270)] text-white shadow-sm ring-1 ring-black/5 ring-inset"
        aria-hidden="true"
    >
        <AppLogoIcon class="size-[62%]" />
    </span>
</template>
