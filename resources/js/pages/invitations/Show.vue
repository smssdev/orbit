<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import { CircleAlert } from '@lucide/vue';
import { computed } from 'vue';
import AcceptInvitationController from '@/actions/App/Http/Controllers/Workspaces/AcceptInvitationController';
import TextLink from '@/components/TextLink.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import WorkspaceAvatar from '@/components/WorkspaceAvatar.vue';
import { roleLabels } from '@/lib/workspaces';
import { dashboard, logout } from '@/routes';
import type { InvitationStatus, WorkspaceRole } from '@/types';
import { useI18n } from '@/composables/useI18n';

const { t } = useI18n();

defineOptions({
    layout: {
        title: 'Workspace invitation',
    },
});

const props = defineProps<{
    token: string;
    invitation: {
        workspace: string;
        invitedBy: string | null;
        email: string;
        role: WorkspaceRole;
    };
    status: InvitationStatus;
}>();

const page = usePage();
const signedInAs = computed(() => page.props.auth.user.email);
const inviter = computed(() => props.invitation.invitedBy ?? t('A teammate'));
</script>

<template>
    <Head :title="t('Join :workspace', { workspace: invitation.workspace })" />

    <div class="flex flex-col items-center gap-6 text-center">
        <WorkspaceAvatar :name="invitation.workspace" class="size-14 text-lg" />

        <p class="text-sm text-muted-foreground">
            {{
                t(':inviter invited you to join :workspace as :role.', {
                    inviter,
                    workspace: invitation.workspace,
                    role: t(roleLabels[invitation.role]),
                })
            }}
        </p>

        <Form
            v-if="status === 'valid'"
            v-bind="AcceptInvitationController.store.form(token)"
            class="flex w-full flex-col gap-3"
            v-slot="{ processing }"
        >
            <Button
                type="submit"
                class="w-full"
                :disabled="processing"
                data-test="accept-invitation-button"
            >
                <Spinner v-if="processing" />
                {{ t('Join :workspace', { workspace: invitation.workspace }) }}
            </Button>
            <TextLink :href="dashboard()" class="text-sm">{{
                t('Not now')
            }}</TextLink>
        </Form>

        <template v-else-if="status === 'expired'">
            <Alert variant="destructive" class="text-start">
                <CircleAlert />
                <AlertTitle>{{ t('This invitation has expired') }}</AlertTitle>
                <AlertDescription>
                    {{ t('Ask :inviter to send you a new one.', { inviter }) }}
                </AlertDescription>
            </Alert>
            <Button variant="secondary" class="w-full" as-child>
                <Link :href="dashboard()">{{ t('Go to dashboard') }}</Link>
            </Button>
        </template>

        <template v-else>
            <Alert class="text-start">
                <CircleAlert />
                <AlertTitle>{{
                    t('This invitation is for another account')
                }}</AlertTitle>
                <AlertDescription>
                    {{
                        t(
                            "It was sent to :email, but you're signed in as :current. Sign in with the invited address to accept it.",
                            { email: invitation.email, current: signedInAs },
                        )
                    }}
                </AlertDescription>
            </Alert>
            <Button variant="secondary" class="w-full" as-child>
                <Link :href="logout()" as="button">{{ t('Log out') }}</Link>
            </Button>
        </template>
    </div>
</template>
