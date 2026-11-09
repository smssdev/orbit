<script setup lang="ts">
import { Languages } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { useI18n } from '@/composables/useI18n';

withDefaults(defineProps<{ compact?: boolean }>(), { compact: false });

const { t, locale, setLocale } = useI18n();

// With two languages a single button that offers the other one is quicker than a menu.
const other = computed(
    () =>
        locale.value.available.find((l) => l.code !== locale.value.current) ??
        locale.value.available[0],
);
</script>

<template>
    <Button
        variant="ghost"
        :size="compact ? 'icon' : 'sm'"
        :lang="other.code"
        :title="t('Switch language')"
        :aria-label="t('Switch language')"
        @click="setLocale(other.code)"
    >
        <Languages class="size-4" aria-hidden="true" />
        <span v-if="!compact">{{ other.name }}</span>
    </Button>
</template>
