# Repository Guidelines

## Project Overview

ЧКУ — «Читальный клуб умничек», private reading club platform with turn-based book selection, book verification, meetings, reading progress, ratings, reviews, and a club archive.

Business rules are source-of-truth documentation and live in `docs/business-rules.md`. Before changing domain behavior, API contracts, UI flows, seed data, or tests for club logic, read that file and keep the implementation aligned with it.

Core rule: the next book can be approved only when no active club member has read it before.

## Project Structure & Module Organization

This repository is a monorepo:

- `apps/chku-frontend` — Vue 3 frontend. Application code is in `src/`, assets are in `src/assets/` and `public/`, tests live near code as `src/**/__tests__/*.spec.ts`.
- `apps/chku-backend` — Laravel backend. Domain models are in `app/Models/`, enums in `app/Enums/`, API resources in `app/Http/Resources/`, controllers in `app/Http/Controllers/`, services in `app/Services/`, migrations and seeders in `database/`, and tests in `tests/`.
- `infra/docker` — development and production Docker Compose setup.
- `infra/deploy` and `infra/scripts` — deployment and database helper scripts.
- `docs` — product and business documentation.

Frontend dashboard UI is in `apps/chku-frontend/src/components/dashboard/`, page views are in `src/views/`, Pinia stores are in `src/stores/`, router setup is in `src/router/`, shared types are in `src/types/`, and local seed/domain data is in `src/data/`.

## Docker-Only Development

Run, test, install dependencies, and execute project scripts from Docker containers by default. Do not run `bun`, `composer`, `php artisan`, `npm`, or Vite directly on the host unless the user explicitly asks for host execution.

Use repository-level Makefile and Docker Compose flow from the repo root:

- `make dev-build` builds development images.
- `make dev` starts the development stack.
- `make dev-logs` tails development logs.
- `make dev-down` stops the development stack.
- `make backend-install` runs Composer install in the backend container.
- `make backend-key` generates the Laravel app key in the backend container.
- `make backend-migrate` runs Laravel migrations in the backend container.
- `make backend-test` runs backend tests in the backend container.

The development app is served through nginx on `http://localhost:8090`.

For commands not covered by Makefile targets, use the dev Compose file explicitly:

- `docker compose --env-file apps/chku-backend/.env -f infra/docker/dev/docker-compose.yml exec frontend bun run test:unit`
- `docker compose --env-file apps/chku-backend/.env -f infra/docker/dev/docker-compose.yml exec frontend bun run build`
- `docker compose --env-file apps/chku-backend/.env -f infra/docker/dev/docker-compose.yml exec frontend bun run lint`
- `docker compose --env-file apps/chku-backend/.env -f infra/docker/dev/docker-compose.yml exec frontend bun run format`
- `docker compose --env-file apps/chku-backend/.env -f infra/docker/dev/docker-compose.yml exec backend php artisan test`
- `docker compose --env-file apps/chku-backend/.env -f infra/docker/dev/docker-compose.yml exec backend php artisan migrate`
- `docker compose --env-file apps/chku-backend/.env -f infra/docker/dev/docker-compose.yml exec backend composer install`

If containers are not running, start them with `make dev` before using `exec`, or use the matching `docker compose ... run --rm <service> <command>` form for one-off commands.

## Build, Test, and Verification

Frontend scripts are defined in `apps/chku-frontend/package.json`:

- `bun run build` type-checks and creates a production build.
- `bun run test:unit` runs Vitest unit tests in `jsdom`.
- `bun run lint` runs Oxlint and ESLint with autofix.
- `bun run format` formats `src/` with Prettier.

Backend scripts are defined in `apps/chku-backend/composer.json` and Laravel Artisan:

- `php artisan test` runs PHPUnit/Laravel tests.
- `php artisan migrate` applies database migrations.
- `composer install` installs backend dependencies.

Execute these scripts inside the appropriate Docker service, not on the host.

## Business Rules & Domain Expectations

Follow `docs/business-rules.md` for product behavior. The MVP centers on this flow:

1. A member sees it is their turn to choose a book.
2. They propose a candidate book.
3. Active members respond whether they have read it.
4. If every active member answers `not_read`, the book can be approved.
5. The club reads the book, tracks progress, holds a meeting, rates and reviews it.
6. The finished book moves into the archive and rating list.

Important domain constraints:

- Book selection is turn-based, not vote-based.
- Voting can be used for meeting date/time, but not as the primary book-selection mechanic.
- A candidate cannot be approved if any active member answered `read`.
- A candidate should not be auto-approved while any active member is `pending` or `not_sure`.
- Only active members participate in the turn order and candidate verification.
- Historical selections, ratings, and reviews should remain when a member leaves the club.
- Ratings are one per member per book within a reading cycle and range from 1 to 10.

## Coding Style & Naming Conventions

Frontend:

- Use Vue 3 single-file components with `<script setup lang="ts">`.
- Templates use Pug via `<template lang="pug">`.
- Component names are PascalCase, for example `DashboardMeetingCard.vue`.
- TypeScript variables, functions, and data files use camelCase.
- Use icons from the connected `@lucide/vue` library instead of custom inline SVG icons when a suitable icon exists.
- Use scoped styles, BEM-like classes such as `current-book__details`, and shared tokens from `src/assets/base.css`.

Backend:

- Follow Laravel conventions for controllers, models, resources, migrations, factories, seeders, and tests.
- Keep domain state names aligned with existing enums in `apps/chku-backend/app/Enums/`.
- Prefer API resources for response shape changes instead of ad hoc controller arrays when a resource already exists.

Let configured formatters and linters decide formatting. Do not hand-format large unrelated areas.

## Testing Guidelines

Frontend tests use Vitest with `jsdom`. Name tests `*.spec.ts` and place them in the nearest `__tests__` directory, for example `src/views/__tests__/HomeView.spec.ts`. For UI changes, prefer Vue Test Utils assertions for rendered text, component state, and user-visible behavior.

Backend tests use Laravel/PHPUnit under `apps/chku-backend/tests`. Cover domain rules in feature or unit tests when changing candidate approval, turn order, reading cycles, meetings, ratings, reviews, or archive behavior.

Run relevant checks in Docker before submitting behavior changes. For full frontend verification, run `bun run test:unit` and `bun run build` inside the frontend container. For backend behavior, run `php artisan test` inside the backend container.

## UI/UX Direction

The interface should be modern, minimal, calm, stylish, readable, and low-noise. It should not feel like a corporate CRM, book marketplace, social network, heavy admin panel, children’s book site, or vintage parchment-themed site.

The dashboard should quickly answer:

- what the club is reading now;
- what my reading progress is;
- when the next meeting is;
- who chooses the next book;
- whether the candidate passed the “nobody has read it” check.

## Frontend Design Patterns

The frontend uses a restrained monochrome, monospace, sharp-edged interface. Keep new UI aligned with the existing system instead of introducing a separate visual language.

- Build Vue 3 SFCs with `<script setup lang="ts">`, Pug templates, scoped styles, and BEM-like classes.
- Use shared tokens from `src/assets/base.css`: `--bg-*`, `--text-*`, `--border*`, `--space-*`, `--accent`, and `--warn`. Avoid hard-coded colors unless a component already owns a specific book-cover color.
- Reuse global primitives from `src/assets/main.css`: `.container`, `.section-header`, `.label-text`, `.body-text`, `.button`, `.panel`, `.badge`, `.avatar`, `.progress`, `.data-list`, and `.book-cover`.
- Keep panels, cards, buttons, inputs, selects, textareas, and avatars rectangular with `border-radius: 0`. Use circles only for icon-only controls or explicitly round elements already established in a component.
- Prefer quiet, dense layouts: CSS grid/flex, clear borders, modest spacing via `var(--space-*)`, and information-first sections. Do not add marketing-style heroes, decorative gradients, parchment textures, playful illustrations, or card-heavy landing-page composition.
- Page views usually start with `.container`, then `.section-header`, then a grid or panel-based content area. Dashboard-style screens should answer the current club state quickly.
- For loading, empty, and error states, use `section.panel` with concise `.body-text` copy and `aria-live` when the content changes asynchronously.
- For forms, pair `label.label-text` with full-width rectangular controls, use `border-color: var(--text-main)` on focus, and `--warn` for validation errors.
- Use `.button` modifiers for actions: primary for the main commit action, secondary for neutral actions, ghost for low-emphasis links, inverted for positive confirmation. Keep button text short and uppercase through `.label-text`.
- Use `@lucide/vue` icons for recognizable actions when helpful, but do not replace clear text labels with ambiguous icon-only controls unless the control has an accessible label.
- Responsive behavior should collapse two-column grids to one column around `960px`; stack form actions and banner actions vertically around `640-760px`.

## Commit & Pull Request Guidelines

Git history uses short imperative commit subjects, for example `Add reusable dashboard components` and `Cover dashboard rendering tests`. Keep commits focused on one logical change.

Pull requests should include a concise summary, verification commands, linked issues when applicable, and screenshots or short recordings for visible UI changes. Call out skipped checks or follow-up work explicitly.

## Security & Configuration Tips

Do not commit secrets, local environment files, database dumps, or production credentials. Keep deployment and database work routed through the existing `infra/` scripts and Makefile targets.
