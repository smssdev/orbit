<script setup lang="ts">
import BrandMark from '@/components/BrandMark.vue';
</script>

<template>
    <!-- Mark + "orbit" wordmark. The wordmark stays Latin and left to right in both languages. -->
    <span class="inline-flex items-center gap-2" dir="ltr">
        <BrandMark class="size-8" />
        <span class="text-xl font-semibold tracking-tight">orbit</span>
    </span>
</template>
