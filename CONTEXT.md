# CymruQL - Context

Welsh football data (Cymru Premier, Cymru North, Cymru South: 48 clubs) served over both REST and GraphQL from one Laravel app. A React UI loads the same screens from either API and shows what each cost: requests, payload size, timing, DB query count and the raw request or query. Portfolio and learning project. Hobby project with no connection to the FAW, the leagues or any club.

## Runtime

| Item | Value |
|---|---|
| Framework | Laravel 13, PHP 8.4+ |
| Database | MySQL 8, native (WSL, macOS, Linux). Database `cymruql`, created by `php artisan migrate` if missing |
| Tests | Pest 5, in-memory SQLite (`phpunit.xml`), coverage via pcov, 90% minimum on `app/` |
| Front end | Vite (React + TypeScript + Tailwind planned) |
| Containers | None. No Docker, no Makefile |

## Planned architecture

| Area | Location | Notes |
|---|---|---|
| Seed data | `database/data/*.json` | Generated once by `cymru:import` from the Over the Terraces Cymru config, committed |
| Distance matrix | `database/data/distances.json` | 48 x 48 road km (OSRM, precomputed) plus straight-line km. No OSRM calls at runtime |
| Domain services | `app/Services/` | Shared by REST and GraphQL so the comparison is fair |
| REST | `routes/api.php`, API Resources | |
| GraphQL | `graphql/schema.graphql` (Lighthouse) | Schema first, batch loading toggle, complexity and depth limits |
| Live feed | Lighthouse subscriptions over Reverb | Fixture simulator command pushes match events |

## Decisions

| Decision | Why |
|---|---|
| Native runtime, no Docker | Clone, `composer setup`, `composer dev` on any machine with PHP, Node and MySQL |
| `composer setup` / `composer dev` instead of a Makefile | One familiar entry point that works cross-platform |
| Tests on in-memory SQLite, app on MySQL | Fast, isolated tests with no second database to create; MySQL-specific SQL needs care |
| Committed JSON seed data | Seeding needs no network access; external sources are only hit by `cymru:import` |
| Simulated fixtures by default | No API keys needed to run; TheSportsDB is an optional extra |
| Generated placeholder crests | Avoid shipping third-party club badges |

## Commands

**composer**: setup | dev | test
**artisan**: migrate | db:seed | cymru:import (planned) | cymru:reset (planned)
**pest**: ./vendor/bin/pest | ./vendor/bin/pest --coverage --min=90
