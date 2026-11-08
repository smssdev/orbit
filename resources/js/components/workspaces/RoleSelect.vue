<script setup lang="ts">
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { roleDescriptions, roleLabels } from '@/lib/workspaces';
import type { WorkspaceRole } from '@/types';

defineProps<{
    roles: WorkspaceRole[];
    id?: string;
    name?: string;
    disabled?: boolean;
    label: string;
}>();

const role = defineModel<WorkspaceRole>({ required: true });
</script>

<template>
    <Select v-model="role" :name="name" :disabled="disabled">
        <SelectTrigger :id="id" class="w-32" :aria-label="label">
            <SelectValue />
        </SelectTrigger>
        <SelectContent align="end">
            <SelectItem
                v-for="option in roles"
                :key="option"
                :value="option"
                :title="roleDescriptions[option]"
            >
                {{ roleLabels[option] }}
            </SelectItem>
        </SelectContent>
    </Select>
</template>
