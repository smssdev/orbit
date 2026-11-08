<script setup lang="ts">
import { computed } from 'vue';
import { projectColors, projectIcons } from '@/lib/projects';
import { cn } from '@/lib/utils';
import type { ProjectColor, ProjectIcon } from '@/types';

const props = withDefaults(
    defineProps<{
        color: ProjectColor;
        icon: ProjectIcon;
        size?: 'sm' | 'md' | 'lg';
        class?: string;
    }>(),
    { size: 'md', class: '' },
);

const sizes = {
    sm: 'size-5 rounded [&_svg]:size-3',
    md: 'size-9 rounded-lg [&_svg]:size-4.5',
    lg: 'size-12 rounded-xl [&_svg]:size-6',
};

const Icon = computed(() => projectIcons[props.icon]);
</script>

<template>
    <span
        :class="
            cn(
                'inline-flex shrink-0 items-center justify-center',
                projectColors[props.color].tile,
                sizes[props.size],
                props.class,
            )
        "
        aria-hidden="true"
    >
        <component :is="Icon" />
    </span>
</template>
