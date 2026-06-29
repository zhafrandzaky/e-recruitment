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
6. Run `bash copy-skills.sh` once from the repository root to populate `.agents/skills/` (then delete the script per its own instructions — see Section 4 below for why this is separate from the env setup).

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
| `GOOGLE_CALENDAR_CLIENT_ID` | *(per deployment)* | For interview scheduling (FR-015) — see Section 3 |
| `GOOGLE_CALENDAR_CLIENT_SECRET` | *(per deployment)* | |
| `ACCOUNT_LOCKOUT_MAX_ATTEMPTS` | `3` | See `docs/SECURITY.md` Section 2.2 |
| `ACCOUNT_LOCKOUT_COOLDOWN_MINUTES` | `15` | |
| `PASSWORD_RESET_TOKEN_TTL_MINUTES` | `60` | |

## 3. External Service Setup Notes

- **Google Calendar/Meet API**: each deploying company needs their own Google Cloud project with the Calendar API enabled and OAuth credentials configured. This is a per-deployment setup step, not something the codebase can provide a default for (see `docs/SECURITY.md` Section 6 — secrets are never shared across deployments).
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
