# CymruQL

<img width="3148" height="1652" alt="Screenshot 2026-11-11 211307" src="https://github.com/user-attachments/assets/22f0041b-68fd-4f3c-9396-761d12b04cca" />


Welsh football data (Cymru Premier, North and South) served over both REST and GraphQL from one Laravel 13 app, with a React UI that shows what each API costs side by side: requests, payload, timing and database queries.

## What this demonstrates

Most REST versus GraphQL comparisons are arguments. This one is a measurement. Every screen in the app (club, league table, ground) can load its data from either API, and a scoreboard beside it shows what that load cost. Toggle the API and the other side of the scoreboard fills in.

Loading the Caernarfon Town page, on seeded local data:

| | REST | GraphQL |
|---|---|---|
| HTTP requests | 20 | 1 |
| Data received | 20.9 kB | 8.0 kB |
| Database queries | 52 | 19 |
| Database queries, batch loading off | | 49 |

The screen is identical in both modes. Each API is used the way it is meant to be used: REST resources reference related resources by link, so the client follows those links (club, then its seasons, then its ground, table and away distances, then each away ground); GraphQL asks for the whole shape in one query. Both sides call the same Laravel service classes, so the difference is the API, not the code behind it.

Along the way the project covers:

- **Schema-first GraphQL with Lighthouse**: relation directives with batch loading, `@paginate`, `@find`, custom resolvers over shared services, query complexity and depth limits, PHP enums as GraphQL enums
- **The N+1 problem, made visible**: a switch turns Lighthouse's batch loading off for a single request so you can watch the query count climb
- **Instrumenting both APIs fairly**: a middleware adds query count and timing to REST headers and to GraphQL `extensions`, and one front end transport records every request either client makes
- **GraphQL subscriptions over WebSockets**: an artisan command plays fixtures live and pushes goals and score changes through Laravel Reverb
- **Typed front end**: `graphql-codegen` generates TypeScript types from the Lighthouse schema, and a test fails if they drift
- **A deterministic data pipeline**: clubs, grounds and a road distance matrix imported once from external sources and committed as JSON, with simulated results that are identical on every install

## Tech stack

| Area | Choice |
|---|---|
| Framework | Laravel 13, PHP 8.4 |
| Database | MySQL 8, running natively (tests use in-memory SQLite) |
| GraphQL | [Lighthouse](https://lighthouse-php.com) 6, [GraphiQL](https://github.com/mll-lab/laravel-graphiql) playground |
| REST | Laravel controllers and API Resources |
| Real time | Laravel Reverb, Lighthouse subscriptions, `pusher-js` on the client |
| Front end | React 19, TypeScript, Tailwind CSS 4, Vite, React Router |
| Data fetching | urql (GraphQL), TanStack Query (REST) |
| Code generation | graphql-codegen client preset |
| Quality | Pest, Larastan, Pint, ESLint, Playwright, Husky git hooks |
| Runtime | Native: no Docker. `composer dev` runs everything together |

## Getting started

### Prerequisites

- PHP 8.4+ with `pdo_mysql` (and `pcov` for coverage), Composer
- Node 20+ and npm
- MySQL 8 running locally (WSL, macOS or Linux). Laravel Herd on Windows also works, but WSL is the documented path
- [osv-scanner](https://github.com/google/osv-scanner), used by the pre-commit hook: `go install github.com/google/osv-scanner/cmd/osv-scanner@latest`
- For the Claude Code MCP servers in `.mcp.json` (optional):
  - GitHub MCP authenticates with `gh auth token`; run `gh auth login` once per machine
  - MySQL MCP reads `MYSQL_HOST`, `MYSQL_PORT`, `MYSQL_USER`, `MYSQL_PASSWORD` and `MYSQL_DATABASE` from your shell (defaults to `127.0.0.1:3306`, user `root`); export values matching your `.env`
  - Playwright and Context7 need nothing beyond `npx`

### Run it

1. Clone the repo and install everything:

   ```bash
   git clone https://github.com/phoenix1331/cymru-ql
   cd cymru-ql
   composer setup
   ```

   `composer setup` installs PHP and Node dependencies, creates `.env` from `.env.example`, generates the app key, migrates and seeds the database (creating `cymruql` if it does not exist), activates the git hooks and builds the front end. If your MySQL user has a password, set `DB_PASSWORD` in `.env` and run it again.

2. Start the app:

   ```bash
   composer dev
   ```

   This runs the web server on http://localhost:8000, Reverb on port 8080, the queue worker, the live match simulator and Vite, together.

3. Open http://localhost:8000, pick a league, then a club, and toggle between REST and GraphQL in the panel on the right. http://localhost:8000/graphiql has example queries ready to run, and http://localhost:8000/live shows the simulated matches in play.

No API keys or external services are needed to run it.

### Other commands

| Command | What it does |
|---|---|
| `php artisan cymru:reset` | Wipe and reseed the database, resimulating results up to now (`--as-of=2026-10-01` to pick a date) |
| `php artisan cymru:simulate` | Play the next round live (`--tick=1` seconds per match minute, `--pause=30` between rounds, `--rounds=0` for no limit) |
| `php artisan cymru:import` | Regenerate `database/data/*.json` from the Over the Terraces config (`--enrich` for Wikidata and OpenStreetMap, `--distances` for the OSRM matrix) |
| `composer test` | Pest |
| `composer test:coverage` | Pest with coverage, 90% minimum on `app/` |
| `composer lint` / `composer analyse` | Pint / Larastan |
| `npm run lint` / `npm run typecheck` | ESLint / TypeScript |
| `npm run codegen` | Print the Lighthouse schema and regenerate the front end GraphQL types |
| `npm run test:e2e` | Playwright smoke tests (against `php artisan serve`, seeded database) |

## Environment variables

Everything in `.env.example` works as it is for local development except your MySQL credentials.

| Key | Default | Description |
|---|---|---|
| `DB_HOST`, `DB_PORT` | `127.0.0.1`, `3306` | MySQL server |
| `DB_DATABASE` | `cymruql` | Created by `migrate` if it does not exist |
| `DB_USERNAME`, `DB_PASSWORD` | `root`, empty | Your MySQL credentials |
| `CYMRU_METRICS` | `true` | Query count and timing headers on REST and `extensions.metrics` on GraphQL |
| `CYMRU_BATCHLOAD_TOGGLE` | on outside production | Allow `X-Batchload: off` on GraphQL requests (the N+1 demo) |
| `CYMRU_SIMULATION_SEED` | `1331` | Seed for simulated results; change it for a different season |
| `CYMRU_SIMULATE_AS_OF` | empty (now) | Fixtures before this moment are simulated as played when seeding |
| `LIGHTHOUSE_MAX_QUERY_COMPLEXITY` | `1000` | GraphQL complexity limit |
| `LIGHTHOUSE_MAX_QUERY_DEPTH` | `8` | GraphQL depth limit (introspection is exempt) |
| `LIGHTHOUSE_BROADCASTER` | `reverb` | Where subscription updates are pushed |
| `LIGHTHOUSE_SUBSCRIPTION_STORAGE` | `database` | Cache store holding subscribers (no Redis needed) |
| `REVERB_APP_ID`, `REVERB_APP_KEY`, `REVERB_APP_SECRET` | local placeholders | Reverb credentials; use random values anywhere public |
| `REVERB_HOST`, `REVERB_PORT`, `REVERB_SCHEME` | `localhost`, `8080`, `http` | Where Reverb listens (mirrored into `VITE_REVERB_*` for the browser) |
| `GRAPHIQL_ENABLED` | `true` | Serve the playground at `/graphiql` |
| `OTT_CYMRU_PATH` | empty | Over the Terraces `cymru.js`, only for `cymru:import` |
| `CYMRU_HTTP_USER_AGENT` | `laravel-data-import/1.0` | User agent for Wikidata and Nominatim during `cymru:import --enrich` |

## Endpoints

Every request below is in [`request.http`](request.http), ready for the VS Code REST Client or JetBrains HTTP Client.

### REST (`/api`)

| Endpoint | Returns |
|---|---|
| `GET /api/seasons` | Seasons, newest first |
| `GET /api/leagues`, `GET /api/leagues/{league}` | The three leagues |
| `GET /api/leagues/{league}/table?season=` | League table (`meta.basis` is `results` or `final_positions`) |
| `GET /api/clubs?league=&season=&per_page=` | Clubs, paginated |
| `GET /api/clubs/{club}`, `GET /api/clubs/{club}/seasons` | A club and its league history |
| `GET /api/grounds`, `GET /api/grounds/{ground}` | Grounds and the clubs that play there |
| `GET /api/grounds/{ground}/nearby?limit=&league=&season=` | Nearest grounds by road |
| `GET /api/grounds/{ground}/distances?league=&season=` | Every ground in a league, nearest first, with a total |
| `GET /api/grounds/{from}/distances/{to}` | One directed distance |
| `GET /api/fixtures?season=&league=&club=&status=&from=&to=&round=` | Fixtures, paginated |
| `GET /api/fixtures/{id}`, `GET /api/fixtures/{id}/events` | A fixture and its match events |

Every response carries `X-Query-Count`, `X-Query-Time-Ms`, `X-Response-Time-Ms` and `Server-Timing` headers.

### GraphQL (`/graphql`)

The schema lives in [`graphql/schema.graphql`](graphql/schema.graphql). Top-level fields: `seasons`, `season`, `leagues`, `league(slug, season)`, `table(league, season)`, `club`, `clubs`, `ground`, `grounds`, `nearbyGrounds`, `fixtures`, `fixture`, and the subscription `matchUpdated(fixtureId)`. The showcase query, a club with its ground, its league table and the road distance to every other ground in its league, is the first tab in GraphiQL and in [`graphql/examples/`](graphql/examples).

Send `X-Batchload: off` to resolve relations without batch loading, and read `extensions.metrics` for the query count either way.

## Architecture notes

**Two APIs, one set of services.** `app/Services/` holds the logic both APIs need: table calculation, nearby grounds, fixture filtering. REST controllers and GraphQL resolvers call the same methods, and simple lookups (`@find`, `@paginate`) issue the same Eloquent queries a service would. If the numbers differ, it is because of how each API is shaped, not because one side was given a better implementation.

**Measuring without favouring either side.** Both front end clients fetch through a single instrumented transport that records bytes, timing and the server's query count for every request. Neither client caches across screen loads, so revisiting a screen is a real round trip rather than a misleading zero. Requests are filed under the load that was active when they started, so a slow response from a screen you just left cannot inflate the next one.

**One view, two data hooks.** Each screen has a view model and a component that renders it, fed by either a REST hook or a GraphQL hook. That keeps the comparison honest: if REST needs one extra request per away ground to show which clubs play there, that is what it does, and the scoreboard shows it.

**Batch loading as a switch.** Lighthouse reads `batchload_relations` while building the schema for each request, after route middleware has run. A small middleware flips it per request when `X-Batchload: off` is sent, which takes a query for the Cymru Premier clubs with their grounds and seasons from 10 database queries to 116. This relies on the schema being built per request (PHP-FPM, `php artisan serve`); it would need a different approach under Octane.

**Query limits that do not break tooling.** Complexity and depth limits reject abusive queries, but graphql-php counts introspection towards depth, and GraphiQL's own introspection query is deeper than any real query. A small subclass exempts `__schema` and `__type`, the same way the complexity rule already does.

**Committed data, no network at runtime.** Clubs, grounds and coordinates come from the Over the Terraces Cymru config, enriched once from Wikidata and OpenStreetMap, with a 47 by 47 road distance matrix from one OSRM request (48 clubs, one shared ground). All of it is committed under `database/data/`, so seeding needs no network access. Hand-maintained corrections live in `overrides.json`, for example where Wikidata gives today's Rhyl the founding year of the club that folded in 2020.

**Deterministic simulation.** Results are simulated from club ratings (based on the previous season's finish) with a seed derived from the season and the pairing, so every install sees the same season and a live match ends exactly as the bulk simulation would. Only the as-of date decides which fixtures have been played.

**Live without Redis.** Subscriptions broadcast through Reverb, with subscribers stored in the database cache. Laravel 13 refuses to unserialise cached objects by default, so `config/cache.php` allow-lists exactly the two classes Lighthouse stores. The simulator publishes directly rather than queuing a broadcast per match minute.

**Native by design.** The brief was clone, `composer setup`, `composer dev`, with nothing but PHP, Node and MySQL. CI follows the same path on a plain Ubuntu runner using its preinstalled MySQL, then runs Playwright against `php artisan serve`.

## Data and credits

| Data | Source | Licence |
|---|---|---|
| Clubs, grounds, coordinates, capacities, descriptions | Over the Terraces Cymru map (same author) | Original compilation |
| Founded years, some ground details | [Wikidata](https://www.wikidata.org) | CC0 |
| Ground addresses | [OpenStreetMap](https://www.openstreetmap.org/copyright) contributors | ODbL |
| 2025/26 final positions | Wikipedia season articles | CC BY-SA 4.0 |
| Road distances | [OSRM](https://project-osrm.org) over OpenStreetMap data | ODbL |
| Fixtures and results | Simulated | |
| Crests | Generated placeholders, no real club badges | |

Full details are in [`database/data/SOURCES.md`](database/data/SOURCES.md).

This is a hobby project with no connection to the Football Association of Wales, the Cymru Leagues or any club.
