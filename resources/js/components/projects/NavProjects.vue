<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { computed } from 'vue';
import {
    SidebarGroup,
    SidebarGroupAction,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { projectColors } from '@/lib/projects';
import { cn } from '@/lib/utils';
import { index as projectsIndex, show as showProject } from '@/routes/projects';
import { useI18n } from '@/composables/useI18n';

const { t } = useI18n();

// The current workspace's active projects, shared by the server on every page.
const page = usePage();
const projects = computed(() => page.props.projects ?? []);
const { isCurrentUrl } = useCurrentUrl();
</script>

<template>
    <SidebarGroup
        v-if="page.props.currentWorkspace"
        class="px-2 py-0 group-data-[collapsible=icon]:hidden"
    >
        <SidebarGroupLabel>{{ t('Projects') }}</SidebarGroupLabel>
        <SidebarGroupAction as-child :title="t('New project')">
            <Link
                :href="projectsIndex({ query: { new: 1 } })"
                data-test="sidebar-new-project"
            >
                <Plus />
                <span class="sr-only">{{ t('New project') }}</span>
            </Link>
        </SidebarGroupAction>
        <SidebarMenu>
            <SidebarMenuItem v-for="project in projects" :key="project.id">
                <SidebarMenuButton
                    as-child
                    :is-active="isCurrentUrl(showProject(project.id))"
                    :tooltip="project.name"
                >
                    <Link
                        :href="showProject(project.id)"
                        :data-test="`sidebar-project-${project.id}`"
                    >
                        <span
                            :class="
                                cn(
                                    'size-2.5 shrink-0 rounded-full',
                                    projectColors[project.color].dot,
                                )
                            "
                            aria-hidden="true"
                        />
                        <span class="truncate">{{ project.name }}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
            <SidebarMenuItem v-if="!projects.length">
                <p class="px-2 py-1.5 text-xs text-muted-foreground">
                    {{ t('No projects yet') }}
                </p>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
