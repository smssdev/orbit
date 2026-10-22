# Orbit

A real-time project management app: workspaces, Kanban boards, live collaboration, and a keyboard-first interface.

> **Status: in active development.** Built in public as a portfolio project. The roadmap below shows what's done and what's next.

## Tech stack

| Layer | Choice |
|---|---|
| Back end | Laravel 13, PHP 8.4+ |
| Front end | Vue 3 (`<script setup>`, TypeScript), Inertia 3, Tailwind CSS 4 |
| Routing | Laravel Wayfinder (type-safe routes in TypeScript) |
| Real-time | Laravel Reverb + Echo *(planned)* |
| Auth | Laravel Fortify (2FA, passkeys) |

## Code quality

Every push runs the full check in GitHub Actions:

- **Pest** feature tests and **architecture tests** (PHP, security, and Laravel presets)
- **Larastan** at the strictest level (`max`), with no baseline and no ignored errors
- **Rector** for automated refactoring checks
- **Pint** for PHP code style
- **Vite+** lint and format checks, plus `vue-tsc` type checking

```bash
composer ci:check
```

## Roadmap

- [x] Project setup, quality tooling, CI
- [ ] Workspaces, members, and invitations
- [ ] Projects, Kanban board, drag and drop with optimistic updates
- [ ] Task details: rich text, labels, assignees, due dates, attachments
- [ ] Real-time updates and presence (Reverb)
- [ ] Comments, @mentions, notifications, activity log
- [ ] Command palette (Ctrl+K) and keyboard shortcuts
- [ ] Dark mode, Arabic/English with RTL
- [ ] Live demo

## Local setup

Requires PHP 8.4+, Composer, and Node 22+.

```bash
composer setup
composer dev
```

## License

MIT
