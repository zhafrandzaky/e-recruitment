# DECISIONS.md — Architecture Decision Record (ADR) Log

**Project:** e-recruitment

Each entry records a significant decision, the context that led to it, and what was rejected. New AI agents joining a later phase should read this in full before re-litigating a decision that was already made deliberately.

---

## ADR-001: Single-tenant architecture, not multi-tenant SaaS

**Date:** Project inception
**Status:** Accepted

**Context:** The project was initially inspired by an academic E-Recruitment coursework reference. That reference's data model had no `Company` entity at all — one HR Admin, one set of job postings, applicants from the public applying to that one company.

**Decision:** Keep this single-tenant model. One deployment instance serves exactly one company. No `tenant_id`, no cross-company aggregation, no shared infrastructure between companies.

**Rejected alternative:** Multi-tenant SaaS model (like `zhire`, a separate unrelated project). Rejected because the product's actual distribution model (Section "Business Model" below) doesn't call for it — and multi-tenancy would add meaningful architectural complexity (tenant scoping, data isolation guarantees) for no benefit given how the product is sold.

## ADR-002: Licensed/delivered once per company, not SaaS subscription

**Date:** Project inception
**Status:** Accepted

**Context:** Considered SaaS subscription, then hybrid SaaS+self-host, before settling on the final model.

**Decision:** This software is licensed/delivered once per company — similar to traditional licensed software — not hosted as a recurring subscription managed by the project owner. There is no billing, plan management, or usage metering built into the product.

**Rejected alternatives:**
- Pure SaaS (project owner hosts and bills monthly per company) — rejected, doesn't match the intended business model.
- Hybrid SaaS + self-host (some companies subscribe, others self-host) — initially considered, then explicitly rejected in favor of the simpler one-time-delivery model once clarified.

## ADR-003: Interview scheduling via external Calendar/Meet API link generation, not embedded video

**Date:** Project inception
**Status:** Accepted

**Context:** Three options were considered for the interview feature: (A) build custom WebRTC video calling from scratch, (B) embed a third-party video provider's SDK (Daily.co, Twilio Video, Jitsi) directly in the web app, (C) auto-generate a Google Meet/Zoom link via API and let the interview happen outside the application.

**Decision:** Option C. HR schedules an interview in the system; the system calls the Calendar/Meet API to create an event and generate a meeting link; the link is emailed to the applicant. The interview itself happens outside this application.

**Rejected alternatives:**
- Option A (custom WebRTC) — rejected as disproportionate effort for a solo-developer-built supporting feature; video call infrastructure reliability is itself a multi-month-plus engineering effort, not justified when the feature is a means to an end (scheduling), not the product's core value.
- Option B (embedded third-party SDK) — rejected mainly due to ongoing per-usage cost and added integration complexity, for a feature that external tools already solve well. Revisit only if a strong product reason emerges (not currently the case).

## ADR-004: Real-time chat scoped narrowly — one thread per application, no extras

**Date:** Project inception
**Status:** Accepted

**Decision:** Chat is one real-time thread per `Application`, connecting exactly the applicant and HR handling that application. Explicitly excluded: group chat, applicant-to-applicant chat, file attachments in chat, read receipts, typing indicators.

**Rationale:** The brainstormed purpose was filling a real communication gap (applicants currently have no structured channel to ask questions), not building a general messaging product. Each excluded feature was evaluated and judged to add complexity disproportionate to its value at this stage.

## ADR-005: Reporting/Analytics included in initial scope, not deferred

**Date:** Project inception
**Status:** Accepted

**Context:** Initially proposed for deferral to a later phase to keep early scope minimal; reconsidered and moved into current scope.

**Decision:** Module 8 (Reporting — applicants per posting, selection funnel, time-to-hire) is part of the initial build, not deferred. This required adding `application_status_history` as a dedicated table (see `docs/SCHEMA.md`) beyond what the original academic reference modeled, since funnel/time-to-hire calculations need a status-change timeline, not just current status.

## ADR-006: Single HR role type, no Recruiter/Hiring Manager distinction

**Date:** Project inception
**Status:** Accepted

**Decision:** One `hr_admin` role. No granular permission tiers between different kinds of HR staff.

**Rationale:** Matches the original academic reference's model and keeps the authorization logic simple for the current scope. Explicitly flagged as a known limitation in `docs/SECURITY.md` Section 10 and `docs/PRD.md` Section 4.2 — not an oversight, a deliberate scope boundary. Revisit if a real deploying company specifically needs it.

## ADR-007: Vue.js + Laravel + PostgreSQL stack, with Bun as the exclusive frontend package manager

**Date:** Project inception
**Status:** Accepted

**Context:** A competing stack baseline (TypeScript + Bun end-to-end, no PHP) existed from the project owner's general governance template used across other unrelated projects. The chat feature's natural implementation (Laravel Reverb) drove reconsideration.

**Decision:** Vue.js (frontend) + Laravel (backend) + PostgreSQL, matching the pattern already proven in the project owner's other project (`zhire`) and in the academic reference material. Bun replaces npm/yarn/pnpm for the frontend specifically — backend dependency management stays on Composer as normal for Laravel.

**Rejected alternative:** Full TypeScript+Bun stack (no Laravel) — rejected once Laravel Reverb was identified as the natural fit for the real-time chat requirement, and because Laravel's built-in Flysystem (storage abstraction) and queue system directly serve other already-decided requirements (portable storage, async notifications).

## ADR-008: Dual deployment — Railway for development, Docker Compose for production delivery

**Date:** Project inception
**Status:** Accepted

**Decision:** Use Railway during the current development phase for fast iteration. For actual delivery to a licensing company, ship a self-contained Docker Compose stack that can run on any VPS or on-premise server — not tied to Railway as a platform.

**Rationale:** Directly follows from ADR-002 (one-time delivery, not hosted SaaS) — a company receiving this software needs to run it on infrastructure of their choosing, which Railway-specific deployment would not support without modification.

## ADR-009: Object storage — S3-compatible (MinIO default), swappable via Flysystem

**Date:** Project inception
**Status:** Accepted

**Decision:** Default to self-hosted MinIO for object storage (CVs), but implement entirely through Laravel's Flysystem S3-compatible driver so a deployment can swap to Cloudflare R2 or AWS S3 purely via environment variable changes.

**Rationale:** Avoids vendor lock-in consistent with the portable/self-hosted delivery model (ADR-008). Matches a pattern already used in the project owner's other project (`zhire`/`Zovault`).

## ADR-010: Email — Resend for production, Mailpit for development

**Date:** Project inception
**Status:** Accepted

**Context:** Considered requiring each deploying company to provide their own SMTP server (as the original academic reference assumed), versus bundling a specific provider.

**Decision:** Resend for production sending, Mailpit for local development (catches emails locally without sending real mail).

**Rejected alternative:** Generic "bring your own SMTP" requirement — rejected in favor of a baseline that works out of the box; a deploying company can still swap to their own SMTP via Laravel's standard mail configuration if they prefer, without code changes.

## ADR-011: Branding configured via environment variables only, no admin settings UI

**Date:** Project inception
**Status:** Accepted

**Decision:** `APP_NAME` and `APP_LOGO_URL` environment variables control per-deployment branding. Changing them requires an application restart. No in-app settings page for HR to change branding live.

**Rationale:** Deliberately kept simple — building a live-editable settings UI (with its own storage, validation, and access control) was judged unnecessary complexity for a value that's set once at deployment time and rarely changed afterward.

## ADR-012: Repository name `e-recruitment`, distinct from displayed product branding

**Date:** Project inception
**Status:** Accepted

**Decision:** The GitHub repository and codebase are named `e-recruitment`. The name shown to end users in any given deployment is controlled separately via `APP_NAME` (see ADR-011) — a deploying company brands the running instance as their own.

## ADR-013: License — MIT

**Date:** Project inception
**Status:** Accepted

**Decision:** MIT license for the public repository, chosen over the initially-open Apache-2.0/MIT options.

## ADR-014: AGENTS.md lives at repository root and is fully public

**Date:** Project inception
**Status:** Accepted

**Context:** `AGENTS.md` could have lived inside the gitignored `.agents/` directory alongside `skills/` and `prompts/`, keeping it private.

**Decision:** `AGENTS.md` is placed at the repository root and is committed to git — fully visible in the public repository, unlike `.agents/skills/` and `.agents/prompts/`, which remain local-only (gitignored).

**Rationale:** Explicit project owner choice — the AI agent governance rules themselves are not considered sensitive/competitive, unlike the detailed per-phase prompts and the locally-copied skill playbooks.

## ADR-015: Frontend animation via GSAP + Vue Transition, not Nuxt-specific libraries

**Date:** Project inception
**Status:** Accepted

**Context:** A visual design reference document from an unrelated project ("Zinkly") specified Inspira UI / Nuxt UI / Reka UI for animation — all Nuxt-ecosystem-coupled libraries that don't apply since this project uses Vue.js without Nuxt (see ADR-007).

**Decision:** Adapt the *principles* from that reference (color tokens, typography rules, icon discipline, when-animation-belongs-where) into `docs/DESIGN-SYSTEM.md`, but replace the Nuxt-specific implementation libraries with **GSAP** (complex/orchestrated animation) and Vue's built-in `Transition`/`TransitionGroup` (standard UI transitions). Base headless components use Headless UI (Vue) or Reka UI specifically because both are framework-agnostic, not Nuxt-exclusive.

## ADR-016: Phase breakdown — 7 phases (0 through 6)

**Date:** Project inception
**Status:** Accepted

**Context:** An initial 10-phase breakdown (one phase per functional module) was proposed, then judged too granular.

**Decision:** Consolidated to 7 phases grouping related modules into single units of work: Phase 0 (setup), Phase 1 (Auth + Job Management), Phase 2 (Applications + Screening), Phase 3 (Notifications + Interview Scheduling), Phase 4 (Real-time Chat), Phase 5 (Reporting), Phase 6 (Hardening & Deployment). Full detail in `docs/ROADMAP.md`.

**Rationale:** Each phase should represent a coherent, substantial unit of work suitable for a single AI agent session (see `AGENTS.md` Section 11 — phases run in separate, memory-isolated sessions), without being so small that the mandatory full-read-before-write overhead (Section 1) dominates the actual work done.
