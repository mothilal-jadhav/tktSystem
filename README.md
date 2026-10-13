# tktSystem

An internal helpdesk and ticketing application, built one feature at a time with Laravel, React, and Inertia.js.

The project has two goals: to end up as a secure, maintainable helpdesk, and to be a hands-on way to learn Laravel, PHP, React, TypeScript, authentication, authorization, and web application security.

> **Status:** early development. The app currently has a dashboard UI driven by **sample data**. Authentication, tickets, and the database-backed features are not built yet. See [Roadmap](#roadmap).

## Tech stack

| Layer       | Technology                                             |
| ----------- | ------------------------------------------------------ |
| Backend     | PHP 8.3+, Laravel 13                                   |
| Frontend    | React 19, TypeScript                                   |
| Integration | Inertia.js v3, Laravel Wayfinder (typed route helpers) |
| Styling     | Tailwind CSS v4                                        |
| Database    | MySQL (development/production), SQLite (CI)            |
| Testing     | Pest 4                                                 |
| Tooling     | Vite Plus, Laravel Pint, Larastan (PHPStan), `tsc`     |

### How the pieces fit together

- **Laravel** owns routing, business logic, validation, authentication, and authorization.
- **Inertia** connects Laravel controllers to React pages: a controller returns `Inertia::render('page', $props)` and the matching component in `resources/js/pages/` renders with those props. There is no separate JSON API.
- **React** handles presentation and interaction only. Frontend visibility checks are never treated as a security boundary; all authorization will be enforced on the server with Policies and Gates.

## What's been built so far

### 1. Project setup

- Laravel + React + Inertia starter, with Tailwind, Pest, Pint, Larastan, and Wayfinder.
- `composer setup` installs dependencies, creates `.env`, generates the app key, runs migrations, and builds the frontend.
- GitHub Actions CI (`.github/workflows/tests.yml`) runs on every push to `main` and every pull request. It sets up an SQLite database and runs `composer ci:check` (frontend lint/format, TypeScript check, Pint, PHPStan, and the Pest suite).

### 2. Dashboard (sample data)

The home route (`/`, named `dashboard`) is served by an invokable `DashboardController`, which renders the `dashboard` Inertia page.

The dashboard includes:

- **Six stat cards**: open tickets, pending, resolved today, average first response, SLA breaches, and CSAT. Each has a tilted icon and a green/red "since last month" badge.
- **Created vs. resolved bar chart** for the last 12 days, drawn as plain SVG. The y-axis rounds to multiples of 10, and hovering a bar shows its value.
- **Resolution-rate ring gauge.**
- **Tickets by department**: horizontal bars, each sized relative to the busiest department.
- **Recent tickets table** with coloured priority and status labels.

Layout and UI:

- An app layout with a sidebar, top bar, and responsive mobile navigation. Nav items for features that don't exist yet (Tickets, Departments, Users, Settings) are shown without links.
- A **dark mode toggle**. The choice is saved in `localStorage`, and a small inline script in `app.blade.php` applies the theme before the page paints, so there is no white flash on load. If nothing is saved, it follows the OS preference.
- A small hand-written SVG icon set (`resources/js/components/icons.tsx`). No icon library was added.

Data shape:

- The props are typed in `resources/js/types/dashboard.ts` (`DashboardProps`). If the backend changes the shape of the data, `tsc` flags the mismatch.
- The values come from a private `sampleData()` method in the controller. They are placeholders with no real data, and will be replaced by Eloquent queries scoped to the signed-in user's role and department once tickets exist.

## Project structure

```
app/Http/Controllers/DashboardController.php   Dashboard controller (sample data for now)
routes/web.php                                 Web routes
resources/views/app.blade.php                  Root HTML shell + theme bootstrap script
resources/js/app.tsx                           Inertia/React entry point
resources/js/layouts/app-layout.tsx            Sidebar, top bar, mobile nav, dark mode toggle
resources/js/pages/dashboard.tsx               Dashboard page
resources/js/components/dashboard/             Card, stat card, bar chart, ring gauge,
                                               department bars, recent tickets table
resources/js/components/icons.tsx              Inline SVG icons
resources/js/types/dashboard.ts                Dashboard prop types
tests/Feature/DashboardTest.php                Dashboard feature tests
```

## Getting started

### Prerequisites

- PHP 8.3+ and Composer
- Node.js 22+ and npm
- MySQL (or SQLite for a quick local setup)

### Install

```bash
git clone <repo-url> tktSystem
cd tktSystem
composer setup
```

`composer setup` copies `.env.example` to `.env`. Before (or after) running it, set your database credentials in `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tktsystem
DB_USERNAME=your_user
DB_PASSWORD=your_password
```

To use SQLite instead, set `DB_CONNECTION=sqlite`, run `touch database/database.sqlite`, and then `php artisan migrate`.

### Run in development

```bash
composer dev
```

This starts the Laravel server and the Vite dev server together. Open the `APP_URL` from `.env` (default `http://localhost:8000`).

## Testing and quality checks

| Command                | What it does                                                       |
| ---------------------- | ------------------------------------------------------------------ |
| `php artisan test`     | Run the Pest test suite                                            |
| `composer test`        | Pint format check + PHPStan + Pest                                 |
| `composer lint`        | Auto-format PHP with Pint                                          |
| `composer types:check` | Static analysis with Larastan/PHPStan                              |
| `npm run check`        | Frontend lint and format check (Vite Plus)                         |
| `npm run check:fix`    | Auto-fix frontend lint/format issues                               |
| `npm run types:check`  | TypeScript type check (`tsc --noEmit`)                             |
| `composer ci:check`    | Everything CI runs: frontend checks, TS types, and `composer test` |

### Current test coverage

`tests/Feature/DashboardTest.php` checks that:

- the home page renders the `dashboard` Inertia component;
- the dashboard receives every expected prop with the right types (6 stats, 12 days of chart data, a resolution rate between 0 and 100, department counts, and recent tickets);
- the props never include sensitive fields such as `password`, `remember_token`, `email`, or `internal_note`.

A pending (`todo`) test is in place for redirecting guests to the login page. It will be filled in once authentication exists.

## Security notes

- The dashboard route is **not protected yet**. A `TODO` in `routes/web.php` marks where the `auth` middleware goes once login is built. This is safe for now only because the dashboard shows placeholder data.
- A test already makes sure dashboard props don't leak sensitive fields. This will matter once real data is wired in.
- Planned rules for every feature: validate all input on the server, enforce authorization with Policies/Gates, guard against IDOR, never trust client-supplied IDs, roles, or ownership, keep internal notes and private attachments hidden from unauthorized users, and make sure uploaded files can never be executed.

## Roadmap

- [x] Project setup, tooling, and CI
- [x] Dashboard UI (sample data), layout, and dark mode
- [ ] Authentication and user management
- [ ] Departments and roles (requester, agent, department lead, administrator)
- [ ] Ticket creation, listing, details, and updates
- [ ] Ticket assignment and status management
- [ ] Role-based and resource-level authorization
- [ ] Public comments and private internal notes
- [ ] Secure attachment uploads and downloads
- [ ] Server-side search, filtering, sorting, and pagination
- [ ] Dashboard backed by real data (statistics, response times, SLA breaches)
- [ ] Embeddable JavaScript widget for submitting tickets
- [ ] Logging, notifications, and production hardening

## Development workflow

- One small feature at a time, each on its own feature branch (e.g. `feature/dashboard`), merged through a pull request.
- Every feature ships with automated tests, including negative authorization tests for protected resources.
- Run `composer ci:check` and review `git diff` before committing.
- Never commit secrets or real user data.

See [`CLAUDE.md`](CLAUDE.md) for the full project rules and definition of done.
