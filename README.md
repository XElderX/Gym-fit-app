# GYM Fit — personal gym tracker

Current release: **v0.1**

A phone-first personal fitness web app with a **Laravel 13 REST API**, **MySQL**, and a **React + TypeScript** SPA. The API is versioned under `/api/v1` and uses Laravel Sanctum-compatible authentication so the same backend can later serve a native mobile client.

## Architecture overview

- **Backend:** Laravel 13, Eloquent, REST controllers, service layer for workout synchronization and statistics.
- **Database:** MySQL in normal use; SQLite in-memory for tests.
- **Authentication:** stateful Sanctum cookies for the web SPA. Personal access-token support remains available for a future mobile app.
- **Frontend:** React + TypeScript + React Router + Recharts, built with Vite.
- **Autosave:** client UUIDs are persisted for workouts and sets. Repeated autosave requests upsert the same records rather than creating duplicate sets/workouts. The browser also stores the active draft in `localStorage` for refresh/network recovery.
- **Timezone:** each user has a timezone, default `Europe/Vilnius`; date presets are resolved in that timezone.

## Database model

```text
users
  ├─ workouts
  │   └─ workout_exercises
  │       └─ workout_sets
  ├─ workout_templates
  │   └─ template_exercises
  │       └─ template_sets
  └─ custom exercises

muscle_groups
  ├─ exercises.primary_muscle_group_id
  └─ exercise_secondary_muscle_group (pivot)
```

`template_sets` and `workout_sets` are intentionally separate. Starting from a template copies its plan into a new workout, so changes during training never mutate the original template.

## Statistics rules

The implementation uses one explicit rule everywhere: **only completed working sets count in primary-muscle totals**. Warm-up sets and incomplete sets are excluded. Each eligible set counts once toward its exercise's primary muscle group. Secondary muscle involvement is reported separately and never added to the primary totals.

For volume, weighted repetitions use `weight_kg × reps`. Weighted-bodyweight exercises use only the explicitly entered extra load × reps. Pure bodyweight exercises have sets/reps but no invented kilogram volume.

## Included functionality

- Start a workout today or backdate it.
- Per-set decimal kg, reps, completion, warm-up/working type and optional RPE.
- Bodyweight and weighted-bodyweight tracking.
- Exercise feeling rating (1–5) and notes, independent from RPE.
- Reorder/delete exercises, fast “add set using previous values”, previous performance prefill.
- Start/end timestamps, duration, notes, mood, autosave and draft restore.
- Searchable seeded exercise library and custom-exercise REST endpoints.
- Calendar/history, date/exercise filtering, workout editing, visits/frequency.
- Statistics date presets, primary and secondary muscle-set counts, volume, exercise totals, feelings/mood data and exercise progress/PR endpoints.
- Reusable workout templates with planned sets, plus start-from-template.
- Dark responsive UI with a gym-friendly bottom navigation on phones.
- User data isolation and validation at API boundaries.

## Ubuntu local setup

### 1. System packages

```bash
sudo apt update
sudo apt install -y mysql-server php8.3-cli php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip unzip curl nodejs npm
```

Install Composer if it is not already available:

```bash
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php --install-dir=$HOME/.local/bin --filename=composer
rm composer-setup.php
export PATH="$HOME/.local/bin:$PATH"
```

### 2. MySQL

```bash
sudo mysql
```

```sql
CREATE DATABASE gym_fit CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'gym_fit'@'localhost' IDENTIFIED BY 'choose-a-local-password';
GRANT ALL PRIVILEGES ON gym_fit.* TO 'gym_fit'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 3. Backend

```bash
cd backend
cp .env.example .env
# Edit DB_PASSWORD in .env
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve --host=127.0.0.1 --port=8000
```

Optional demo user data is deliberately separated from the normal seed. Run it only when wanted:

```bash
php artisan db:seed --class=DemoSeeder
```

The demo seeder creates `demo@example.test` with password `demo-password`. Do not use demo credentials outside local development.

### 4. Frontend

In a second terminal:

```bash
cd frontend
cp .env.example .env
npm install
npm run dev
```

Open `http://localhost:5173`.

## Tests

```bash
cd backend
composer install
php artisan test
```

The feature suite covers user isolation, idempotent workout saving/editing, date filtering, and the muscle-set/volume rules.

## Production notes

Set real secrets only in `.env`, use HTTPS, set appropriate `SESSION_DOMAIN` / `SANCTUM_STATEFUL_DOMAINS`, turn off `APP_DEBUG`, and run Laravel behind a production web server. The repository intentionally excludes subscriptions, social feeds, nutrition tracking, AI coaching, queues/Redis infrastructure that the current product does not need.

See [`docs/API.md`](docs/API.md) for the REST contract and examples, and [`CHANGELOG.md`](CHANGELOG.md) for release notes.
