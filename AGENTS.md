# AGENTS.md

Guidance for AI coding agents working in **Laravel Tools** — an internal admin panel (UI/docs in Bahasa Indonesia) that centralizes external API integrations and custom scripts behind one dashboard. Laravel 13 + Livewire 3 + Volt + Breeze. Author: ERIE PUTRANTO.

Sibling AI configs exist (`CLAUDE.md`, `GEMINI.md`, `PLANNING.md`); this file is the canonical one for Kilo sessions.

## Commands

```bash
composer run setup   # first-time: install + key:generate + migrate + npm install + build
composer run dev     # concurrent: artisan serve + queue:listen + pail + npm run dev
composer run test    # clears config, then PHPUnit

php artisan test --compact tests/Feature/Search/TokopediaSearchTest.php
php artisan test --compact --filter=test_can_search   # run one test after a change

npm run build        # Tailwind/Vite. Run if UI changes aren't reflected (ViteException = build needed)
vendor/bin/pint --dirty --format agent   # run after editing ANY PHP file
```

`composer run test` must run before finalizing — it calls `config:clear` first, so a plain `php artisan test` can miss config-cache issues.

## Architecture

- **Livewire-first.** Nearly all UI logic is in `app/Livewire/<Domain>/` paired with `resources/views/livewire/<domain>/` (kebab-case). Real controllers are only for auth. Default to a Livewire/Volt component, not a controller. `app/Http/Controllers/Generation/` is intentionally empty.
- **Service layer for external APIs.** Each integration is wrapped by a service in `app/Services/<Domain>/` using the `Http` facade. Livewire calls services; services never call Livewire. Use `app/Services/Search/TokopediaSearchService.php` as the canonical pattern (readonly-promoted constructor with `SystemSettings` + `HttpFactory`, constant `API_KEY_NAME`/`ENDPOINT`/`BASE_URL`, timeout/retry from settings, Bahasa Indonesia errors). `PvcCalculator` is the exception — logic lives in the component.
- **Centralized, encrypted API keys (NOT in `.env`).** Keys live in the `api_keys` table (`Crypt::encryptString` via the `ApiKey` model) and are managed in Settings UI. Resolve at runtime with `ApiKey::valueByName($name)` / `ApiKey::findByName($name)`. Known identifiers: `downloader_provider`, `apicoid_provider`, `apifreaks_provider`, `freepik_provider`, `youtubeapi_provider`, `apify_provider`, `freeimage_host`. **Only** ChatBot AI keys (`OPENAI_API_KEY`, `GEMINI_API_KEY`, `ANTHROPIC_API_KEY`, `PERPLEXITY_API_KEY`) come from `.env`.
- **Feature gating.** Freepik-dependent routes (Image AI, Video AI, Freepik Image, Improve Prompt) are gated by `config('services.freepik.enabled')` (env `FREEPIK_ENABLED`, default `false`) via `abort_unless(..., 404)`. Don't assume those pages exist when `FREEPIK_ENABLED=false`.
- **Global settings.** `App\Support\Settings\SystemSettings` is a singleton (registered in `AppServiceProvider`) backed by the `app_settings` table. It reads `config('tools.settings.*')` for `request_timeout_seconds`, `request_retry_times`, `request_retry_sleep_ms`, `queue_connection`. `SystemSettings::get()` throws for undefined keys — define new settings in `config/tools.php` first.
- **Laravel AI SDK (`laravel/ai`).** ChatBot uses `app/Ai/Agents/ChatBotAgent.php` + `app/Services/Ai/` (`ChatResponder`, `LlmCredentialResolver`, `PerplexityClient`). LLM models are per-provider rows in `llm_models` (managed via `LlmModelManager`); only `is_active` ones show in the UI. Perplexity also serves as web-search grounding for other providers.
- **MCP.** `routes/ai.php` registers the Boost MCP endpoint at `/mcp/laravel-boost`.
- **Exports.** `dompdf/dompdf` (PDF) and `phpoffice/phpspreadsheet` (XLSX), e.g. Apify scraper results.

## Testing

- PHPUnit only (no Pest). In-memory SQLite (`:memory:`), array cache, sync queue — see `phpunit.xml`.
- Feature tests mirror the Livewire component structure under `tests/Feature/<Domain>/`. Use model factories; do not create models in tinker without approval. Never remove tests or test files without approval.
- External-API service tests typically use mocked `Http` responses — real providers need paid keys, so don't hit live endpoints in tests.

## Conventions

- UI strings, comments, and docs are primarily **Bahasa Indonesia** — match the surrounding language when editing.
- `temp-laravel13/` is a leftover temp directory; ignore it, it is not project source.
- Don't change dependencies or create new base folders without approval.

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

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.
- To check environment variables, read the `.env` file directly.

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

# Octane

- Octane boots the application once and reuses it across requests, so singletons persist between requests.
- The Laravel container's `scoped` method may be used as a safe alternative to `singleton`.
- Never inject the container, request, or config repository into a singleton's constructor; use a resolver closure or `bind()` instead:

```php
// Bad
$this->app->singleton(Service::class, fn (Application $app) => new Service($app['request']));

// Good
$this->app->singleton(Service::class, fn () => new Service(fn () => request()));
```

- Never append to static properties, as they accumulate in memory across requests.

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
