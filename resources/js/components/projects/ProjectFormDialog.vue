<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Check } from '@lucide/vue';
import { computed, watch } from 'vue';
import ProjectController from '@/actions/App/Http/Controllers/Projects/ProjectController';
import InputError from '@/components/InputError.vue';
import ProjectBadge from '@/components/projects/ProjectBadge.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { projectColors, projectIcons } from '@/lib/projects';
import { cn } from '@/lib/utils';
import type { ProjectColor, ProjectIcon, ProjectSummary } from '@/types';

const props = defineProps<{
    /** The project to edit; none to create one. */
    project?: ProjectSummary | null;
    colors: ProjectColor[];
    icons: ProjectIcon[];
}>();

const open = defineModel<boolean>('open', { required: true });

const form = useForm({
    name: '',
    description: '',
    color: 'blue' as ProjectColor,
    icon: 'folder' as ProjectIcon,
});

const editing = computed(() => !!props.project);

// Each time the dialog opens it starts from the project (or a fresh one).
watch(open, (isOpen) => {
    if (!isOpen) {
        return;
    }

    form.clearErrors();
    form.defaults({
        name: props.project?.name ?? '',
        description: props.project?.description ?? '',
        color: props.project?.color ?? 'blue',
        icon: props.project?.icon ?? 'folder',
    });
    form.reset();
});

function submit(): void {
    const options = {
        preserveScroll: true,
        onSuccess: () => (open.value = false),
    };

    if (props.project) {
        form.submit(ProjectController.update(props.project.id), options);
    } else {
        form.submit(ProjectController.store(), options);
    }
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-lg">
            <form class="space-y-6" @submit.prevent="submit">
                <DialogHeader>
                    <DialogTitle>{{
                        editing ? 'Edit project' : 'New project'
                    }}</DialogTitle>
                    <DialogDescription>
                        {{
                            editing
                                ? 'Changes show for everyone in the workspace.'
                                : 'Everyone in the workspace can see it.'
                        }}
                    </DialogDescription>
                </DialogHeader>

                <div class="flex items-center gap-3">
                    <ProjectBadge
                        :color="form.color"
                        :icon="form.icon"
                        size="lg"
                    />
                    <div class="grid flex-1 gap-2">
                        <Label for="project-name">Name</Label>
                        <Input
                            id="project-name"
                            v-model="form.name"
                            required
                            maxlength="80"
                            placeholder="Website relaunch"
                            autocomplete="off"
                            data-test="project-name"
                        />
                        <InputError :message="form.errors.name" />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="project-description">
                        Description
                        <span class="font-normal text-muted-foreground"
                            >(optional)</span
                        >
                    </Label>
                    <textarea
                        id="project-description"
                        v-model="form.description"
                        rows="3"
                        maxlength="500"
                        class="w-full resize-none rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                        placeholder="What is this project about?"
                    />
                    <InputError :message="form.errors.description" />
                </div>

                <fieldset class="grid gap-2">
                    <legend class="mb-2 text-sm font-medium">Colour</legend>
                    <div class="flex flex-wrap gap-2">
                        <label
                            v-for="color in colors"
                            :key="color"
                            :class="
                                cn(
                                    'relative grid size-8 cursor-pointer place-items-center rounded-full ring-offset-2 ring-offset-background has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-ring',
                                    projectColors[color].dot,
                                )
                            "
                        >
                            <input
                                v-model="form.color"
                                type="radio"
                                name="color"
                                :value="color"
                                class="sr-only"
                            />
                            <span class="sr-only">{{
                                projectColors[color].label
                            }}</span>
                            <Check
                                v-if="form.color === color"
                                class="size-4 text-white"
                            />
                        </label>
                    </div>
                    <InputError :message="form.errors.color" />
                </fieldset>

                <fieldset class="grid gap-2">
                    <legend class="mb-2 text-sm font-medium">Icon</legend>
                    <div class="grid grid-cols-5 gap-2">
                        <label
                            v-for="icon in icons"
                            :key="icon"
                            :class="
                                cn(
                                    'grid h-10 cursor-pointer place-items-center rounded-md border has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-ring',
                                    form.icon === icon
                                        ? 'border-primary bg-accent'
                                        : 'border-input hover:bg-accent/60',
                                )
                            "
                        >
                            <input
                                v-model="form.icon"
                                type="radio"
                                name="icon"
                                :value="icon"
                                class="sr-only"
                            />
                            <span class="sr-only">{{ icon }}</span>
                            <component
                                :is="projectIcons[icon]"
                                class="size-4.5"
                            />
                        </label>
                    </div>
                    <InputError :message="form.errors.icon" />
                </fieldset>

                <DialogFooter class="gap-2">
                    <Button
                        type="button"
                        variant="secondary"
                        @click="open = false"
                    >
                        Cancel
                    </Button>
                    <Button
                        type="submit"
                        :disabled="form.processing"
                        data-test="save-project-button"
                    >
                        <Spinner v-if="form.processing" />
                        {{ editing ? 'Save' : 'Create project' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
