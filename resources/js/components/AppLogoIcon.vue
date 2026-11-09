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
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 40 40"
        :class="className"
        v-bind="$attrs"
    >
        <circle cx="20" cy="20" r="7.5" fill="currentColor" />
        <ellipse
            cx="20"
            cy="20"
            rx="17"
            ry="7"
            transform="rotate(-25 20 20)"
            fill="none"
            stroke="currentColor"
            stroke-width="2.5"
        />
    </svg>
</template>
