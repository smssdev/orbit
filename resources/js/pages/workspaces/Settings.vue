<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import WorkspaceController from '@/actions/App/Http/Controllers/Workspaces/WorkspaceController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { edit } from '@/routes/workspace';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Workspace settings', href: edit() }],
    },
});

defineProps<{
    workspace: { id: number; name: string };
    canUpdate: boolean;
}>();
</script>

<template>
    <Head title="Workspace settings" />

    <div class="max-w-xl space-y-6">
        <Heading
            variant="small"
            title="General"
            :description="
                canUpdate
                    ? 'Rename the workspace for everyone in it'
                    : 'Only owners and admins can change these settings'
            "
        />

        <Form
            v-bind="WorkspaceController.update.form()"
            :options="{ preserveScroll: true }"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="name">Workspace name</Label>
                <Input
                    id="name"
                    name="name"
                    :default-value="workspace.name"
                    :disabled="!canUpdate"
                    required
                    maxlength="80"
                    autocomplete="organization"
                />
                <InputError :message="errors.name" />
            </div>

            <Button
                v-if="canUpdate"
                :disabled="processing"
                data-test="update-workspace-button"
            >
                <Spinner v-if="processing" />
                Save
            </Button>
        </Form>
    </div>
</template>
