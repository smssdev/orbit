<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { toUrl } from '@/lib/utils';
import { edit as workspaceSettings } from '@/routes/workspace';
import { index as workspaceMembers } from '@/routes/workspace/members';
import type { NavItem } from '@/types';
import { useI18n } from '@/composables/useI18n';

const { t } = useI18n();

const page = usePage();
const workspaceName = computed(() => page.props.currentWorkspace?.name ?? '');

const navItems: NavItem[] = [
    { title: 'General', href: workspaceSettings() },
    { title: 'Members', href: workspaceMembers() },
];

const { isCurrentOrParentUrl } = useCurrentUrl();
</script>

<template>
    <div class="px-4 py-6">
        <Heading
            :title="workspaceName"
            description="Manage this workspace and the people in it"
        />

        <div class="flex flex-col lg:flex-row lg:gap-12">
            <aside class="w-full max-w-xl lg:w-48">
                <nav
                    class="flex flex-col space-y-1"
                    :aria-label="t('Workspace')"
                >
                    <Button
                        v-for="item in navItems"
                        :key="toUrl(item.href)"
                        variant="ghost"
                        :class="[
                            'w-full justify-start',
                            { 'bg-muted': isCurrentOrParentUrl(item.href) },
                        ]"
                        as-child
                    >
                        <Link :href="item.href">{{ t(item.title) }}</Link>
                    </Button>
                </nav>
            </aside>

            <Separator class="my-6 lg:hidden" />

            <div class="min-w-0 flex-1 md:max-w-3xl">
                <section class="space-y-12">
                    <slot />
                </section>
            </div>
        </div>
    </div>
</template>
