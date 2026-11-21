# Lab 11 — Database-Based User Login Validation

> ** Web Programming Lab Assignment**
> A database-backed authentication system using **PHP 8**, **PostgreSQL**, and **PDO**.

---

## Project: ACCESS — Authentication Portal

A complete, secure login validation web application demonstrating:

- Password hashing with `password_hash()` / `password_verify()` (bcrypt)
- SQL injection prevention with PDO prepared statements
- CSRF token protection
- PHP session management with session ID regeneration
- Environment-variable-based database configuration
- Split-screen responsive UI with dark navy + electric-blue design

---

## Demo Credentials

| Field    | Value          |
|----------|----------------|
| Username | `lab11student` |
| Password | `Demo@12345`   |

---

## Project Structure

```
lab11-login-validation/
├── index.php              # Entry point (redirects to login)
├── login.php              # Login page + POST handler
├── dashboard.php          # Protected welcome/dashboard page
├── logout.php             # Session destroy + redirect
├── health.php             # Health-check endpoint (Render)
│
├── config/
│   └── database.php       # PDO connection factory
│
├── includes/
│   ├── session.php        # Session helpers, CSRF functions
│   ├── header.php         # Shared HTML <head>
│   └── footer.php         # Shared footer + script tag
│
├── database/
│   └── schema.sql         # PostgreSQL table definition
│
├── scripts/
│   └── seed_demo_user.php # Safe database seeder (no duplicates)
│
├── assets/
│   ├── css/style.css      # Full custom CSS (no frameworks)
│   └── js/script.js       # Vanilla JS (toggle, copy, spinner)
│
├── Dockerfile             # PHP 8.3 + Apache + pdo_pgsql
├── docker-entrypoint.sh   # Seeds DB then starts Apache
├── render.yaml            # Render deployment config
├── .env.example           # Environment variable template
├── .gitignore
└── README.md
```

---

## Local Setup

### Prerequisites

- **PHP 8.x** with `pdo` and `pdo_pgsql` extensions
- **PostgreSQL** (local or Neon)

### 1. Clone the repository

```bash
git clone https://github.com/<your-username>/lab11-login-validation.git
cd lab11-login-validation
```

### 2. Configure the environment

```bash
cp .env.example .env
```

Edit `.env` with your local PostgreSQL credentials:

```
DB_HOST=localhost
DB_PORT=5432
DB_NAME=lab11_db
DB_USER=postgres
DB_PASSWORD=your_password
DB_SSLMODE=prefer
```

### 3. Create the database

Using psql:

```sql
CREATE DATABASE lab11_db;
```

### 4. Run the schema + seed

```bash
php scripts/seed_demo_user.php
```

This creates the `users` table and inserts the demo user (safe to run multiple times — no duplicates).

### 5. Start the development server

```bash
php -S localhost:8000
```

Open [http://localhost:8000](http://localhost:8000) in your browser.

---

## Checking PHP PDO Extensions

```bash
php -m | findstr pdo
```

You should see `pdo` and `pdo_pgsql` listed.

---

## Security Features

| Feature                  | Implementation                              |
|--------------------------|---------------------------------------------|
| Password storage         | `password_hash()` with bcrypt (cost 12)     |
| Password verification    | `password_verify()`                         |
| SQL injection prevention | PDO prepared statements with named params   |
| CSRF protection          | Per-session CSRF token on login form        |
| Session fixation         | `session_regenerate_id(true)` after login   |
| Session security         | HttpOnly, SameSite=Strict session cookie    |
| Output escaping          | `htmlspecialchars()` on all user output     |
| Credential storage       | Environment variables only (no hardcoding)  |
| Error messages           | Generic — does not reveal which field failed|

---

## Test Cases

| # | Action                              | Expected Result                |
|---|-------------------------------------|-------------------------------|
| 1 | Correct username + correct password | Dashboard / welcome page       |
| 2 | Correct username + wrong password   | "Invalid username or password" |
| 3 | Wrong username + wrong password     | "Invalid username or password" |
| 4 | Empty fields submitted              | Field validation messages       |
| 5 | SQL injection in username field     | Login rejected; DB unaffected  |
| 6 | Direct access to dashboard.php      | Redirected to login page       |
| 7 | Logout then browser back button     | Redirected to login page       |

---

## Docker (Local)

```bash
docker build -t lab11-login-validation .
docker run -p 8080:80 \
  -e DB_HOST=host.docker.internal \
  -e DB_PORT=5432 \
  -e DB_NAME=lab11_db \
  -e DB_USER=postgres \
  -e DB_PASSWORD=your_password \
  -e DB_SSLMODE=prefer \
  lab11-login-validation
```

Open [http://localhost:8080](http://localhost:8080).

---

## Deployment (Render + Neon PostgreSQL)

See [Section 10 of the assignment instructions] for the step-by-step deployment guide.

**Environment variables to set on Render:**

| Variable     | Description                    |
|--------------|--------------------------------|
| `DB_HOST`    | Neon hostname                  |
| `DB_PORT`    | `5432`                         |
| `DB_NAME`    | Neon database name             |
| `DB_USER`    | Neon username                  |
| `DB_PASSWORD`| Neon password                  |
| `DB_SSLMODE` | `require`                      |

The `docker-entrypoint.sh` script automatically runs `seed_demo_user.php` on each deployment start (safe, no duplicates).

---

## Technology Stack

- **PHP 8.3**
- **PostgreSQL** (via Neon for production)
- **PDO** — prepared statements
- **HTML5 / CSS3 / Vanilla JS**
- **Apache** (via Docker on Render)

---

