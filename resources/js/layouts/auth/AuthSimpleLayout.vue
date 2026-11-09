<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { CheckCircle2 } from '@lucide/vue';
import BrandWordmark from '@/components/BrandWordmark.vue';
import LocaleProvider from '@/components/LocaleProvider.vue';
import LocaleSwitcher from '@/components/LocaleSwitcher.vue';
import OrbitIllustration from '@/components/OrbitIllustration.vue';
import { useI18n } from '@/composables/useI18n';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();

const { t } = useI18n();

const points = [
    'Workspaces and roles for your whole team',
    'Boards you shape to the way you work',
    'Arabic and English, right to left included',
];
</script>

<template>
    <LocaleProvider>
        <div class="grid min-h-svh bg-background lg:grid-cols-[1.05fr_1fr]">
            <!-- Brand panel -->
            <aside
                class="relative hidden overflow-hidden bg-[oklch(0.25_0.09_278)] p-10 text-white lg:flex lg:flex-col"
            >
                <div
                    class="pointer-events-none absolute inset-0 bg-[radial-gradient(60%_50%_at_20%_10%,oklch(0.55_0.2_285/.55),transparent),radial-gradient(50%_40%_at_90%_90%,oklch(0.76_0.16_62/.25),transparent)]"
                    aria-hidden="true"
                />
                <Link :href="home()" class="relative w-fit">
                    <BrandWordmark />
                </Link>

                <div class="relative my-auto max-w-md py-10">
                    <OrbitIllustration class="mb-10 h-44 w-auto opacity-95" />
                    <h2
                        class="text-3xl leading-tight font-semibold tracking-tight"
                    >
                        {{ t('Every project, in one orbit.') }}
                    </h2>
                    <p class="mt-3 text-white/70">
                        {{
                            t(
                                'Plan, track and ship your team’s work together, from the first idea to done.',
                            )
                        }}
                    </p>
                    <ul class="mt-8 space-y-3 text-sm text-white/85">
                        <li
                            v-for="point in points"
                            :key="point"
                            class="flex items-center gap-3"
                        >
                            <CheckCircle2
                                class="size-4 shrink-0 text-comet"
                                aria-hidden="true"
                            />
                            {{ t(point) }}
                        </li>
                    </ul>
                </div>

                <p class="relative text-xs text-white/50">
                    © {{ new Date().getFullYear() }} Orbit
                </p>
            </aside>

            <!-- Form -->
            <main class="flex flex-col">
                <div class="flex items-center justify-between p-4 sm:p-6">
                    <Link :href="home()" class="lg:invisible">
                        <BrandWordmark />
                    </Link>
                    <LocaleSwitcher />
                </div>
                <div
                    class="flex flex-1 items-center justify-center px-4 pb-16 sm:px-6"
                >
                    <div class="w-full max-w-sm">
                        <div class="mb-8 space-y-2">
                            <h1 class="text-2xl font-semibold tracking-tight">
                                {{ title ? t(title) : '' }}
                            </h1>
                            <p
                                v-if="description"
                                class="text-sm text-muted-foreground"
                            >
                                {{ t(description) }}
                            </p>
                        </div>
                        <slot />
                    </div>
                </div>
            </main>
        </div>
    </LocaleProvider>
</template>
