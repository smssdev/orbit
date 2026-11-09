<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { LogOut, MailPlus, RotateCw, UserMinus, X } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import InvitationController from '@/actions/App/Http/Controllers/Workspaces/InvitationController';
import MemberController from '@/actions/App/Http/Controllers/Workspaces/MemberController';
import ConfirmAction from '@/components/ConfirmAction.vue';
import Heading from '@/components/Heading.vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import InviteMemberForm from '@/components/workspaces/InviteMemberForm.vue';
import RoleSelect from '@/components/workspaces/RoleSelect.vue';
import { useI18n } from '@/composables/useI18n';
import { getInitials } from '@/composables/useInitials';
import { fromNow, roleLabels } from '@/lib/workspaces';
import { index } from '@/routes/workspace/members';
import type {
    PendingInvitation,
    WorkspaceMember,
    WorkspaceRole,
} from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Members', href: index() }],
    },
});

const props = defineProps<{
    members: WorkspaceMember[];
    invitations: PendingInvitation[];
    canManageMembers: boolean;
    assignableRoles: WorkspaceRole[];
}>();

/**
 * Roles are shown optimistically: the select changes at once, and snaps back if the server refuses.
 */
const roles = ref<Record<number, WorkspaceRole>>({});

watch(
    () => props.members,
    (members) => {
        roles.value = Object.fromEntries(members.map((m) => [m.id, m.role]));
    },
    { immediate: true },
);

const canChangeRole = (member: WorkspaceMember): boolean =>
    member.can.updateRole && props.assignableRoles.length > 1;

function changeRole(member: WorkspaceMember, role: WorkspaceRole): void {
    const previous = member.role;
    roles.value[member.id] = role;

    router.patch(
        MemberController.update.url(member.id),
        { role },
        {
            preserveScroll: true,
            onError: () => (roles.value[member.id] = previous),
            onCancel: () => (roles.value[member.id] = previous),
        },
    );
}

const resending = ref<number | null>(null);

function resend(invitation: PendingInvitation): void {
    router.post(
        InvitationController.store.url(),
        { email: invitation.email, role: invitation.role },
        {
            preserveScroll: true,
            onStart: () => (resending.value = invitation.id),
            onFinish: () => (resending.value = null),
        },
    );
}

const { t, tc, locale } = useI18n();

const memberCount = computed(() =>
    tc(':count member|:count members', props.members.length),
);

const ago = (iso: string): string => fromNow(iso, locale.value.current);
</script>

<template>
    <Head :title="t('Members')" />

    <div v-if="canManageMembers" class="space-y-4">
        <Heading
            variant="small"
            title="Invite people"
            description="They'll get an email with a link to join this workspace"
        />
        <InviteMemberForm :roles="assignableRoles" />
    </div>

    <div class="space-y-4">
        <Heading variant="small" title="Members" :description="memberCount" />

        <ul class="divide-y rounded-lg border" data-test="member-list">
            <li
                v-for="member in members"
                :key="member.id"
                class="flex flex-wrap items-center gap-3 p-3 sm:flex-nowrap"
            >
                <Avatar class="size-9 rounded-lg">
                    <AvatarFallback class="rounded-lg text-xs">
                        {{ getInitials(member.name) }}
                    </AvatarFallback>
                </Avatar>

                <div class="min-w-0 flex-1">
                    <p class="flex items-center gap-2 text-sm font-medium">
                        <span class="truncate">{{ member.name }}</span>
                        <Badge v-if="member.isYou" variant="secondary">
                            {{ t('You') }}
                        </Badge>
                    </p>
                    <p class="truncate text-xs text-muted-foreground">
                        {{ member.email }}
                        <template v-if="member.joinedAt">
                            ·
                            {{
                                t('joined :time', {
                                    time: ago(member.joinedAt),
                                })
                            }}
                        </template>
                    </p>
                </div>

                <div class="ms-auto flex items-center gap-1">
                    <RoleSelect
                        v-if="canChangeRole(member)"
                        :model-value="roles[member.id] ?? member.role"
                        :roles="assignableRoles"
                        :label="t('Role for :name', { name: member.name })"
                        @update:model-value="changeRole(member, $event)"
                    />
                    <Badge
                        v-else
                        :variant="
                            member.role === 'owner' ? 'default' : 'outline'
                        "
                    >
                        {{ t(roleLabels[member.role]) }}
                    </Badge>

                    <ConfirmAction
                        v-if="member.can.remove && member.isYou"
                        :title="t('Leave this workspace?')"
                        :description="
                            t(
                                'You\'ll lose access to its projects until someone invites you again.',
                            )
                        "
                        :confirm-label="t('Leave workspace')"
                        :action="MemberController.destroy(member.id)"
                    >
                        <Button variant="ghost" size="sm">
                            <LogOut />
                            {{ t('Leave') }}
                        </Button>
                    </ConfirmAction>

                    <ConfirmAction
                        v-else-if="member.can.remove"
                        :title="t('Remove :name?', { name: member.name })"
                        :description="
                            t(
                                ':name will lose access to this workspace and its projects.',
                                { name: member.name },
                            )
                        "
                        :confirm-label="t('Remove member')"
                        :action="MemberController.destroy(member.id)"
                    >
                        <Button
                            variant="ghost"
                            size="icon"
                            :aria-label="
                                t('Remove :name', { name: member.name })
                            "
                        >
                            <UserMinus />
                        </Button>
                    </ConfirmAction>
                </div>
            </li>
        </ul>
    </div>

    <div v-if="canManageMembers" class="space-y-4">
        <Heading
            variant="small"
            title="Pending invitations"
            description="Invitations that haven't been accepted yet"
        />

        <div
            v-if="invitations.length === 0"
            class="flex flex-col items-center gap-2 rounded-lg border border-dashed p-8 text-center"
        >
            <MailPlus class="size-6 text-muted-foreground" />
            <p class="text-sm text-muted-foreground">
                {{ t('No pending invitations.') }}
            </p>
        </div>

        <ul
            v-else
            class="divide-y rounded-lg border"
            data-test="invitation-list"
        >
            <li
                v-for="invitation in invitations"
                :key="invitation.id"
                class="flex flex-wrap items-center gap-3 p-3 sm:flex-nowrap"
            >
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium">
                        {{ invitation.email }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        <template v-if="invitation.invitedBy">
                            {{
                                t('Invited by :name', {
                                    name: invitation.invitedBy,
                                })
                            }}
                            ·
                        </template>
                        <span
                            v-if="invitation.isExpired"
                            class="text-destructive"
                        >
                            {{
                                t('Expired :time', {
                                    time: ago(invitation.expiresAt),
                                })
                            }}
                        </span>
                        <template v-else>
                            {{
                                t('Expires :time', {
                                    time: ago(invitation.expiresAt),
                                })
                            }}
                        </template>
                    </p>
                </div>

                <div class="ms-auto flex items-center gap-1">
                    <Badge variant="outline">
                        {{ t(roleLabels[invitation.role]) }}
                    </Badge>
                    <Button
                        variant="ghost"
                        size="sm"
                        :disabled="resending === invitation.id"
                        @click="resend(invitation)"
                    >
                        <RotateCw
                            :class="{
                                'animate-spin': resending === invitation.id,
                            }"
                        />
                        {{ t('Resend') }}
                    </Button>
                    <ConfirmAction
                        :title="t('Revoke this invitation?')"
                        :description="
                            t('The link sent to :email will stop working.', {
                                email: invitation.email,
                            })
                        "
                        :confirm-label="t('Revoke')"
                        :action="InvitationController.destroy(invitation.id)"
                    >
                        <Button
                            variant="ghost"
                            size="icon"
                            :aria-label="
                                t('Revoke the invitation for :email', {
                                    email: invitation.email,
                                })
                            "
                        >
                            <X />
                        </Button>
                    </ConfirmAction>
                </div>
            </li>
        </ul>
    </div>
</template>
