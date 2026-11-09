<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    CalendarClock,
    Check,
    FolderKanban,
    Keyboard,
    KanbanSquare,
    Languages,
    ListChecks,
    MoonStar,
    ShieldCheck,
    Tags,
    Timer,
    Users,
} from '@lucide/vue';
import BrandMark from '@/components/BrandMark.vue';
import BrandWordmark from '@/components/BrandWordmark.vue';
import LocaleProvider from '@/components/LocaleProvider.vue';
import LocaleSwitcher from '@/components/LocaleSwitcher.vue';
import { Button } from '@/components/ui/button';
import { useI18n } from '@/composables/useI18n';
import { dashboard, login, register } from '@/routes';

const { t } = useI18n();

const features = [
    {
        icon: Users,
        title: 'Workspaces for every team',
        body: 'Invite people by email, give each one a role (owner, admin or member) and switch workspaces in a click.',
    },
    {
        icon: FolderKanban,
        title: 'Projects that stay tidy',
        body: 'Give every project a colour and an icon, keep the active ones in the sidebar and archive the rest without losing anything.',
    },
    {
        icon: KanbanSquare,
        title: 'Boards shaped your way',
        body: 'Add, rename and reorder columns, then drag cards from idea to done. Everyone sees the same board.',
    },
    {
        icon: Keyboard,
        title: 'Fast from the keyboard',
        body: 'Pick a card up with Space and move it with the arrow keys. Screen readers announce every move.',
    },
    {
        icon: Languages,
        title: 'Arabic and English',
        body: 'The whole interface, emails included, in both languages, with a true right-to-left layout in Arabic.',
    },
    {
        icon: ShieldCheck,
        title: 'Secure by default',
        body: 'Two-factor authentication, passkeys and verified emails keep your team’s work in the right hands.',
    },
];

const steps = [
    {
        title: 'Create your workspace',
        body: 'Sign up and your first workspace is ready, named after you.',
    },
    {
        title: 'Invite your team',
        body: 'Send invitations by email and choose what each person can do.',
    },
    {
        title: 'Plan on the board',
        body: 'Open a project, shape its columns and start moving work forward.',
    },
];

const roadmap = [
    { icon: Tags, label: 'Labels, priorities and task types' },
    { icon: CalendarClock, label: 'Due dates and assignees' },
    { icon: ListChecks, label: 'Checklists, comments and attachments' },
    { icon: Timer, label: 'Backlog, sprints and reports' },
];

// The product preview on the hero: a small board, drawn with the app's own styles.
const previewColumns = [
    {
        name: 'To do',
        cards: [
            {
                title: 'Interview five customers',
                tag: 'Research',
                tone: 'bg-chart-2/15 text-chart-2',
            },
            {
                title: 'Draft the pricing page',
                tag: 'Marketing',
                tone: 'bg-chart-4/15 text-chart-4',
            },
        ],
    },
    {
        name: 'In progress',
        cards: [
            {
                title: 'Arabic onboarding emails',
                tag: 'Content',
                tone: 'bg-comet/20 text-[oklch(0.5_0.13_62)] dark:text-comet',
            },
            {
                title: 'Board keyboard shortcuts',
                tag: 'Product',
                tone: 'bg-primary/12 text-primary',
            },
        ],
    },
    {
        name: 'Done',
        cards: [
            {
                title: 'Launch the new website',
                tag: 'Marketing',
                tone: 'bg-chart-4/15 text-chart-4',
            },
        ],
    },
];
</script>

<template>
    <Head />

    <LocaleProvider>
        <div class="min-h-screen bg-background text-foreground">
            <!-- Header -->
            <header
                class="sticky top-0 z-30 border-b border-border/60 bg-background/80 backdrop-blur-md"
            >
                <div
                    class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-4 px-4 sm:px-6"
                >
                    <Link :href="'/'" :aria-label="t('Orbit home')">
                        <BrandWordmark />
                    </Link>
                    <nav
                        class="hidden items-center gap-6 text-sm text-muted-foreground md:flex"
                    >
                        <a href="#features" class="hover:text-foreground">{{
                            t('Features')
                        }}</a>
                        <a href="#how" class="hover:text-foreground">{{
                            t('How it works')
                        }}</a>
                        <a href="#roadmap" class="hover:text-foreground">{{
                            t('Coming next')
                        }}</a>
                    </nav>
                    <div class="flex items-center gap-1 sm:gap-2">
                        <LocaleSwitcher class="max-sm:hidden" />
                        <LocaleSwitcher compact class="sm:hidden" />
                        <Button v-if="$page.props.auth.user" size="sm" as-child>
                            <Link :href="dashboard()">{{
                                t('Open Orbit')
                            }}</Link>
                        </Button>
                        <template v-else>
                            <Button
                                variant="ghost"
                                size="sm"
                                class="max-sm:hidden"
                                as-child
                            >
                                <Link :href="login()">{{ t('Log in') }}</Link>
                            </Button>
                            <Button size="sm" as-child>
                                <Link :href="register()">{{
                                    t('Get started')
                                }}</Link>
                            </Button>
                        </template>
                    </div>
                </div>
            </header>

            <main>
                <!-- Hero -->
                <section class="relative overflow-hidden">
                    <div
                        class="pointer-events-none absolute inset-0 -z-0 bg-[radial-gradient(55%_45%_at_50%_0%,oklch(0.51_0.21_278/.14),transparent),radial-gradient(30%_30%_at_85%_30%,oklch(0.76_0.16_62/.12),transparent)]"
                        aria-hidden="true"
                    />
                    <div
                        class="relative mx-auto max-w-6xl px-4 pt-16 pb-20 text-center sm:px-6 sm:pt-24"
                    >
                        <p
                            class="mx-auto inline-flex items-center gap-2 rounded-full border bg-card/70 px-3 py-1 text-xs font-medium text-muted-foreground shadow-xs"
                        >
                            <span class="size-1.5 rounded-full bg-comet" />
                            {{ t('Now in Arabic and English') }}
                        </p>
                        <h1
                            class="mx-auto mt-6 max-w-3xl text-4xl leading-[1.1] font-semibold tracking-tight text-balance sm:text-6xl"
                        >
                            {{ t('Every project your team runs,') }}
                            <span
                                class="bg-linear-to-r from-primary to-[oklch(0.62_0.2_300)] bg-clip-text text-transparent"
                                >{{ t('in one orbit.') }}</span
                            >
                        </h1>
                        <p
                            class="mx-auto mt-6 max-w-2xl text-lg text-pretty text-muted-foreground"
                        >
                            {{
                                t(
                                    'Orbit brings your workspaces, projects and boards together, so everyone can see what is in progress, what is next and what is done.',
                                )
                            }}
                        </p>
                        <div
                            class="mt-9 flex flex-wrap items-center justify-center gap-3"
                        >
                            <Button
                                v-if="$page.props.auth.user"
                                size="lg"
                                as-child
                            >
                                <Link :href="dashboard()">
                                    {{ t('Go to your dashboard') }}
                                    <ArrowRight class="rtl:-scale-x-100" />
                                </Link>
                            </Button>
                            <template v-else>
                                <Button size="lg" as-child>
                                    <Link :href="register()">
                                        {{ t('Start for free') }}
                                        <ArrowRight class="rtl:-scale-x-100" />
                                    </Link>
                                </Button>
                                <Button size="lg" variant="outline" as-child>
                                    <Link :href="login()">{{
                                        t('Log in')
                                    }}</Link>
                                </Button>
                            </template>
                        </div>

                        <!-- Product preview -->
                        <div
                            class="relative mx-auto mt-16 max-w-5xl rounded-2xl border bg-card/80 p-2 shadow-[0_30px_80px_-30px_oklch(0.4_0.15_278/.45)] backdrop-blur"
                            aria-hidden="true"
                        >
                            <div
                                class="flex items-center gap-2 border-b px-3 py-2.5"
                            >
                                <BrandMark class="size-5" />
                                <span class="text-sm font-medium">
                                    {{ t('Website relaunch') }}
                                </span>
                                <span
                                    class="ms-auto hidden gap-1.5 sm:inline-flex"
                                >
                                    <span
                                        v-for="n in 3"
                                        :key="n"
                                        class="grid size-6 place-items-center rounded-full border-2 border-card text-[10px] font-semibold text-white"
                                        :class="
                                            [
                                                'bg-primary',
                                                'bg-chart-2',
                                                'bg-comet',
                                            ][n - 1]
                                        "
                                        >{{ ['SA', 'LM', 'NK'][n - 1] }}</span
                                    >
                                </span>
                            </div>
                            <div
                                class="grid gap-3 p-3 text-start sm:grid-cols-3"
                            >
                                <div
                                    v-for="column in previewColumns"
                                    :key="column.name"
                                    class="rounded-xl bg-muted/60 p-2.5"
                                >
                                    <p
                                        class="flex items-center justify-between px-1 pb-2 text-xs font-medium text-muted-foreground"
                                    >
                                        {{ t(column.name) }}
                                        <span class="tabular-nums">{{
                                            column.cards.length
                                        }}</span>
                                    </p>
                                    <div class="space-y-2">
                                        <div
                                            v-for="card in column.cards"
                                            :key="card.title"
                                            class="rounded-lg border bg-card p-3 shadow-xs"
                                        >
                                            <p class="text-sm">
                                                {{ t(card.title) }}
                                            </p>
                                            <span
                                                class="mt-2 inline-block rounded-md px-1.5 py-0.5 text-[11px] font-medium"
                                                :class="card.tone"
                                                >{{ t(card.tag) }}</span
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Features -->
                <section
                    id="features"
                    class="scroll-mt-20 border-t bg-muted/30 py-20 sm:py-28"
                >
                    <div class="mx-auto max-w-6xl px-4 sm:px-6">
                        <div class="mx-auto max-w-2xl text-center">
                            <p
                                class="text-sm font-semibold tracking-wide text-primary"
                            >
                                {{ t('Features') }}
                            </p>
                            <h2
                                class="mt-2 text-3xl font-semibold tracking-tight text-balance sm:text-4xl"
                            >
                                {{
                                    t(
                                        'Everything your team needs to plan and ship',
                                    )
                                }}
                            </h2>
                            <p class="mt-4 text-muted-foreground">
                                {{
                                    t(
                                        'Simple enough to start in a minute, structured enough for real teams.',
                                    )
                                }}
                            </p>
                        </div>
                        <ul
                            class="mt-14 grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
                        >
                            <li
                                v-for="feature in features"
                                :key="feature.title"
                                class="rounded-2xl border bg-card p-6 transition-shadow hover:shadow-md"
                            >
                                <span
                                    class="grid size-10 place-items-center rounded-xl bg-accent text-accent-foreground"
                                >
                                    <component
                                        :is="feature.icon"
                                        class="size-5"
                                        aria-hidden="true"
                                    />
                                </span>
                                <h3 class="mt-5 font-semibold">
                                    {{ t(feature.title) }}
                                </h3>
                                <p
                                    class="mt-2 text-sm leading-relaxed text-muted-foreground"
                                >
                                    {{ t(feature.body) }}
                                </p>
                            </li>
                        </ul>
                    </div>
                </section>

                <!-- How it works -->
                <section id="how" class="scroll-mt-20 py-20 sm:py-28">
                    <div class="mx-auto max-w-6xl px-4 sm:px-6">
                        <div class="mx-auto max-w-2xl text-center">
                            <p
                                class="text-sm font-semibold tracking-wide text-primary"
                            >
                                {{ t('How it works') }}
                            </p>
                            <h2
                                class="mt-2 text-3xl font-semibold tracking-tight text-balance sm:text-4xl"
                            >
                                {{
                                    t(
                                        'From sign-up to a moving board in minutes',
                                    )
                                }}
                            </h2>
                        </div>
                        <ol class="mt-14 grid gap-6 md:grid-cols-3">
                            <li
                                v-for="(step, index) in steps"
                                :key="step.title"
                                class="relative rounded-2xl border bg-card p-6"
                            >
                                <span
                                    class="grid size-9 place-items-center rounded-full bg-primary text-sm font-semibold text-primary-foreground tabular-nums"
                                    >{{ index + 1 }}</span
                                >
                                <h3 class="mt-5 font-semibold">
                                    {{ t(step.title) }}
                                </h3>
                                <p class="mt-2 text-sm text-muted-foreground">
                                    {{ t(step.body) }}
                                </p>
                            </li>
                        </ol>
                    </div>
                </section>

                <!-- Roadmap -->
                <section
                    id="roadmap"
                    class="scroll-mt-20 border-y bg-muted/30 py-20 sm:py-24"
                >
                    <div
                        class="mx-auto grid max-w-6xl items-center gap-10 px-4 sm:px-6 lg:grid-cols-2"
                    >
                        <div>
                            <p
                                class="text-sm font-semibold tracking-wide text-primary"
                            >
                                {{ t('Coming next') }}
                            </p>
                            <h2
                                class="mt-2 text-3xl font-semibold tracking-tight text-balance sm:text-4xl"
                            >
                                {{ t('Growing into a full project suite') }}
                            </h2>
                            <p class="mt-4 text-muted-foreground">
                                {{
                                    t(
                                        'Richer cards and agile planning are on the way, built on the same boards you use today.',
                                    )
                                }}
                            </p>
                        </div>
                        <ul class="grid gap-3 sm:grid-cols-2">
                            <li
                                v-for="item in roadmap"
                                :key="item.label"
                                class="flex items-center gap-3 rounded-xl border bg-card p-4 text-sm"
                            >
                                <component
                                    :is="item.icon"
                                    class="size-5 shrink-0 text-primary"
                                    aria-hidden="true"
                                />
                                {{ t(item.label) }}
                            </li>
                        </ul>
                    </div>
                </section>

                <!-- Call to action -->
                <section class="py-20 sm:py-28">
                    <div class="mx-auto max-w-6xl px-4 sm:px-6">
                        <div
                            class="relative overflow-hidden rounded-3xl bg-[oklch(0.25_0.09_278)] px-6 py-14 text-center text-white sm:px-12"
                        >
                            <div
                                class="pointer-events-none absolute inset-0 bg-[radial-gradient(50%_60%_at_20%_0%,oklch(0.55_0.2_285/.6),transparent),radial-gradient(40%_50%_at_90%_100%,oklch(0.76_0.16_62/.3),transparent)]"
                                aria-hidden="true"
                            />
                            <div class="relative">
                                <h2
                                    class="mx-auto max-w-2xl text-3xl font-semibold tracking-tight text-balance sm:text-4xl"
                                >
                                    {{ t('Bring your team into orbit') }}
                                </h2>
                                <p class="mx-auto mt-4 max-w-xl text-white/75">
                                    {{
                                        t(
                                            'Free to start. Set up your workspace and invite your team today.',
                                        )
                                    }}
                                </p>
                                <ul
                                    class="mx-auto mt-6 flex max-w-xl flex-wrap justify-center gap-x-6 gap-y-2 text-sm text-white/85"
                                >
                                    <li
                                        v-for="point in [
                                            'No credit card',
                                            'Arabic and English',
                                            'Light and dark mode',
                                        ]"
                                        :key="point"
                                        class="flex items-center gap-2"
                                    >
                                        <Check class="size-4 text-comet" />
                                        {{ t(point) }}
                                    </li>
                                </ul>
                                <Button
                                    size="lg"
                                    class="mt-8 bg-white text-[oklch(0.3_0.1_278)] hover:bg-white/90"
                                    as-child
                                >
                                    <Link
                                        :href="
                                            $page.props.auth.user
                                                ? dashboard()
                                                : register()
                                        "
                                    >
                                        {{
                                            $page.props.auth.user
                                                ? t('Go to your dashboard')
                                                : t('Create your workspace')
                                        }}
                                        <ArrowRight class="rtl:-scale-x-100" />
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    </div>
                </section>
            </main>

            <footer class="border-t">
                <div
                    class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 px-4 py-8 text-sm text-muted-foreground sm:flex-row sm:px-6"
                >
                    <BrandWordmark />
                    <p class="flex items-center gap-2">
                        <MoonStar class="size-4" aria-hidden="true" />
                        © {{ new Date().getFullYear() }} Orbit.
                        {{ t('Plan together.') }}
                    </p>
                </div>
            </footer>
        </div>
    </LocaleProvider>
</template>
