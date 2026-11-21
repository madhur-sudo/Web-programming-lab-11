 ACCESS — User Authentication

A PHP application for database-based user authentication. A user submits a username and password; PHP checks the account in PostgreSQL and displays a protected page only when `password_verify()` accepts the stored password hash.

## Features

- PostgreSQL user lookup with PDO prepared statements
- Password hashes created with `password_hash()` and verified with `password_verify()`
- Account registration using the existing PostgreSQL `users` table and its unique username constraint
- CSRF protection for registration, login, and logout requests
- Session ID regeneration after successful authentication; HttpOnly and SameSite cookies
- Responsive login and authenticated dashboard pages
- Docker image with PHP 8.3, Apache, PDO_PGSQL, and database schema setup
- Render Blueprint for the web service and a separate managed PostgreSQL database

New users can create an account from the sign-in page. Registration validates the username and password, stores only a password hash in PostgreSQL, and returns the user to sign in. Existing database users continue to use the same login flow.

## Architecture

`register.php` validates and inserts a new account into the existing `users` table with a prepared statement. `login.php` retrieves the matching username with a prepared query, verifies the hash, and creates an authenticated session. `dashboard.php` checks the session and reloads the account from PostgreSQL. `logout.php` accepts only a CSRF protected POST request and destroys the session. `health.php` reports application/database readiness without exposing connection details.

## Project layout

```text
config/       Environment loading and PDO connection factory
database/     Idempotent PostgreSQL schema
includes/     Session and shared page layout helpers
scripts/      Schema setup and optional demo-user seeder
assets/       CSS and vanilla JavaScript
index.php     Redirects to the login page
register.php  Registration form and account creation
login.php     Login form and credential validation
dashboard.php Authenticated page
logout.php    CSRF-protected logout handler
health.php    Database-aware health endpoint
Dockerfile    PHP/Apache production image
render.yaml   Render web service and managed PostgreSQL blueprint
```

## PostgreSQL setup

Create a database named `lab11_db`, then apply the schema:

```sh
psql -U postgres -d lab11_db -f database/schema.sql
```

The schema creates `users` with an integer primary key, unique username, password hash, and creation timestamp. It is safe to apply more than once. Do not store plaintext passwords in `password_hash`.

### Configure environment

Copy `.env.example` to `.env` and set local PostgreSQL values. The application accepts either the individual `DB_*` variables or a PostgreSQL `DATABASE_URL` (which takes precedence). `.env` is ignored by Git; production credentials belong in the hosting provider's environment settings.

```text
DB_HOST=localhost
DB_PORT=5432
DB_NAME=lab11_db
DB_USER=postgres
DB_PASSWORD=your_local_password
DB_SSLMODE=prefer
```

### Create the first account

After setting `.env` and applying the schema, start the app and use **Create one** on the sign-in page. This works when the `users` table is empty and stores a password hash rather than the submitted password. Registration accepts usernames of 3–32 letters, numbers, underscores, or hyphens, and passwords of 8–72 bytes.

The optional one-time helper remains available for local development. It uses environment variables and never prints the password or its hash:

```sh
# PowerShell
$env:DEMO_USERNAME = "lab11student"
$env:DEMO_PASSWORD = "choose-a-local-test-password"
php scripts/seed_demo_user.php
```

```sh
# macOS/Linux
DEMO_USERNAME=lab11student DEMO_PASSWORD='choose-a-local-test-password' php scripts/seed_demo_user.php
```

The helper skips an existing username and is not run automatically in production.

## Run locally

Requirements: PHP 8.3 (or compatible PHP 8.x) with `pdo` and `pdo_pgsql`, and PostgreSQL.

```sh
php scripts/migrate.php
php -S 127.0.0.1:8000
```

Open <http://127.0.0.1:8000>, create an account, and sign in. Existing rows in the configured `users` table remain valid.

## Docker

Build the image and run it with a reachable PostgreSQL server. The startup script retries schema setup, then starts Apache on port 10000. It does not seed a shared/default account.

```sh
docker build -t lab11-login-validation .
docker run --rm -p 8080:10000 `
  -e DB_HOST=host.docker.internal `
  -e DB_PORT=5432 `
  -e DB_NAME=lab11_db `
  -e DB_USER=postgres `
  -e DB_PASSWORD=your_local_password `
  -e DB_SSLMODE=prefer `
  lab11-login-validation
```

For macOS/Linux, replace PowerShell backticks with `\` line continuations. Open <http://localhost:8080>.

## Deploy to Render

`render.yaml` defines one Docker web service and one separate Render Postgres database. The web service receives the managed database's private `DATABASE_URL`; registered users remain in the database across web service rebuilds and restarts. The database plan declared in the Blueprint is a paid persistent plan; check current Render pricing before provisioning.

1. Push this repository to GitHub and sign in to Render.
2. In Render, choose **New → Blueprint**, connect `madhur-sudo/lab11-login-validation`, and select `main`.
3. Review the Blueprint before applying it. It should show one web service and one PostgreSQL database. If Render detects existing resources with these names, verify that they are the intended resources before syncing.
4. Apply the Blueprint and wait for both services to become available. Render supplies `DATABASE_URL` from the managed database; no database password goes in the repository.
5. The container applies `database/schema.sql` on startup. No initial user is required.
6. Visit the web service URL, choose **Create one**, register, and sign in. Confirm `/health.php` returns HTTP 200 with a connected database before considering the deployment live.

The `github-pages` deployment shown in the repository screenshot is not an application deployment: GitHub Pages serves static files and cannot execute PHP or connect this app to PostgreSQL. This checkout contains no `.github/workflows` deployment workflow, so the separate failed `main` status has no local workflow definition to diagnose. Inspect its GitHub Actions run log if a workflow was added outside this checkout; do not use Pages to host this app.

## Security notes

- Password hashes only; no plaintext credential storage
- Prepared SQL statements and generic invalid-login feedback
- CSRF tokens on registration, login, and logout; login session ID rotated after success
- Authenticated dashboard reads the user's current row from PostgreSQL
- User visible output is HTML-escaped
- Database configuration comes from environment variables; connection errors are logged server-side and a generic message is shown to users

## Verification checklist

Test registration with valid details, empty fields, invalid and duplicate usernames, short and mismatched passwords, and verify PostgreSQL contains a hash rather than plaintext. Then test sign-in with new and existing users, invalid credentials, direct unauthenticated dashboard access, logout, and dashboard access after logout. Also test with an empty `users` table. Hosting-specific checks require a reachable PostgreSQL instance and Render service.
