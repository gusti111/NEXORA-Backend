# NEXORA Backend

Laravel REST API for **NEXORA**, a role-based PKL (Praktik Kerja Lapangan) logbook, review, and supervision platform.

This repository provides the API consumed by [NEXORA Mobile](https://github.com/gusti111/NEXORA-Mobile).

## Product capabilities

| Role | Responsibilities |
| --- | --- |
| **Mahasiswa** | View own PKL placement, submit activity logbooks with optional photo and GPS coordinates, track review status, resubmit revisions, and view review history. |
| **Dosen** | View assigned students and logbooks, approve/reject submissions, request revisions with comments, and maintain review history. |
| **Korlap** | Monitor assigned students, inspect progress statistics, complete eligible PKL placements, and export PDF/Excel reports. |
| **Admin** | Manage accounts and PKL placements, reset passwords, inspect logbooks, and view platform statistics. |

## Architecture and technology

- PHP 8.2+
- Laravel 12
- Laravel Sanctum 4 (personal access tokens)
- MySQL or SQLite
- Eloquent ORM and database migrations
- Intervention Image for uploaded logbook photo processing
- Laravel Excel for spreadsheet exports
- DomPDF for PDF reports
- PHPUnit / Laravel testing utilities

```text
NEXORA Mobile (Flutter)
        │
        │ HTTPS + Authorization: Bearer <token>
        ▼
NEXORA Backend (Laravel API)
        ├── Authentication (Sanctum)
        ├── Role and active-account middleware
        ├── User and PKL management
        ├── Logbook submission and review workflow
        ├── Report export (PDF / Excel)
        └── Database and uploaded files
```

## Requirements

- PHP 8.2 or newer.
- Composer 2.
- PHP extensions required by Laravel and the packages in `composer.json` (including PDO, SQLite or MySQL support, GD, fileinfo, mbstring, XML, and ZIP).
- Node.js and npm only when building the bundled Vite assets.
- MySQL or SQLite database.

## Local setup

### 1. Install dependencies

```bash
composer install
```

### 2. Configure the environment

```bash
cp .env.example .env
php artisan key:generate
```

By default, `.env.example` is configured for SQLite. Create the file if it does not exist:

```bash
touch database/database.sqlite
```

For MySQL, update the following values in `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nexora
DB_USERNAME=your_local_user
DB_PASSWORD=your_local_password
```

Never commit `.env` or production credentials.

### 3. Run migrations

```bash
php artisan migrate
```

### 4. Prepare public file storage

```bash
php artisan storage:link
```

### 5. Start the API

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

- Health check: `GET /up`
- API prefix: `/api`
- Android emulator base URL: `http://10.0.2.2:8000/api`

Use HTTPS in production. Set `APP_ENV=production`, `APP_DEBUG=false`, a production `APP_URL`, secure database credentials, and a properly configured web server / reverse proxy.

## Authentication and authorization

Public authentication endpoints:

| Method | Endpoint | Purpose |
| --- | --- | --- |
| `POST` | `/api/login` | Authenticate using email or username and password; returns a Sanctum bearer token and user profile. |
| `POST` | `/api/register` | Register a student account. |

Authenticated endpoint:

| Method | Endpoint | Purpose |
| --- | --- | --- |
| `POST` | `/api/logout` | Revoke the current personal access token. |
| `GET` | `/api/user` | Validate the current account and return the current profile in the standard `data` response envelope. |

Include the token on protected requests:

```http
Accept: application/json
Authorization: Bearer <access-token>
```

### Role access

| Role | Protected API groups |
| --- | --- |
| `mahasiswa` | Own PKL placement, logbook submission, statistics, revision, and review history. |
| `dosen` | Assigned student list, assigned logbooks, review actions, and permitted review history. |
| `korlap` | Assigned students and logbooks, statistics, PKL completion, and report exports. |
| `admin` | User management, PKL placement management, platform statistics, and logbook oversight. |

Protected routes use `auth:sanctum` together with role middleware. Active-account checks reject disabled users. Controllers additionally scope queries to the authenticated student's, lecturer's, or coordinator's assignments; role middleware alone is not treated as data ownership authorization.

### Token lifecycle

- Sanctum stores token hashes in the database; the plain-text token is returned to the client at issuance.
- The mobile application stores its token in platform secure storage.
- The mobile client calls `/api/logout` and the backend deletes the token used for the request.
- Admin password resets revoke the target user's existing personal access tokens.
- The current Sanctum expiration setting is 10,080 minutes (seven days); update `config/sanctum.php` deliberately if policy changes.
- Run `php artisan sanctum:prune-expired --hours=24` periodically in production to remove expired token records.

## Main API modules

| Module | Endpoints |
| --- | --- |
| Authentication | `POST /login`, `POST /register`, `POST /logout`, `GET /user` |
| Student PKL | `GET /pkls` |
| Student logbook | `GET /logbooks`, `POST /logbooks`, `PUT /logbooks/{id}/resubmit`, `GET /logbooks/statistics`, `GET /logbooks/{id}/history`, `GET /logbooks/{id}/photo` |
| Lecturer | `GET /dosen/logbooks`, `GET /dosen/mahasiswa`, `PATCH /dosen/logbooks/{id}/review` |
| Coordinator | `GET /korlap/logbooks`, `GET /korlap/statistics`, `GET /korlap/mahasiswa`, `PUT /korlap/pkls/{id}/complete`, PDF/Excel exports |
| Administrator | `/users`, `/admin/users/{id}`, `/pkls`, `/admin/pkls`, `/admin/logbooks`, `/admin/statistics` and related management routes |

See `routes/api.php` for the canonical route definitions and middleware.

### Idempotent submissions and in-app notifications

- Mobile submissions may include an `idempotency_key` UUID. The backend stores it on the logbook under a unique constraint.
- Replaying the same key for the same PKL returns the already-created logbook with HTTP `200` and `duplicate: true`; it does not create a second logbook or send another submission notification.
- Logbook creation and the lecturer's database notification are committed in one database transaction; a notification-write failure rolls back the logbook insert so a retry can safely start again.
- Concurrent requests using the same key are arbitrated by the database unique index and reconciled to the already-created record.
- A key already bound to a different PKL is rejected with HTTP `409`.
- The assigned lecturer receives a database notification when a new logbook is accepted.
- The student receives a database notification after the lecturer records an approval, revision request, or rejection.
- Authenticated notification endpoints: `GET /api/notifications`, `GET /api/notifications/unread-count`, `PATCH /api/notifications/{id}/read`, and `PATCH /api/notifications/read-all`.
- Notification queries and read-state updates are scoped to the authenticated user.
- Notifications are currently stored in Laravel's database notification table and displayed inside the mobile app. FCM/APNs push delivery is not configured in this phase.

## Logbook workflow and data rules

1. An administrator creates a PKL placement and assigns the student, lecturer, and coordinator.
2. A student can submit a logbook only for their own active PKL placement and within the allowed activity date range.
3. A new logbook starts with `pending` status.
4. The assigned lecturer can approve, reject, or request a revision. A revision request requires a non-empty comment.
5. Each lecturer review is recorded in `logbook_reviews`.
6. A student can resubmit a logbook only when its status is `revision_requested`; resubmission resets the current status to `pending`.
7. A coordinator can complete a PKL after at least one logbook has been approved.

Logbook photos are validated as images (up to 10 MB on upload), converted to JPEG, and processed to a target size of at most 500 KB. New photos are stored on Laravel's non-public `local` disk (`storage/app/private`). The `GET /api/logbooks/{id}/photo` endpoint streams the image only after checking the caller's relationship to the PKL placement: the student owner, assigned lecturer, assigned coordinator, or an administrator. Responses use private/no-store cache headers. Latitude and longitude are optional and validated against geographic ranges.

## Data protection and backup policy

- Do not commit `.env`, database dumps, access tokens, real student records, uploaded documents, or private evidence.
- `database/backup/` is ignored by Git and local backup artifacts are removed from the proposed Phase 1 branch. Use sanitized fixtures or model factories for tests.
- Keep operational backups outside the source repository, encrypt them at rest, restrict access, and define a retention policy.
- New logbook uploads are stored privately and are served through the authenticated photo endpoint; direct public `/storage/logbooks/...` URLs are not part of the new flow.
- Before deploying this change against an existing database, back up the database and storage directory, inspect the planned migration, then move legacy public photos into private storage:
  ```bash
  php artisan nexora:migrate-logbook-photos-to-private --dry-run
  php artisan nexora:migrate-logbook-photos-to-private
  ```
  The migration verifies file hashes before deleting a public duplicate. Missing files or mismatches are reported and return a non-zero exit status for manual review. Run the command with an account that has permission to read the public files and write to `storage/app/private`.
- Hard deletion of users with PKL assignments or review history is blocked to preserve academic records. Deactivate such accounts instead.

## Staging integration

A staging runbook and Postman collection are available for the Mobile–Backend Phase 2 contract:

- [Staging integration runbook](docs/STAGING_INTEGRATION_RUNBOOK.md)
- [Postman collection](postman/NEXORA-Phase-2-Staging.postman_collection.json)

The collection contains no real credentials. Configure a private Postman environment with the staging API URL, dummy student/lecturer accounts, a PKL placement ID, and a valid activity date before running it. Staging HTTP tests have not been marked passed until a real staging endpoint is configured and the collection is executed.

## Tests

Run the full automated suite:

```bash
php artisan test
```

Current feature tests cover:

- Login for all four supported roles.
- The authenticated user profile response.
- Cross-role request rejection and inactive-account rejection.
- Token revocation after logout.
- Token revocation after administrator password reset.
- Protection of user records with PKL history.
- Student logbook ownership isolation.
- Lecturer review ownership isolation.
- Logbook activity-title mass assignment regression.
- Database notification delivery for submission and review events.
- Notification inbox ownership, unread count, and read-state transitions.
- Transaction rollback when database notification persistence fails during submission or lecturer review.
- Idempotent submission replay, including after the PKL placement has been completed.
- Rejection of idempotency-key reuse across different PKL placements.
- Correct MIME response headers for legacy private PNG and WEBP photos.

## Continuous integration

GitHub Actions runs Composer validation and the Laravel test suite on pushes to `main` / `fix/**` and on pull requests targeting `main`.

Workflow: `.github/workflows/laravel-ci.yml`.

## Related repository

- [NEXORA Mobile](https://github.com/gusti111/NEXORA-Mobile)

## Project status

NEXORA is under active development. Review API contracts, database migrations, file-privacy requirements, notification retention, and deployment configuration before production releases.
