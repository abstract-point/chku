# Repository Guidelines

## Project Structure & Module Organization

This repository is a platform monorepo. The Vue frontend lives in `apps/chku-frontend`, with application code in `src/`, static assets in `src/assets/` and `public/`, and tests under `src/**/__tests__/*.spec.ts`. Dashboard UI is in `src/components/dashboard/`, page views in `src/views/`, Pinia stores in `src/stores/`, router setup in `src/router/`, shared types in `src/types/`, and seed/domain data in `src/data/`.

Infrastructure is under `infra/`: Docker Compose files in `infra/docker/`, deployment scripts in `infra/deploy`, and database helpers in `infra/scripts`. Static design/reference HTML files are kept in `docs/`.

## Build, Test, and Development Commands

Run frontend commands from `apps/chku-frontend`:

- `bun install` installs dependencies from `bun.lock`.
- `bun run dev` starts the Vite dev server.
- `bun run build` type-checks and creates a production build.
- `bun run test:unit` runs Vitest unit tests in `jsdom`.
- `bun run lint` runs Oxlint and ESLint with autofix.
- `bun run format` formats `src/` with Prettier.

Repository-level Docker helpers:

- `make dev` / `make dev-down` start or stop the dev Compose stack.
- `make dev-logs` tails development logs.
- `make prod` builds and starts the production Compose stack.

Run the project through Docker by default. For local manual verification, use the repository-level Docker/Makefile flow (`make dev`, `make dev-logs`, `make dev-down`) instead of starting the frontend directly with `bun run dev` on the host.

## Coding Style & Naming Conventions

Use Vue 3 single-file components with `<script setup lang="ts">`; templates use Pug via `<template lang="pug">`. Keep component names in PascalCase, for example `DashboardMeetingCard.vue`, and use camelCase for TypeScript variables, functions, and data files. Follow the current CSS style: scoped styles, BEM-like classes such as `current-book__details`, and shared tokens from `src/assets/base.css`.

Let the configured tools decide formatting. Do not hand-format large areas unrelated to a change.

## Testing Guidelines

Vitest is configured in `vitest.config.ts` with a `jsdom` environment. Name tests `*.spec.ts` and place them in the nearest `__tests__` directory, such as `src/views/__tests__/HomeView.spec.ts`. For UI changes, prefer Vue Test Utils assertions that verify rendered text, component state, and user-visible behavior. Run `bun run test:unit` before submitting frontend behavior changes.

## Commit & Pull Request Guidelines

Git history uses short imperative commit subjects, for example `Add reusable dashboard components` and `Cover dashboard rendering tests`. Keep commits focused on one logical change.

Pull requests should include a concise summary, verification commands, linked issues when applicable, and screenshots or short recordings for visible UI changes. Call out skipped checks or follow-up work explicitly.

## Security & Configuration Tips

Do not commit secrets, local environment files, database dumps, or production credentials. Keep deployment and database work routed through the existing `infra/` scripts and Makefile targets.
