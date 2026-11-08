<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { Send } from '@lucide/vue';
import { ref } from 'vue';
import InvitationController from '@/actions/App/Http/Controllers/Workspaces/InvitationController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import RoleSelect from '@/components/workspaces/RoleSelect.vue';
import { roleDescriptions } from '@/lib/workspaces';
import type { WorkspaceRole } from '@/types';

const props = defineProps<{
    roles: WorkspaceRole[];
}>();

// Default to the least-privileged role on offer.
const role = ref<WorkspaceRole>(props.roles.at(-1) ?? 'member');
</script>

<template>
    <Form
        v-bind="InvitationController.store.form()"
        :options="{ preserveScroll: true }"
        reset-on-success
        :reset="['email']"
        class="space-y-2"
        v-slot="{ errors, processing }"
    >
        <Label for="invite-email">Invite by email</Label>
        <div class="flex flex-col gap-2 sm:flex-row">
            <Input
                id="invite-email"
                name="email"
                type="email"
                required
                autocomplete="off"
                placeholder="teammate@company.com"
                class="flex-1"
                :aria-invalid="errors.email ? true : undefined"
            />
            <RoleSelect
                v-model="role"
                name="role"
                :roles="roles"
                label="Role for the invited person"
            />
            <Button
                type="submit"
                :disabled="processing"
                data-test="invite-member-button"
            >
                <Spinner v-if="processing" />
                <Send v-else />
                Send invite
            </Button>
        </div>
        <InputError :message="errors.email ?? errors.role" />
        <p class="text-xs text-muted-foreground">
            {{ roleDescriptions[role] }}. The invitation link expires after 7
            days.
        </p>
    </Form>
</template>
