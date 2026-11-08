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
const inviter = computed(() => props.invitation.invitedBy ?? 'A teammate');
</script>

<template>
    <Head :title="`Join ${invitation.workspace}`" />

    <div class="flex flex-col items-center gap-6 text-center">
        <WorkspaceAvatar :name="invitation.workspace" class="size-14 text-lg" />

        <p class="text-sm text-muted-foreground">
            {{ inviter }} invited you to join
            <span class="font-semibold text-foreground">{{
                invitation.workspace
            }}</span>
            as {{ roleLabels[invitation.role].toLowerCase() }}.
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
                Join {{ invitation.workspace }}
            </Button>
            <TextLink :href="dashboard()" class="text-sm">Not now</TextLink>
        </Form>

        <template v-else-if="status === 'expired'">
            <Alert variant="destructive" class="text-left">
                <CircleAlert />
                <AlertTitle>This invitation has expired</AlertTitle>
                <AlertDescription>
                    Ask {{ inviter }} to send you a new one.
                </AlertDescription>
            </Alert>
            <Button variant="secondary" class="w-full" as-child>
                <Link :href="dashboard()">Go to dashboard</Link>
            </Button>
        </template>

        <template v-else>
            <Alert class="text-left">
                <CircleAlert />
                <AlertTitle>This invitation is for another account</AlertTitle>
                <AlertDescription>
                    It was sent to {{ invitation.email }}, but you're signed in
                    as {{ signedInAs }}. Sign in with the invited address to
                    accept it.
                </AlertDescription>
            </Alert>
            <Button variant="secondary" class="w-full" as-child>
                <Link :href="logout()" as="button">Log out</Link>
            </Button>
        </template>
    </div>
</template>
