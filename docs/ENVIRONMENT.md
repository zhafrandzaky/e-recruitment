# ENVIRONMENT.md — Environment Variables & Local Setup

**Project:** e-recruitment
**Version:** 1.0

## 1. Local Development Setup

### Prerequisites
- Docker and Docker Compose (for PostgreSQL, Redis, MinIO, Mailpit)
- PHP + Composer (for `apps/api`, Laravel)
- Bun (for `apps/web` — **never** npm/yarn/pnpm, see `AGENTS.md` Section 8)

### Steps
1. Clone the repository.
2. Copy `apps/api/.env.example` to `apps/api/.env` and `apps/web/.env.example` to `apps/web/.env`; fill in values per the tables below.
3. Run `docker compose -f docker/docker-compose.yml up -d` to start PostgreSQL, Redis, MinIO, and Mailpit.
4. In `apps/api`: `composer install`, then run migrations (`php artisan migrate`).
5. In `apps/web`: `bun install`, then `bun run dev`.
6. Generate a Reverb app key/secret for development: `php artisan reverb:install` inside `apps/api` if the `.env` values are blank — or copy from the generated `.env` defaults.

> **Note:** `copy-skills.sh` was run during Phase 0 setup and deleted. `.agents/skills/` is already populated. This step is no longer needed for new contributors.

## 1a. Demo Data Seeder (Local/Staging Only)

`DemoDataSeeder` populates **every** entity with realistic Indonesian demo data for manual testing and demos. Run it with `php artisan db:seed`, or together with a fresh schema via `php artisan migrate:fresh --seed` (both from `apps/api`).

What it creates (exact counts):

| Entity | Count |
|---|---|
| Users | 13 (1 HR, 12 applicants) |
| Applicant profiles | 12 |
| Job postings | 5 (4 active, 1 closed) |
| Applications | 20 (6 pending, 5 shortlisted, 5 rejected, 4 hired) |
| Application status history | 18 |
| Interviews | 5 (manual meeting links) |
| Chat threads / messages | 6 / 19 |

### Known test accounts

All seeded accounts share the password **`password`**.

| Role | Email | Password |
|---|---|---|
| HR Admin | `hr@example.com` | `password` |
| Applicant | `pelamar1@example.com` | `password` |
| Applicant | `pelamar2@example.com` | `password` |
| Applicant | `pelamar3@example.com` | `password` |

The remaining 9 applicants use emails derived from their names (e.g. `dewi.lestari@example.com`), same password.

### Behaviour and guarantees

- **Designed for a fresh/empty database** — the canonical command is `php artisan migrate:fresh --seed`.
- **Safe to re-run:** if the demo HR account (`hr@example.com`) already exists, the seeder is a **no-op** — it does not append duplicate rows. To reset, run `migrate:fresh --seed` again.
- **Never runs in production:** the seeder refuses to run when `app()->environment('production')` (in addition to Laravel's own `--force` requirement for seeding in production). Do not seed demo data into a production database.
- **CV files are not seeded to object storage:** seeded applications reference placeholder `cv_path` keys, so downloading a seeded application's CV shows the handled "Dokumen tidak dapat dimuat" message. Submit a real application through the UI to exercise actual CV upload/download.
- Interview meeting links are manually-entered URLs (per `docs/DECISIONS.md` ADR-024 — there is no external Calendar/Meet API).

## 2. Backend Environment Variables (`apps/api/.env`)

| Variable | Example (dev) | Description |
|---|---|---|
| `APP_NAME` | `e-recruitment` | Branding — displayed product name (see `docs/ARCHITECTURE.md` Section 6). Override per deployment to the licensing company's brand. |
| `APP_LOGO_URL` | `/assets/logo/logo-primary.svg` | Branding — logo asset path or URL. |
| `APP_ENV` | `local` | `local` / `production` |
| `APP_KEY` | *(generated)* | Laravel application key — generate via `php artisan key:generate`, never commit a real value |
| `APP_URL` | `http://localhost:8000` | Base backend URL |
| `DB_CONNECTION` | `pgsql` | |
| `DB_HOST` | `localhost` | |
| `DB_PORT` | `5432` | |
| `DB_DATABASE` | `e_recruitment` | |
| `DB_USERNAME` | `postgres` | |
| `DB_PASSWORD` | *(set locally, never commit)* | |
| `REDIS_HOST` | `localhost` | |
| `REDIS_PORT` | `6379` | |
| `QUEUE_CONNECTION` | `redis` | |
| `FILESYSTEM_DISK` | `s3` | Configured to point at MinIO locally |
| `AWS_ACCESS_KEY_ID` | *(MinIO access key)* | S3-compatible credential — works identically for MinIO, R2, AWS S3 |
| `AWS_SECRET_ACCESS_KEY` | *(MinIO secret key)* | |
| `AWS_DEFAULT_REGION` | `us-east-1` | Required by S3-compatible clients even when irrelevant to the actual provider |
| `AWS_BUCKET` | `e-recruitment-cvs` | |
| `AWS_ENDPOINT` | `http://localhost:9000` | MinIO endpoint locally; omit/change for R2/AWS S3 |
| `AWS_USE_PATH_STYLE_ENDPOINT` | `true` | Required for MinIO; typically `false` for AWS S3 |
| `MAIL_MAILER` | `smtp` | |
| `MAIL_HOST` | `localhost` (Mailpit) / Resend SMTP host in production | |
| `MAIL_PORT` | `1025` (Mailpit) | |
| `RESEND_API_KEY` | *(production only)* | Used instead of SMTP credentials when `MAIL_MAILER=resend` in production |
| `REVERB_APP_ID` | *(generated)* | Laravel Reverb broadcasting credentials |
| `REVERB_APP_KEY` | *(generated)* | |
| `REVERB_APP_SECRET` | *(generated)* | |
| `REVERB_HOST` | `localhost` | |
| `REVERB_PORT` | `8080` | |
| `ACCOUNT_LOCKOUT_MAX_ATTEMPTS` | `3` | See `docs/SECURITY.md` Section 2.2 |
| `ACCOUNT_LOCKOUT_COOLDOWN_MINUTES` | `15` | |
| `PASSWORD_RESET_TOKEN_TTL_MINUTES` | `60` | |

## 3. External Service Setup Notes

- **Resend**: requires a Resend account and verified sending domain for production. Mailpit requires no external account for local development.
- **MinIO**: runs locally via Docker Compose with no external account needed. For production, either continue self-hosting MinIO or swap to Cloudflare R2/AWS S3 by changing the `AWS_*` variables only (no code change — see `docs/ARCHITECTURE.md` Section 3).

## 4. Frontend Environment Variables (`apps/web/.env`)

| Variable | Example (dev) | Description |
|---|---|---|
| `VITE_API_BASE_URL` | `http://localhost:8000/api` | Backend API base URL |
| `VITE_REVERB_HOST` | `localhost` | WebSocket connection target |
| `VITE_REVERB_PORT` | `8080` | |
| `VITE_REVERB_APP_KEY` | *(matches backend `REVERB_APP_KEY`)* | |

## 5. Docker Compose Services (Development)

Defined in `docker/docker-compose.yml`:

| Service | Purpose |
|---|---|
| `postgres` | Primary database |
| `redis` | Cache and queue backend |
| `minio` | S3-compatible object storage |
| `mailpit` | Local email capture (no real sending) |

See `docker/docker-compose.prod.yml` for the production/self-hosted equivalent (see `docs/ARCHITECTURE.md` Section 5.2) — structurally similar but adds the application containers themselves (Laravel API, Vue.js static build) and omits Mailpit in favor of real Resend configuration.

## 6. Secret Handling Reminder

Never commit a populated `.env` file. `.env.example` files (committed) document variable *names* only — see `docs/SECURITY.md` Section 6 for the full secrets management policy.
