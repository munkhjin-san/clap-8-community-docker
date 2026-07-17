# Agent Instructions

**Start here:** read [AGENT_CONTEXT.md](./AGENT_CONTEXT.md) for full app structure, domain map, entry points, and active migration work. This file covers day-to-day rules only.

This repository follows the contribution and coding rules in `CONTRIBUTING.md`.

## Quality Gates
- PHP lint/static analysis: `composer run-script phpcs`, `composer run-script phpstan`
- JS/TS lint/type check: `npm run lint`, `npm run type-check`
- Tests: `php artisan test` (PHP), `npm run test` (JS)

## Coding Standards
- PHP/Laravel: PSR-12, Laravel best practices, meaningful names, comment complex logic.
- JS/TS: follow ESLint/Prettier, use proper types, add JSDoc to functions/classes.
- Vue: prefer Composition API, keep SFC structure, define props/emits/exposed explicitly.
- CSS: prefer Tailwind utilities, minimize custom CSS, ensure responsive design.

## Commits
- Follow Conventional Commits (`feat:`, `fix:`, `docs:`, `style:`, `refactor:`, `perf:`, `test:`, `chore:`).
