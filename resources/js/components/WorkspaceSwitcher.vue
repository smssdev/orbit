<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { Check, ChevronsUpDown, Plus, Settings, Users } from '@lucide/vue';
import { computed } from 'vue';
import CurrentWorkspaceController from '@/actions/App/Http/Controllers/Workspaces/CurrentWorkspaceController';
import AppLogo from '@/components/AppLogo.vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import WorkspaceAvatar from '@/components/WorkspaceAvatar.vue';
import { roleLabels } from '@/lib/workspaces';
import { dashboard } from '@/routes';
import { edit as workspaceSettings } from '@/routes/workspace';
import { index as workspaceMembers } from '@/routes/workspace/members';
import { create as createWorkspace } from '@/routes/workspaces';
import type { WorkspaceSummary } from '@/types';

const page = usePage();
const current = computed(() => page.props.currentWorkspace);
const workspaces = computed(() => page.props.workspaces);
const { isMobile } = useSidebar();

function switchTo(workspace: WorkspaceSummary): void {
    if (workspace.id === current.value?.id) {
        return;
    }

    router.put(CurrentWorkspaceController.update.url(), {
        workspace_id: workspace.id,
    });
}
</script>

<template>
    <SidebarMenu>
        <SidebarMenuItem>
            <!-- Before a first workspace exists there is nothing to switch, so keep the plain logo. -->
            <SidebarMenuButton v-if="!current" size="lg" as-child>
                <Link :href="dashboard()">
                    <AppLogo />
                </Link>
            </SidebarMenuButton>

            <DropdownMenu v-else>
                <DropdownMenuTrigger as-child>
                    <SidebarMenuButton
                        size="lg"
                        class="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
                        data-test="workspace-switcher"
                    >
                        <WorkspaceAvatar :name="current.name" />
                        <div
                            class="grid flex-1 text-left text-sm leading-tight"
                        >
                            <span class="truncate font-semibold">{{
                                current.name
                            }}</span>
                            <span
                                class="truncate text-xs text-muted-foreground"
                            >
                                {{ roleLabels[current.role] }}
                            </span>
                        </div>
                        <ChevronsUpDown class="ml-auto size-4 opacity-60" />
                    </SidebarMenuButton>
                </DropdownMenuTrigger>

                <DropdownMenuContent
                    class="w-(--reka-dropdown-menu-trigger-width) min-w-60 rounded-lg"
                    :side="isMobile ? 'bottom' : 'right'"
                    align="start"
                    :side-offset="4"
                >
                    <DropdownMenuLabel
                        class="text-xs font-normal text-muted-foreground"
                    >
                        Workspaces
                    </DropdownMenuLabel>
                    <DropdownMenuGroup>
                        <DropdownMenuItem
                            v-for="workspace in workspaces"
                            :key="workspace.id"
                            class="gap-2 p-2"
                            @select="switchTo(workspace)"
                        >
                            <WorkspaceAvatar
                                :name="workspace.name"
                                class="size-6 rounded-md text-[0.625rem]"
                            />
                            <span class="flex-1 truncate">{{
                                workspace.name
                            }}</span>
                            <Check
                                v-if="workspace.id === current.id"
                                class="size-4"
                                aria-label="Current workspace"
                            />
                        </DropdownMenuItem>
                    </DropdownMenuGroup>

                    <DropdownMenuSeparator />

                    <DropdownMenuGroup>
                        <DropdownMenuItem as-child>
                            <Link :href="workspaceMembers()" class="gap-2">
                                <Users class="size-4" />
                                Members
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem as-child>
                            <Link :href="workspaceSettings()" class="gap-2">
                                <Settings class="size-4" />
                                Workspace settings
                            </Link>
                        </DropdownMenuItem>
                    </DropdownMenuGroup>

                    <DropdownMenuSeparator />

                    <DropdownMenuItem as-child>
                        <Link :href="createWorkspace()" class="gap-2">
                            <Plus class="size-4" />
                            New workspace
                        </Link>
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </SidebarMenuItem>
    </SidebarMenu>
</template>
