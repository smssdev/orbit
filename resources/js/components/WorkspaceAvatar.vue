<script setup lang="ts">
import { computed } from 'vue';
import { getInitials } from '@/composables/useInitials';
import { cn } from '@/lib/utils';

const props = defineProps<{
    name: string;
    class?: string;
}>();

const initials = computed(() => getInitials(props.name).slice(0, 2));
</script>

<template>
    <div
        :class="
            cn(
                'flex aspect-square size-8 shrink-0 items-center justify-center rounded-lg bg-sidebar-primary text-xs font-semibold text-sidebar-primary-foreground',
                props.class,
            )
        "
        aria-hidden="true"
    >
        {{ initials }}
    </div>
</template>
