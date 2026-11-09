<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <!--
        The Orbit mark: an open ring (the orbit), a satellite sitting in its gap, and a core.
        Single colour (currentColor), so it works on any background.
    -->
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 40 40"
        :class="className"
        v-bind="$attrs"
    >
        <path
            d="M32.56 16.64A13 13 0 1 1 23.37 7.44"
            fill="none"
            stroke="currentColor"
            stroke-width="5"
            stroke-linecap="round"
        />
        <circle cx="29.19" cy="10.81" r="4" fill="currentColor" />
        <circle cx="20" cy="20" r="4.5" fill="currentColor" />
    </svg>
</template>
