# NEXORA Backend

Laravel REST API for **NEXORA**, a PKL (Praktik Kerja Lapangan) logbook and supervision platform.

This service powers the [NEXORA Mobile](https://github.com/gusti111/NEXORA-Mobile) Flutter application.

## Core modules

- Token-based authentication using Laravel Sanctum.
- Role-based access for Mahasiswa, Dosen, Korlap, and Admin.
- PKL placement management.
- Student logbook submissions with optional photo and location data.
- Lecturer review, approval, and revision history.
- Korlap monitoring, progress statistics, and PDF/Excel exports.
- Admin user management and platform statistics.

## Tech stack

- PHP 8.2+
- Laravel 12
- Laravel Sanctum
- MySQL or SQLite
- Intervention Image
- Laravel Excel
- DomPDF

## Requirements

- PHP 8.2 or newer with the extensions required by Laravel and the packages above.
- Composer
- Node.js and npm (for the bundled Vite assets, when needed)
- A configured database

## Local setup

1. Install PHP dependencies:

   ```bash
   composer install
   ```

2. Create the local environment file and application key:

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. Configure the database connection in `.env`. For SQLite, create the database file if it does not exist. For MySQL, set `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD`.

4. Run migrations:

   ```bash
   php artisan migrate
   ```

5. Create the public storage symlink for uploaded logbook photos:

   ```bash
   php artisan storage:link
   ```

6. Start the API server:

   ```bash
   php artisan serve --host=0.0.0.0 --port=8000
   ```

The API health endpoint is `/up`. The mobile client uses the `/api` prefix.

## Authentication

Send the Sanctum personal access token in the `Authorization` header:

```http
Authorization: Bearer <access-token>
Accept: application/json
```

Available roles:

| Role | Scope |
| --- | --- |
| `mahasiswa` | Own PKL placement and logbooks |
| `dosen` | Assigned students and their logbook reviews |
| `korlap` | Students and PKL records assigned to the coordinator |
| `admin` | User, placement, logbook, and platform administration |

## API route groups

- Public: registration and login.
- Authenticated: logout and the current-user endpoint.
- Admin: user and PKL management, statistics, and logbook oversight.
- Mahasiswa: own placements, logbook submission, revision, statistics, and history.
- Dosen: assigned logbooks, student list, and review actions.
- Korlap: assigned logbooks and students, statistics, completion, and report exports.

Refer to `routes/api.php` for the current route definitions and middleware requirements.

## Tests

Run the automated test suite with:

```bash
php artisan test
```

## Data and backup handling

Do not commit `.env`, production database dumps, uploaded user documents, access tokens, or other operational data. Keep local database backups and uploaded files outside the public source repository. Use sanitized fixtures or factories for automated tests.

## Related repository

- [NEXORA Mobile](https://github.com/gusti111/NEXORA-Mobile)

## Project status

NEXORA is under active development. API contracts and deployment configuration may change as the mobile and backend repositories evolve.
