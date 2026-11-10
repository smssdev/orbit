# Orbit

A real-time project management app: workspaces, Kanban boards, live collaboration, and a keyboard-first interface.

> **Status: in active development.** A portfolio project. The roadmap below shows what's done and what's next.

## Tech stack

| Layer     | Choice                                                          |
| --------- | --------------------------------------------------------------- |
| Back end  | Laravel 13, PHP 8.4+                                            |
| Front end | Vue 3 (`<script setup>`, TypeScript), Inertia 3, Tailwind CSS 4 |
| Routing   | Laravel Wayfinder (type-safe routes in TypeScript)              |
| Real-time | Laravel Reverb + Echo _(planned)_                               |
| Auth      | Laravel Fortify (2FA, passkeys)                                 |

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

**v1**, one pull request per step:

- [x] Project setup, quality tooling, CI
- [x] Workspaces, members, and invitations
- [x] Projects: create, edit, archive and restore, colours and icons, the active ones in the sidebar
- [x] Kanban board: editable columns, drag and drop for cards and columns with optimistic updates, order saved on the server, keyboard support
- [x] Task details: a side panel with description, assignee, due date, priority, and labels
- [x] Arabic/English with full RTL, and a refined dark mode
- [ ] One-click demo login with realistic seed data reset on a schedule, and a polished welcome page
- [ ] Final polish (loading and empty states, accessibility, performance), then the live demo at `orbit.smszs.tech`

**After v1:** real-time updates and presence (Reverb), comments, @mentions, notifications and activity log, and the command palette (Ctrl+K) with keyboard shortcuts.

## Local setup

Requires PHP 8.4+, Composer, and Node 22+.

```bash
composer setup
composer dev
```

## License

MIT
