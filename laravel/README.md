# FA-Digital Laravel application

Laravel 13, Livewire 4, Fortify authentication, and MariaDB. The UI uses the colors and layout from `../prototyp/`; the static prototype and GitHub Pages landing page remain available separately.

## Test with Docker

From this directory, run `docker compose up --build -d`, then open <http://localhost:8000>. Log in with `admin@fa-digital.local` and `TestPasswort123!`. The Compose stack starts MariaDB, applies migrations, and loads sample articles and orders. It binds the site to your computer's loopback address for local testing.

On this Mac, Docker's CLI is not on `PATH`; use `/Applications/Docker.app/Contents/Resources/bin/docker compose up --build -d` if `docker` is not found.

Run `docker compose logs -f app` to inspect startup errors and `docker compose down` to stop the stack. The named volumes retain database data between starts. The demo credentials and database password in `compose.yaml` are for local testing only.

## Requirements

PHP 8.3+ with `pdo_mysql`, Composer, Node.js/npm, and MariaDB. Configure the web server document root to `laravel/public`, never to the repository root.

## Local setup

1. Create a MariaDB database and a dedicated user, for example:

   ```sql
   CREATE DATABASE fa_digital CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'fa_digital'@'127.0.0.1' IDENTIFIED BY 'choose-a-strong-password';
   GRANT ALL PRIVILEGES ON fa_digital.* TO 'fa_digital'@'127.0.0.1';
   ```

2. In this directory, run `composer install`, copy `.env.example` to `.env`, and set `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` to your MariaDB connection. Keep `.env` private.
3. Run `php artisan key:generate`, `php artisan migrate --seed`, and `php artisan fa:admin you@example.com`. The last command prompts for the first administrator's name and password.
4. Run `npm install && npm run build`, then `php artisan serve`. Open `http://localhost:8000` and log in. For frontend development, run `npm run dev` in a second terminal.

`php artisan test` runs the Laravel and Livewire tests with an in-memory SQLite database. `composer run lint:check` checks PHP style. Run `php artisan migrate` after pulling schema changes.

## Workflows

An administrator creates users, articles, work plans, and workstations. A manufacturing order copies the current work plan and hourly rates, so later master-data edits do not change existing planned costs. A worker starts setup or processing from the terminal, can pause with a reason, and finishes with good and scrap quantities. Processing time drives actual costs; interruptions are reported separately. Finished orders appear in the costing screen. The reports screen exports interruption totals as CSV.

Public registration is disabled. Create additional accounts in **Benutzer** after signing in as an administrator. Password-reset email uses the `log` mailer until SMTP is configured.
