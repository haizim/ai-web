# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Laravel 12 app (on top of Laravolt 6 admin framework) that uses LLMs (OpenRouter, Gemini) to generate two kinds of user-owned artifacts from Indonesian-language prompts:

- **Page** — AI-generated static landing page (HTML + Tailwind in `<body>`) published at `/p/{slug}`.
- **MiniApp** — AI-generated single-file interactive HTML app published at `/a/{slug}`.

Both are authored through a CRUD flow plus a set of API endpoints that call the AI services.

## Commands

Backend (PHP 8.2+, Composer 2):

```bash
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate:fresh --seed         # reset DB (default pgsql; sqlite for tests)
php artisan laravolt:link                # publish Laravolt symlinks (one-off per install)
php artisan vendor:publish --tag=laravolt-assets
php artisan serve
composer dev                             # runs serve + queue + pail + vite concurrently
```

Frontend (Vite + Tailwind v4):

```bash
npm install
npm run dev
npm run build
```

Quality gates:

```bash
composer test                            # Pest via `php artisan test` (with config:clear)
./vendor/bin/pest tests/Feature/AuthenticateMiddlewareTest.php  # single file
./vendor/bin/pest --filter=testName      # single test
./vendor/bin/pint                        # formatter (Laravel Pint)
./vendor/bin/phpstan analyse             # level 8, see phpstan.neon.dist
```

PHPStan is strict (level 8) and **disallows `env()`, `dd()`, `dump()`, `var_dump()`, `print_r()`, `exit()`, `die()`** in `app/` — use `config()` and `Log`/`logger()` instead. Check `phpstan.neon.dist` before adding debug calls.

Tests run against in-memory SQLite (see `phpunit.xml`); do not rely on the dev pgsql schema in tests.

## Architecture

### AI Pipeline (`app/Services/`)

Three-layer stack — do not bypass:

1. **`SystemPrompt`** — static methods returning long Indonesian system prompts for each capability (`generatePage`, `editPage`, `generateStyle`, `generateStyleFromDesc`, `generateMiniApp`, `editMiniApp`, `generateFunctionality`, `generateMiniAppStyle…`). Prompts encode hard rules the model must follow (e.g. page output must be body-only HTML, Tailwind-only, responsive, semantic).
2. **`AiAgent`** — task-level facade. Packs user input into a JSON payload, pairs it with the right system prompt, and calls the provider. Each method targets one capability.
3. **`OpenRouter`** (primary) / **`Gemini`** — provider clients. `OpenRouter::generate($prompt, $system, $model)` → `['model','response','token','data']`. Default model comes from `config('ai.openrouter.model')`; some `AiAgent` methods hardcode alternate models (e.g. `z-ai/glm-4.7`, `x-ai/grok-code-fast-1`) — preserve those overrides unless intentionally changing them. Keys live in `config/ai.php` (reads `OPENROUTER_KEY`, `GEMINI_KEY`, etc.).

When adding a new AI capability: add a prompt method in `SystemPrompt`, a wrapper in `AiAgent`, then an API endpoint — don't call `OpenRouter` directly from controllers.

### HTTP surface

- `routes/web.php` — auth-gated CRUD (`page`, `miniapp`, `users-custom`) plus public `/p/{slug}` and `/a/{slug}` (only shows rows with `status = 'AKTIF'`). Root redirects to `/auth/login`.
- `routes/api.php` — stateless AI endpoints consumed by the edit/create Blade views via JS (`/api/generate`, `/api/edit-page`, `/api/generate-style`, `/api/generate-preview`, and `…-miniapp…` equivalents). Incoming HTML is converted to Markdown via `league/html-to-markdown` before being sent to the model.
- `routes/my.php` — user profile/password (Laravolt convention with `my::` route names).
- `routes/auth.php` — included from `web.php`, provides Laravolt auth flows.

### Models

`Page` and `MiniApp` are near-identical: soft-deleted, `user_id`-owned, with `images` and `chats` cast to `array`. Edit/destroy controllers authorize via `user_id === auth()->user()->id` (403 otherwise). `destroy()` toggles `status` between `AKTIF`/`DRFT` rather than deleting — "hapus" in this codebase means soft-deactivate.

### Livewire tables

`app/Livewire/Table/{PageTable,MiniAppTable}.php` extend `Laravolt\Ui\TableView` and scope `Page::query()` / `MiniApp::query()` to the current user. Index views render them via `@livewire(…)` inside `<x-volt-app>`. The empty `app/Http/Livewire/Table/` directory is legacy — new tables go in `app/Livewire/Table/`.

### Views

Blade views use Laravolt's `x-volt-*` components (`x-volt-app`, `x-volt-link-button`, etc.). `resources/views/page/` contains `*-old*.blade.php` leftover variants — use the canonical `create.blade.php` / `edit.blade.php`. `resources/views/vendor/` holds Laravolt overrides.

### Bootstrapping (`bootstrap/app.php`)

- Adds `Laravolt\Middleware\DetectFlashMessage` and `CheckPassword` to the `web` group.
- Overrides: `TokenMismatchException` → `back()` with flash; `AuthenticationException` → JSON 401 or redirect to `auth::login.show`; `AuthorizationException` → JSON 403 / Livewire 403 / redirect.
- Reports exceptions to Sentry if bound. Health check at `/up`.

### Config highlights

- `config/ai.php` — AI keys/models; `provider` selects default (default `gemini`).
- `config/laravolt/` — menu (`menu/menu.php`, `menu/system.php`), permissions, UI.
- `.env.example` defaults DB to Postgres (`dvg`); feature/unit tests use sqlite `:memory:`.

## Conventions

- User-facing strings and prompts are Indonesian; keep that language when touching flash messages, prompts, and UI copy.
- Use `config()` not `env()` outside of config files (PHPStan will fail otherwise).
- New AI calls should always flow `SystemPrompt → AiAgent → provider` so prompts stay centralized.
- `PageController::store()` currently writes the record without calling the AI — generation is triggered by `ApiController` endpoints invoked from the client. The commented `store_old()` shows the previous one-shot flow; do not resurrect it without understanding the two-step flow in the edit view.

<!-- code-review-graph MCP tools -->
## MCP Tools: code-review-graph

**IMPORTANT: This project has a knowledge graph. ALWAYS use the
code-review-graph MCP tools BEFORE using Grep/Glob/Read to explore
the codebase.** The graph is faster, cheaper (fewer tokens), and gives
you structural context (callers, dependents, test coverage) that file
scanning cannot.

### When to use graph tools FIRST

- **Exploring code**: `semantic_search_nodes` or `query_graph` instead of Grep
- **Understanding impact**: `get_impact_radius` instead of manually tracing imports
- **Code review**: `detect_changes` + `get_review_context` instead of reading entire files
- **Finding relationships**: `query_graph` with callers_of/callees_of/imports_of/tests_for
- **Architecture questions**: `get_architecture_overview` + `list_communities`

Fall back to Grep/Glob/Read **only** when the graph doesn't cover what you need.

### Key Tools

| Tool | Use when |
|------|----------|
| `detect_changes` | Reviewing code changes — gives risk-scored analysis |
| `get_review_context` | Need source snippets for review — token-efficient |
| `get_impact_radius` | Understanding blast radius of a change |
| `get_affected_flows` | Finding which execution paths are impacted |
| `query_graph` | Tracing callers, callees, imports, tests, dependencies |
| `semantic_search_nodes` | Finding functions/classes by name or keyword |
| `get_architecture_overview` | Understanding high-level codebase structure |
| `refactor_tool` | Planning renames, finding dead code |

### Workflow

1. The graph auto-updates on file changes (via hooks).
2. Use `detect_changes` for code review.
3. Use `get_affected_flows` to understand impact.
4. Use `query_graph` pattern="tests_for" to check coverage.
