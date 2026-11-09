<script setup lang="ts">
import BrandMark from '@/components/BrandMark.vue';
import { useI18n } from '@/composables/useI18n';

const { t } = useI18n();
</script>

<template>
    <BrandMark class="size-8" />
    <div class="ms-1 grid flex-1 text-start text-sm">
        <span class="truncate leading-tight font-semibold tracking-tight">
            orbit
        </span>
        <span class="truncate text-xs text-muted-foreground">
            {{ t('Plan together') }}
        </span>
    </div>
</template>
