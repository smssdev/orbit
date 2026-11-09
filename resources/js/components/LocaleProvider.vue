<script setup lang="ts">
import { ConfigProvider } from 'reka-ui';
import { watchEffect } from 'vue';
import { syncDocumentLocale, useI18n } from '@/composables/useI18n';

// Tells the reka-ui primitives (menus, dialogs, selects, the sidebar sheet) which way the page reads, and keeps
// <html lang dir> in step when the language changes without a full page load.
const { dir, locale } = useI18n();

watchEffect(() => syncDocumentLocale(locale.value));
</script>

<template>
    <ConfigProvider :dir="dir" :locale="locale.current">
        <slot />
    </ConfigProvider>
</template>
