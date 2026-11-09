<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { FolderKanban, KanbanSquare, Users } from '@lucide/vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Button } from '@/components/ui/button';
import { dashboard, login, register } from '@/routes';

const features = [
    {
        icon: Users,
        title: 'Workspaces for your team',
        body: 'Invite people by email, give them a role, and switch between workspaces in one click.',
    },
    {
        icon: FolderKanban,
        title: 'Projects that stay tidy',
        body: 'Give each project a colour and an icon, keep active ones in the sidebar and archive the rest.',
    },
    {
        icon: KanbanSquare,
        title: 'A board for every project',
        body: 'Shape your own columns and move tasks by drag and drop or straight from the keyboard.',
    },
];
</script>

<template>
    <Head title="Orbit" />

    <div class="flex min-h-screen flex-col bg-background text-foreground">
        <header
            class="mx-auto flex w-full max-w-5xl items-center justify-between px-4 py-5 sm:px-6"
        >
            <div class="flex items-center gap-2 font-semibold">
                <span
                    class="flex size-8 items-center justify-center rounded-md bg-primary text-primary-foreground"
                >
                    <AppLogoIcon class="size-5 fill-current" />
                </span>
                Orbit
            </div>
            <nav class="flex items-center gap-2">
                <Button v-if="$page.props.auth.user" as-child>
                    <Link :href="dashboard()">Open Orbit</Link>
                </Button>
                <template v-else>
                    <Button variant="ghost" as-child>
                        <Link :href="login()">Log in</Link>
                    </Button>
                    <Button as-child>
                        <Link :href="register()">Get started</Link>
                    </Button>
                </template>
            </nav>
        </header>

        <main
            class="mx-auto flex w-full max-w-5xl flex-1 flex-col px-4 sm:px-6"
        >
            <section class="py-16 text-center sm:py-24">
                <h1
                    class="mx-auto max-w-2xl text-4xl font-semibold tracking-tight sm:text-5xl"
                >
                    Plan your team's work in one place
                </h1>
                <p class="mx-auto mt-5 max-w-xl text-lg text-muted-foreground">
                    Orbit brings your workspaces, projects and boards together,
                    so everyone can see what is in progress and what comes next.
                </p>
                <div class="mt-8 flex justify-center gap-3">
                    <Button v-if="$page.props.auth.user" size="lg" as-child>
                        <Link :href="dashboard()">Go to your dashboard</Link>
                    </Button>
                    <template v-else>
                        <Button size="lg" as-child>
                            <Link :href="register()"
                                >Create a free account</Link
                            >
                        </Button>
                        <Button size="lg" variant="outline" as-child>
                            <Link :href="login()">Log in</Link>
                        </Button>
                    </template>
                </div>
            </section>

            <section class="grid gap-4 pb-20 sm:grid-cols-3">
                <div
                    v-for="feature in features"
                    :key="feature.title"
                    class="rounded-xl border bg-card p-6 text-card-foreground"
                >
                    <component
                        :is="feature.icon"
                        class="size-6 text-primary"
                        aria-hidden="true"
                    />
                    <h2 class="mt-4 font-medium">{{ feature.title }}</h2>
                    <p class="mt-2 text-sm text-muted-foreground">
                        {{ feature.body }}
                    </p>
                </div>
            </section>
        </main>

        <footer class="border-t py-6 text-center text-sm text-muted-foreground">
            Orbit
        </footer>
    </div>
</template>
