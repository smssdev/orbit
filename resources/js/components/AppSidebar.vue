<script setup lang="ts">
import { FolderKanban, LayoutGrid, Users } from '@lucide/vue';
import NavMain from '@/components/NavMain.vue';
import NavProjects from '@/components/projects/NavProjects.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
} from '@/components/ui/sidebar';
import WorkspaceSwitcher from '@/components/WorkspaceSwitcher.vue';
import { useI18n } from '@/composables/useI18n';
import { dashboard } from '@/routes';
import { index as projectsIndex } from '@/routes/projects';
import { index as workspaceMembers } from '@/routes/workspace/members';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Projects',
        href: projectsIndex(),
        icon: FolderKanban,
    },
    {
        title: 'Members',
        href: workspaceMembers(),
        icon: Users,
    },
];

// The sidebar sits on the reading side: left in English, right in Arabic.
const { dir } = useI18n();
</script>

<template>
    <Sidebar
        collapsible="icon"
        variant="inset"
        :side="dir === 'rtl' ? 'right' : 'left'"
    >
        <SidebarHeader>
            <WorkspaceSwitcher />
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
            <NavProjects />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
