# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Laravel Tools** is an internal admin panel (UI text and docs are in Bahasa Indonesia) that centralizes external API integrations and custom scripts behind one dashboard. Built on Laravel 13 + Livewire 3 + Volt, auth via Laravel Breeze. Author: ERIE PUTRANTO.

## Common Commands

```bash
# Full first-time setup (install + migrate + build)
composer run setup

# Concurrent dev: php artisan serve + queue:listen + pail logs + npm run dev
composer run dev

# Tests (clears config, then runs PHPUnit). Tests use in-memory SQLite.
composer run test

# Run a single test file / filter
php artisan test --compact tests/Feature/Search/TokopediaSearchTest.php
php artisan test --compact --filter=test_can_search

# Frontend build (Tailwind/Vite). Run if UI changes aren't reflected.
npm run build
npm run dev

# Code formatting (run after editing any PHP file)
vendor/bin/pint --dirty --format agent

# Discover commands / inspect routes
php artisan list
php artisan route:list --except-vendor
```

## Architecture

### Livewire-first (controllers are minimal)
Nearly all UI logic lives in `app/Livewire/<Domain>/` components paired with Blade in `resources/views/livewire/<domain>/` (kebab-case). Traditional controllers only exist for auth (`app/Http/Controllers/Auth/VerifyEmailController.php`). Volt is mounted at `resources/views/livewire` and `resources/views/pages` (see `VoltServiceProvider`). When adding a feature, default to a Livewire/Volt component, not a controller.

### Service layer for external APIs
Each third-party integration is wrapped by a service class in `app/Services/<Domain>/` that uses Laravel's `Http` facade. Livewire components call services; services never call Livewire. Patterns to follow: `app/Services/Search/TokopediaSearchService.php`, `app/Services/ApiFreaks/ApiFreaksService.php` (base class for that domain). The `PvcCalculator` tool is an exception — its logic lives directly in the Livewire component.

### Centralized, encrypted API key storage
Third-party API keys are **not** read from `.env`. They are stored encrypted in the `api_keys` table (`Crypt::encryptString` via `ApiKey` model accessor/mutator) and managed through the Settings UI (`app/Livewire/Settings/ApiKeyManager.php`). Resolve keys at runtime with `ApiKey::valueByName($name)` / `ApiKey::findByName($name)`. Known identifiers: `downloader_provider`, `apicoid_provider`, `apifreaks_provider`, `freepik_provider`, `youtubeapi_provider`, `apify_provider`, `freeimage_host`. **Only** ChatBot AI provider keys (OpenAI/Gemini/Anthropic/Perplexity) come from `.env`.

### Feature gating
Freepik-dependent features (Image AI, Video AI, Freepik Image search) are gated by `config('services.freepik.enabled')` (env `FREEPIK_ENABLED`, defaults `false`). Routes use `abort_unless(config('services.freepik.enabled'), 404)`.

### Laravel AI SDK (`laravel/ai`)
Installed with its own config (`config/ai.php`), migrations extending `Laravel\Ai\Migrations\AiMigration` (`agent_conversations`, `agent_conversation_messages`), and stubs in `stubs/`. The ChatBot uses `app/Ai/Agents/ChatBotAgent.php` and `app/Services/Ai/` (`ChatResponder`, `LlmCredentialResolver`, `PerplexityClient`). LLM models are configurable per-provider via the `llm_models` table and `app/Livewire/Settings/LlmModelManager.php`.

### Config-driven modules
`config/tools.php` holds tool-wide settings (request timeout, retry count/sleep, queue connection) and WhatsApp config. New API menu modules are added via configuration rather than code changes — follow existing module conventions in `routes/web.php` and `app/Livewire/`.

### Singletons & support
`App\Support\Settings\SystemSettings` is registered as a singleton in `AppServiceProvider`. `app/Models/AppSetting.php` backs global key/value settings.

### MCP
`routes/ai.php` registers the Laravel Boost MCP web endpoint at `/mcp/laravel-boost`. The Boost MCP server (`laravel/boost`) provides database, logs, and version-specific docs tooling — see the guidelines below.

### Exports
`dompdf/dompdf` (PDF) and `phpoffice/phpspreadsheet` (XLSX) are used for export, e.g. Apify scraper results.

## Testing Notes
- PHPUnit only (no Pest). Tests use in-memory SQLite, array cache, sync queue.
- Feature tests mirror the Livewire component structure under `tests/Feature/<Domain>/`.
- Use model factories when creating test data; do not create models in tinker without approval.
- Do not remove tests or test files without approval.

## Conventions Specific to This Repo
- UI strings, comments, and documentation are primarily in **Bahasa Indonesia** — match the surrounding language when editing.
- `Generation/` controller directory exists but is intentionally empty (logic is in Livewire).
- `temp-laravel13/` is a leftover temp directory — do not treat it as project source.
- Multiple sibling AI-assistant config files exist (`AGENTS.md`, `GEMINI.md`, `PLANNING.md`); this file is the canonical one for Claude Code.

---

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.3
- laravel/ai (AI) - v0
- laravel/framework (LARAVEL) - v13
- laravel/octane (OCTANE) - v2
- laravel/prompts (PROMPTS) - v0
- livewire/livewire (LIVEWIRE) - v3
- livewire/volt (VOLT) - v1
- laravel/boost (BOOST) - v2
- laravel/breeze (BREEZE) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- phpunit/phpunit (PHPUNIT) - v12
- tailwindcss (TAILWINDCSS) - v3

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== octane/core rules ===

# Laravel Octane

This application uses Laravel Octane, a long-running PHP server. The application bootstraps once and handles many requests within the same process.

- Never store request-specific state in singletons or static properties, because it can leak across requests.
- Use `config('octane.server')` to detect the active driver (`swoole`, `roadrunner`, or `frankenphp`).
- Prefer scoped bindings (`$this->app->scoped()`) over singletons for per-request services.

When working on Octane-specific features (concurrency, shared tables, memory, driver configuration, testing), invoke `octane-development` for detailed rules.

=== livewire/core rules ===

# Livewire

- Livewire allow to build dynamic, reactive interfaces in PHP without writing JavaScript.
- You can use Alpine.js for client-side interactions instead of JavaScript frameworks.
- Keep state server-side so the UI reflects it. Validate and authorize in actions as you would in HTTP requests.

=== volt/core rules ===

# Livewire Volt

- Single-file Livewire components: PHP logic and Blade templates in one file.
- Always check existing Volt components to determine functional vs class-based style.
- IMPORTANT: Always use `search-docs` tool for version-specific Volt documentation and updated code examples.
- IMPORTANT: Activate `volt-development` every time you're working with a Volt or single-file component-related task.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit {name}` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should cover all happy paths, failure paths, and edge cases.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files; these are core to the application.

## Running Tests

- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test --compact`.
- To run all tests in a file: `php artisan test --compact tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --compact --filter=testName` (recommended after making a change to a related file).

</laravel-boost-guidelines>
