# tktSystem

An internal helpdesk and ticketing application, built one feature at a time with Laravel, React, and Inertia.js.

The project has two goals: to end up as a secure, maintainable helpdesk, and to be a hands-on way to learn Laravel, PHP, React, TypeScript, authentication, authorization, and web application security.

> **Status:** early development. The app has email/password login and logout, a way for signed-in users to create accounts (with a forced password change on first login), and a dashboard UI driven by **sample data**. Roles, tickets, and the other database-backed features are not built yet. See [Roadmap](#roadmap).

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
- **React** handles presentation and interaction only. Frontend visibility checks are never treated as a security boundary; all authorization is enforced on the server (middleware today, Policies and Gates once roles exist).
- **Wayfinder** generates typed route helpers (`@/routes/...`) from the Laravel routes, so React pages never hard-code URLs. Forms use Inertia's `<Form>` component with `store.form()`-style helpers.

## What's been built so far

### 1. Project setup

- Laravel + React + Inertia starter, with Tailwind, Pest, Pint, Larastan, and Wayfinder.
- `composer setup` installs dependencies, creates `.env`, generates the app key, runs migrations, and builds the frontend.
- GitHub Actions CI (`.github/workflows/tests.yml`) runs on every push to `main` and every pull request. It sets up an SQLite database and runs `composer ci:check` (frontend lint/format, TypeScript check, Pint, PHPStan, and the Pest suite).

### 2. Dashboard (sample data)

The dashboard lives at `/dashboard` (named `dashboard`), requires a signed-in user, and is served by an invokable `DashboardController`, which renders the `dashboard` Inertia page.

The dashboard includes:

- **Six stat cards**: open tickets, pending, resolved today, average first response, SLA breaches, and CSAT. Each has a tilted icon and a green/red "since last month" badge.
- **Created vs. resolved bar chart** for the last 12 days, drawn as plain SVG. The y-axis rounds to multiples of 10, and hovering a bar shows its value.
- **Resolution-rate ring gauge.**
- **Tickets by department**: horizontal bars, each sized relative to the busiest department.
- **Recent tickets table** with coloured priority and status labels.

Layout and UI:

- An app layout with a sidebar, top bar, and responsive mobile navigation. The signed-in user's name and initial appear in the sidebar and top bar, and the top bar has a sign-out button. Nav items for features that don't exist yet (Tickets, Settings) are shown without links and marked "soon".
- A **dark mode toggle**. The choice is saved in `localStorage`, and a small inline script in `app.blade.php` applies the theme before the page paints, so there is no white flash on load. If nothing is saved, it follows the OS preference.
- A small hand-written SVG icon set (`resources/js/components/icons.tsx`). No icon library was added.

Data shape:

- The props are typed in `resources/js/types/dashboard.ts` (`DashboardProps`). If the backend changes the shape of the data, `tsc` flags the mismatch.
- The values come from a private `sampleData()` method in the controller. They are placeholders with no real data, and will be replaced by Eloquent queries scoped to the signed-in user's role and department once tickets exist.

### 3. Registration (built, then removed)

A public registration page was first built on `/`. Because tktSystem is an internal helpdesk, open sign-up was then removed completely: the controller, form request, page, and tests were deleted, and `/register` now returns 404 (a test checks this). The unused starter `welcome` page was deleted at the same time.

### 4. Login and logout

- `/` (named `login`) shows the login page for guests: email, password, and a "Remember me" checkbox. Guests who open a protected page are redirected here, and signed-in users who open it are redirected to the dashboard.
- `POST /login` is handled by `AuthenticatedSessionController` and validated by `LoginRequest`:
    - `email` is required and must be a valid email; `password` is required; `remember` must be a boolean.
    - `Auth::attempt()` checks the credentials. An unknown email and a wrong password get the **same** generic error ("These credentials do not match our records."), so the form can't be used to discover which emails have accounts.
    - Failed attempts are rate limited per email + IP. After 5 failures, sign-in is locked with a "Too many login attempts… N seconds" message, even for the correct password. A successful login clears the counter.
    - On success the session ID is regenerated (prevents session fixation) and the user is sent to the page they originally asked for, or the dashboard.
- "Remember me" uses Laravel's built-in remember cookie and `remember_token` column.
- `POST /logout` signs the user out, invalidates the session, regenerates the CSRF token, and redirects to `/`. It is POST-only so CSRF protection stops other sites from signing users out.
- Only the signed-in user's `id` and `name` (plus the `auth.can` permission flags described below) are shared with every page (`HandleInertiaRequests`), not the whole user model.

### 5. Create user

A **Create user** item in the sidebar's Admin section opens `/users/create` (`UserController`, validated by `StoreUserRequest`). The form takes a name and a role:

- **Name:** required, max 255 characters, and must match `/^(?=.*[A-Za-z])[A-Za-z. ]+$/`, meaning letters, spaces, and full stops only, with at least one letter.
- **Role:** a dropdown of **employee** (the default), **manager**, or **admin**, loaded from the `roles` table. The server only accepts one of those three names (`Rule::in`); a raw `role_id` is never read. The success panel shows the chosen role.
- **Email:** generated on the server from the letters in the name, lowercased, plus `@fakemail.com` (a placeholder domain, `UserController::EMAIL_DOMAIN`). For example, `Dr. Asha  Rao` becomes `drasharao@fakemail.com`. If that address is taken, a number is added (`drasharao1@fakemail.com`, `drasharao2@fakemail.com`, …).
- **Password:** a random 16-character string from `Str::password(16)`, stored hashed. The plain password is shown to the creator **once**, using Inertia flash data, which is not kept in browser history. It is never stored or logged.
- Any `email`, `password`, `must_change_password`, or `role_id` value sent by the client is ignored.
- Choosing **admin** makes a user who can't be edited or deleted in the app afterwards, because admins can't act on other admins.
- Only **admins** can create users (see [Permissions](#8-permissions)).

### 6. Forced password change on first login

- Users created this way have `must_change_password` set to `true` (a boolean column added by a migration; existing users default to `false`). The column is not mass-assignable.
- The `EnsurePasswordIsChanged` middleware protects the dashboard and user pages. A user with a temporary password is redirected to `/password/change` and can't reach anything else, though signing out still works.
- The new password (`UpdatePasswordRequest`) must meet `Password::defaults()`, be confirmed, and differ from the temporary password.
- On success, `PasswordController` saves the password, clears the flag, regenerates the session, and redirects to the dashboard. Users without a temporary password who open `/password/change` are sent to the dashboard.

### 7. Roles

- Three organisational roles live in a `roles` table: **employee** (raises tickets and can be assigned to them), **manager** (will lead a department), and **admin** (manages the system). Their names are constants on `App\Models\Role` (`Role::EMPLOYEE`, `Role::MANAGER`, `Role::ADMIN`).
- **Requester and agent are not user roles.** They describe someone's part in a single ticket: whoever raises a ticket is its requester, and whoever from the assigned department works on it is its agent. The same person can be both on different tickets. These will be columns on tickets, not roles.
- The roles were first created as requester, agent, and admin. A later migration renamed them in place (requester → employee, agent → manager), so their ids and every `users.role_id` stayed the same.
- Every user has exactly one role through a required `users.role_id` foreign key. A role can't be deleted while users still have it.
- Users who already existed when roles were added became **admin**. Users created from the Create user form get the role the admin picks.
- `role_id` is not mass-assignable, so a role sent by the client is ignored.
- The factory defaults to employee, with `employee()`, `manager()`, and `admin()` states; the seeded `test@example.com` user is an admin.
- Roles are used for permissions; see below.

### 8. Permissions

- Permissions are enforced on the server with a Laravel **policy**: `App\Policies\UserPolicy::create()` allows only users whose role is admin (`$user->hasRole(Role::ADMIN)`).
- `UserPolicy::viewAny()` lets every signed-in user see the user list.
- `UserPolicy::update()` and `delete()` let admins edit or delete employees and managers, but never another admin (or themselves). Both work: Edit changes a user's role (see below), and Delete moves them to the Bin.
- `UserPolicy::viewTrashed()`, `restore()`, and `forceDelete()` let only admins open the bin and restore or permanently delete non-admin users that are in it.
- Both `GET /users/create` and `POST /users` check it with the route's `can('create', User::class)` middleware, and `StoreUserRequest::authorize()` checks it again. Employees and managers get **403 Forbidden**.
- The result is also shared with the frontend as `auth.can.createUsers`, which only hides the **Create user** sidebar link from non-admins. Hiding the link is cosmetic; the server check is the real protection.

### 9. Users list

- The **Users** sidebar item opens `/users` (`users.index`), which every signed-in user can see (`UserPolicy::viewAny()`).
- Every viewer sees each user's name and email. **Admins** also get an Actions column with **Edit** and **Delete** buttons on employee and manager rows only; admin rows show a dash. Everyone sees a **Department** column (a dash if none is set). **Employees and managers** also see a **Joined** column instead of Actions.
- Each row carries `can.update` and `can.delete` flags computed by `UserPolicy` on the server, and the page gets a `canManageUsers` flag. These only control what's shown.
- Users are sorted by name with 15 per page and Prev/Next links. Pagination happens on the server.
- Roles are eager-loaded (`with('role')`), so the page runs one extra query instead of one per user.
- Each row is built field by field, so only `id`, `name`, `email`, `role`, and `joined` reach the browser.
- Admins also see a **Create user** button on this page.

### 10. Deleting users and the Bin

- **Delete** on the users list asks for confirmation, then sends `DELETE /users/{user}` (`users.destroy`). The user is **soft-deleted**: a `deleted_at` timestamp is set instead of removing the row (`SoftDeletes` on the `User` model, added by a migration).
- A deleted user disappears from the users list and **can't sign in**. If they're already signed in, their session ends on their next request, because Laravel no longer finds them.
- Deleted users appear in the **Bin**, a sidebar item below Settings with a trash icon, at `/bin` (`bin.index`). Only admins see the link (`auth.can.viewBin`) or can open the page.
- The Bin lists name, email, role, and deleted date, most recently deleted first, 15 per page. Each row has:
    - **Restore** (`PATCH /bin/{user}/restore`): brings the user back; they can sign in again.
    - **Delete forever** (`DELETE /bin/{user}`), after a confirmation: permanently removes the row. This can't be undone.
- The bin routes use `withTrashed()` so `{user}` can match deleted users, and the policy only allows restore or delete-forever on users that are actually in the bin.
- A short success message (Inertia flash `status`) appears at the top of the page after deleting, restoring, or deleting forever.
- Users in the bin keep their email, so a newly created user with the same name gets the next number (e.g. `johndoe1@fakemail.com`).

### 11. Editing a user's role and department

- **Edit** on the users list opens `/users/{user}/edit` (`users.edit`). The page shows the user's name and email as read-only text, a **Role** dropdown (employee, manager, admin) set to their current role, and a **Department** dropdown set to their current department (or "Choose a department" if they have none).
- **Save changes** sends `PUT /users/{user}` (`users.update`), validated by `UpdateUserRequest`. Only `role` and `department` are accepted, both checked with `Rule::in`; the name, email, `role_id`, `department_id`, and other fields are ignored. The user is redirected to the users list with a message such as "Asha Rao is now manager in IT."
- Both routes use `UserPolicy::update()`: admins only, never on another admin or themselves (403). Users in the bin can't be edited (404).
- The role dropdown is shared with Create user as `resources/js/components/role-select.tsx`.

### 12. Departments

- Six departments live in a `departments` table: **HR**, **Sales**, **IT**, **Wellness**, **Claims**, and **Endorsement**. Names are unique and are constants on `App\Models\Department` (`Department::HR`, ..., and `Department::ALL`).
- They are fixed reference data, so the migration inserts them (like roles) rather than a seeder. The migration uses literal names, and a test checks `Department::ALL` matches the database so the two can't drift.
- **Departments page** at `/departments` (sidebar → Departments): every signed-in user can see it (`DepartmentPolicy::viewAny`). `DepartmentController::index` sends only each department's `id` and `name`, sorted by name, and the page shows them as large cards.
- **Assigning departments:** each user has an optional `users.department_id` (nullable because users created before departments have none; restrict on delete). The Create user and Edit user forms have a required **Department** dropdown below Role (`DepartmentSelect`), with no department pre-selected. The client sends a department **name**, validated with `Rule::in(Department::ALL)` in `StoreUserRequest` and `UpdateUserRequest`; a raw `department_id` is never read, and it isn't mass-assignable. The created-user panel and the update message ("Asha Rao is now manager in IT.") include the department.
- **Employees on each card:** `DepartmentController::index` eager-loads each department's users (sorted by name, users in the bin left out), using two queries in total. Each employee is sent as `id`, `name`, and `role` only, with no email. A card shows the employee count, up to 6 names with an initial avatar and a badge for managers and admins, and then "+N more".
- **Department page:** clicking a card opens `/departments/{department}` (`departments.show`, `DepartmentPolicy::view`, any signed-in user). It shows that department's employees in a table (name, email, role, joined), 15 per page and sorted by name, with users in the bin left out. It sends the same fields the Users list does, and has an "All departments" link back. An unknown id returns 404.

### Shared UI

- Login, change-password, and create-user pages share the dashboard's style: slate backgrounds, indigo accents, white cards, and dark mode support.
- `resources/js/components/form-field.tsx` is a reusable labelled input that shows its server-side validation error underneath.

## Project structure

```
app/Http/Controllers/DashboardController.php            Dashboard controller (sample data for now)
app/Http/Controllers/UserController.php                 Create user (generated email + password)
app/Http/Controllers/Auth/AuthenticatedSessionController.php   Login and logout
app/Http/Controllers/Auth/PasswordController.php        Forced password change
app/Http/Requests/StoreUserRequest.php                  Create user validation (name regex, role)
app/Http/Requests/UpdateUserRequest.php                 Edit user validation (role and department)
app/Http/Requests/Auth/LoginRequest.php                 Login validation, attempt, rate limiting
app/Http/Requests/Auth/UpdatePasswordRequest.php        New password validation
app/Http/Middleware/EnsurePasswordIsChanged.php         Redirects users with a temporary password
app/Http/Middleware/HandleInertiaRequests.php           Shared props (user id/name, auth.can flags)
app/Models/Role.php                                     Role model and role name constants
app/Policies/UserPolicy.php                             Who may list, create, delete, and restore users
app/Http/Controllers/UserBinController.php              Bin: list, restore, and permanently delete users
database/migrations/..._add_must_change_password_to_users_table.php
database/migrations/..._create_roles_table.php          Roles table + the three role rows
database/migrations/..._add_role_id_to_users_table.php  users.role_id (existing users become admin)
database/migrations/..._add_soft_deletes_to_users_table.php  users.deleted_at (the bin)
routes/web.php                                          Web routes
resources/views/app.blade.php                           Root HTML shell + theme bootstrap script
resources/js/app.tsx                                    Inertia/React entry point
resources/js/layouts/app-layout.tsx                     Sidebar, top bar, mobile nav, dark mode, sign out
resources/js/pages/auth/login.tsx                       Login page
resources/js/pages/auth/change-password.tsx             Set a new password page
resources/js/pages/users/create.tsx                     Create user page
resources/js/pages/users/index.tsx                      Users list page
resources/js/pages/users/edit.tsx                       Edit user page (role and department)
resources/js/pages/bin/index.tsx                        Bin page (deleted users)
resources/js/pages/dashboard.tsx                        Dashboard page
resources/js/components/dashboard/                      Card, stat card, bar chart, ring gauge,
                                                        department bars, recent tickets table
resources/js/components/form-field.tsx                  Labelled input with validation error
resources/js/components/role-select.tsx                 Role dropdown (create and edit user)
resources/js/components/page-link.tsx                   Prev/Next pagination link
resources/js/components/icons.tsx                       Inline SVG icons
resources/js/types/                                     Dashboard, auth, flash, and user list types
tests/Feature/DashboardTest.php                         Dashboard tests
tests/Feature/UserCreationTest.php                      Create user tests
tests/Feature/Auth/AuthenticationTest.php               Login, logout, registration-removed tests
tests/Feature/Auth/PasswordChangeTest.php               Forced password change tests
tests/Feature/RoleTest.php                              Role tests
tests/Feature/UserListTest.php                          Users list tests
tests/Feature/EmailDomainMigrationTest.php              gmail → fakemail migration tests
tests/Feature/UserEditTest.php                          Edit user (role) tests
tests/Feature/UserBinTest.php                           Delete, bin, restore, and delete-forever tests
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

There is no public sign-up. To get a first account locally, run `php artisan db:seed`, which creates the admin `test@example.com` with the password `password`. Further accounts are created from **Create user** in the sidebar.

### Run in development

```bash
composer dev
```

This starts the Laravel server and the Vite dev server together. Open the `APP_URL` from `.env` (default `http://localhost:8000`) and sign in.

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

- `tests/Feature/DashboardTest.php`: signed-in users see the `dashboard` component; it receives every expected prop with the right types (6 stats, 12 days of chart data, a resolution rate between 0 and 100, department counts, and recent tickets); the props never include sensitive fields such as `password`, `remember_token`, `email`, or `internal_note`; guests are redirected to the login page.
- `tests/Feature/Auth/AuthenticationTest.php`: the login page renders; valid credentials sign in and regenerate the session ID; a wrong password and an unknown email get the same error; invalid input is rejected; the remember cookie is set only when "Remember me" is ticked; repeated failures lock the account out; users return to the page they asked for; signed-in users are kept away from the login page; logout works, is POST-only, and is not available to guests; registration routes are gone; only the user's `id` and `name` are shared.
- `tests/Feature/UserCreationTest.php`: guests can't open or submit the form; employees and managers get 403 on both; the form receives the role options; admins can create employees, managers, and admins; missing, unknown, or old (requester/agent) roles are rejected; only admins are told they can create users; the email is built from the name, with numbers added for duplicates; the temporary password is 16 characters, stored hashed, and shown only once; invalid names are rejected (empty, digits, symbols, HTML, hyphens, only dots and spaces, too long); client-supplied email, password, and flags are ignored; users with a temporary password can't create users.
- `tests/Feature/EmailDomainMigrationTest.php`: `@gmail.com` addresses (including binned users) move to `@fakemail.com` and others are untouched; only the trailing domain is replaced; a clash stops and rolls back the whole change; the migration reverses; signing in works with the new address.
- `tests/Feature/UserEditTest.php`: admins see the edit form with the user and role options; admins can set each role and a message is flashed; name, email, `role_id`, and other fields can't be changed; missing, unknown, or old roles are rejected; admins can't edit another admin or themselves; employees, managers, and guests can't edit; users in the bin can't be edited.
- `tests/Feature/UserBinTest.php`: admins can move employees and managers to the bin, but not admins or themselves; employees, managers, and guests can't delete; deleted users vanish from the list, can't sign in, and are signed out if already signed in; a success message is flashed; new users don't reuse a binned user's email; only admins can see the bin, which lists only deleted users with safe fields; admins can restore (and the user can sign in again) or delete forever, non-admins can't, and neither works on users that aren't in the bin.
- `tests/Feature/UserListTest.php`: guests are redirected; employees, managers, and admins can all see the list; each row has exactly name, email, role, joined date, and permission flags; no sensitive fields are sent; users are sorted by name, 15 per page; users with a temporary password are sent to change it first; only admins get the manage view; the policy lets admins edit or delete employees and managers but not admins or themselves, and never lets employees or managers.
- `tests/Feature/RoleTest.php`: the employee, manager, and admin roles exist with unique names and the old requester/agent names are gone; a raw `role_id` sent by the client is ignored; `role_id` can't be mass-assigned; the user/role relationships and factory states work; a role in use can't be deleted.
- `tests/Feature/Auth/PasswordChangeTest.php`: users with a temporary password are redirected from protected pages; signing in with one leads to the change page; they can still sign out; the new password is validated (missing, too short, mismatched, same as temporary); a successful change lifts the restriction; other users skip the page; guests can't reach it.

## Security notes

- The dashboard and user pages require a signed-in user (`auth` middleware), and users with a temporary password are held on the change-password page until they set their own.
- Login uses one generic error for unknown emails and wrong passwords (no user enumeration), is rate limited per email + IP, and regenerates the session ID. Logout is POST-only and invalidates the session.
- Only the user's `id` and `name`, plus the `auth.can` flags, are shared with the frontend. A test makes sure dashboard props don't leak sensitive fields.
- When creating users, only the name and a validated role name are accepted from the client; the email, password, and `must_change_password` flag are set on the server. The temporary password is stored hashed and shown once.
- Creating users is restricted to admins by `UserPolicy`, checked on both routes and in the form request, with negative tests for employees and managers.
- Deleting is a soft delete, so a mistaken delete can be undone from the Bin; only admins can delete forever, after a confirmation.
- Generated emails use the placeholder domain `@fakemail.com`. They were `@gmail.com` at first; a migration moved every existing `@gmail.com` address (including users in the bin) to `@fakemail.com`, stopping and rolling back if any new address would already be taken.
- Planned rules for every feature: validate all input on the server, enforce authorization with Policies/Gates, guard against IDOR, never trust client-supplied IDs, roles, or ownership, keep internal notes and private attachments hidden from unauthorized users, and make sure uploaded files can never be executed.

## Roadmap

- [x] Project setup, tooling, and CI
- [x] Dashboard UI (sample data), layout, and dark mode
- [x] Authentication: login, logout, remember me, rate limiting (public registration removed)
- [ ] User management (done so far: create user with a generated email and temporary password, and a forced password change on first login)
- [ ] Departments and roles (done so far: employee, manager, and admin roles; admins pick the role and department when creating or editing users)
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
