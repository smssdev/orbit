<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import WorkspaceController from '@/actions/App/Http/Controllers/Workspaces/WorkspaceController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { dashboard } from '@/routes';
import { create } from '@/routes/workspaces';
import { useI18n } from '@/composables/useI18n';

const { t } = useI18n();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'New workspace', href: create() }],
    },
});

const page = usePage();
const hasWorkspaces = computed(() => page.props.workspaces.length > 0);
</script>

<template>
    <Head :title="t('New workspace')" />

    <div class="flex flex-1 items-start justify-center p-4 md:items-center">
        <Card class="w-full max-w-md">
            <CardHeader>
                <CardTitle class="text-xl">{{
                    t('Create a workspace')
                }}</CardTitle>
                <CardDescription>
                    {{
                        t(
                            "A workspace holds your projects and the people you work with. You can invite teammates once it's set up.",
                        )
                    }}
                </CardDescription>
            </CardHeader>
            <CardContent>
                <Form
                    v-bind="WorkspaceController.store.form()"
                    class="space-y-6"
                    v-slot="{ errors, processing }"
                >
                    <div class="grid gap-2">
                        <Label for="name">{{ t('Workspace name') }}</Label>
                        <Input
                            id="name"
                            name="name"
                            required
                            maxlength="80"
                            autocomplete="organization"
                            :placeholder="t('e.g. Acme Inc.')"
                            v-focus
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="flex items-center justify-end gap-2">
                        <Button
                            v-if="hasWorkspaces"
                            variant="ghost"
                            type="button"
                            as-child
                        >
                            <Link :href="dashboard()">{{ t('Cancel') }}</Link>
                        </Button>
                        <Button
                            type="submit"
                            :disabled="processing"
                            data-test="create-workspace-button"
                        >
                            <Spinner v-if="processing" />
                            {{ t('Create workspace') }}
                        </Button>
                    </div>
                </Form>
            </CardContent>
        </Card>
    </div>
</template>
