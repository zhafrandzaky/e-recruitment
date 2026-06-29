# e-recruitment

A single-tenant internal recruitment system: companies that license this software get one self-contained deployment to manage job postings, candidate applications, screening, interview scheduling, and recruiter-candidate communication — without sharing infrastructure or data with any other company.

> **Note on naming:** "e-recruitment" is the repository and codebase name. The product name shown to end users (page titles, header, outgoing emails) is configured per-deployment via the `APP_NAME` environment variable — see [`docs/ENVIRONMENT.md`](docs/ENVIRONMENT.md). A company deploying this software brands it as their own.

## What this is

This is **not** a multi-company job marketplace (it is not LinkedIn, Glints, or JobStreet). Each deployment serves exactly one company:

- **Applicants** (external, public) browse that company's open job postings and apply.
- **HR Admins** (internal) manage job postings and screen incoming applications.

There is no cross-company aggregation, no shared tenant database, and no subscription billing built into the product itself — see [`docs/PRD.md`](docs/PRD.md) for the full product rationale and [`docs/DECISIONS.md`](docs/DECISIONS.md) for why this architecture was chosen over a multi-tenant SaaS model.

## Core capabilities

| Module | What it does |
|---|---|
| Authentication | Email/password login, account lockout after repeated failures, password reset via email |
| Job listings | Public job search/browse (applicants), full CRUD job management (HR) |
| Applications | CV upload (PDF, max 2MB) with structured application form, status tracking |
| Screening | HR review queue, CV viewing/download, status transitions (Pending → Shortlisted / Rejected) |
| Notifications | Automatic email on every application status change |
| Interview scheduling | HR schedules interviews; the system auto-generates a Google Meet/Zoom link and emails it to the applicant |
| Real-time chat | One chat thread per application, connecting the applicant and HR in real time |
| Reporting | HR dashboard: applicants per posting, selection funnel, average time-to-hire |

Full functional detail lives in [`docs/FR.md`](docs/FR.md) and [`docs/SRS.md`](docs/SRS.md).

## Tech stack

| Layer | Technology |
|---|---|
| Frontend | Vue.js, managed exclusively with **Bun** |
| Backend | Laravel |
| Database | PostgreSQL |
| Real-time | Laravel Reverb |
| File storage | S3-compatible (MinIO by default, swappable to Cloudflare R2 / AWS S3) |
| Email | Resend (production) / Mailpit (development) |
| Interview links | Google Calendar / Meet API |

Full rationale for every choice above lives in [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md).

## Documentation

All project documentation lives in [`docs/`](docs/):

| Document | Purpose |
|---|---|
| [PRD.md](docs/PRD.md) | Product vision, problem statement, target users |
| [SRS.md](docs/SRS.md) | Formal software requirement specification |
| [FR.md](docs/FR.md) | Functional requirements, module by module |
| [NFR.md](docs/NFR.md) | Non-functional requirements (performance, security baseline, etc.) |
| [USECASE.md](docs/USECASE.md) | Use case diagram and narrative |
| [CLASS-DIAGRAM.md](docs/CLASS-DIAGRAM.md) | Domain model / class structure |
| [SEQUENCE-DIAGRAM.md](docs/SEQUENCE-DIAGRAM.md) | Critical interaction flows |
| [SCHEMA.md](docs/SCHEMA.md) | Database schema |
| [ARCHITECTURE.md](docs/ARCHITECTURE.md) | Technical architecture and rationale |
| [SECURITY.md](docs/SECURITY.md) | Internal threat model and security controls |
| [API.md](docs/API.md) | API contract |
| [ROADMAP.md](docs/ROADMAP.md) | Phase plan and status |
| [DECISIONS.md](docs/DECISIONS.md) | Architecture Decision Record (ADR) log |
| [TESTING.md](docs/TESTING.md) | Testing standards |
| [ENVIRONMENT.md](docs/ENVIRONMENT.md) | Environment variables and local setup |
| [GLOSSARY.md](docs/GLOSSARY.md) | Domain terminology |
| [DESIGN-SYSTEM.md](docs/DESIGN-SYSTEM.md) | Visual design tokens and UI rules |

## Getting started (local development)

See [`docs/ENVIRONMENT.md`](docs/ENVIRONMENT.md) for the full setup guide, including all required environment variables and the Docker Compose services used in development (PostgreSQL, Redis, MinIO, Mailpit).

## Contributing

See [`CONTRIBUTING.md`](CONTRIBUTING.md) for contribution guidelines, and [`AGENTS.md`](AGENTS.md) if you are an AI coding agent working on this repository — that file is binding for all AI-assisted work here.

## Security

To report a vulnerability, see [`SECURITY.md`](SECURITY.md).

## License

MIT — see [`LICENSE`](LICENSE).
