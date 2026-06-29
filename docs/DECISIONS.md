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

## ADR-017: Typography — Inter (variable font, self-hosted via @fontsource-variable/inter)

**Date:** Phase 0 implementation
**Status:** Accepted

**Context:** `docs/DESIGN-SYSTEM.md` Section 4 intentionally left the specific font name open ("Inter or equivalent") pending a Phase 0 implementation-time check for current licensing, self-hosting method, and performance characteristics.

**Decision:** Use **Inter** (variable font) as the sole typeface, loaded via `@fontsource-variable/inter` — an npm package that self-hosts the font files without any external network request at runtime.

**Rejected alternatives:**
- System font stack — insufficient brand consistency across platforms.
- Google Fonts CDN — adds an external network dependency per page load, and requires CSP adjustment.
- Inter via CDN (Bunny, jsDelivr) — same dependency concern as Google Fonts.

**Why @fontsource-variable/inter specifically:** Packages the WOFF2 variable font files directly into the build output via Vite's asset pipeline, resulting in fully self-hosted and cache-controlled font delivery — consistent with the portable/self-hosted delivery model (ADR-008) and the security posture in `docs/SECURITY.md`.

## ADR-018: Headless UI component library — Reka UI

**Date:** Phase 0 implementation
**Status:** Accepted

**Context:** `docs/DESIGN-SYSTEM.md` Section 6.3 and `docs/ARCHITECTURE.md` Section 10 allow either Headless UI for Vue or Reka UI, instructing the implementing agent to check current maintenance status and documentation quality before picking one.

**Decision:** Use **Reka UI** (v2.10.1) as the headless component library for modals, dropdowns, accordions, tabs, and other unstyled primitives.

**Comparison at time of decision (2026-06-29):**
- Reka UI: actively maintained, ~4000 GitHub stars, comprehensive Vue-native primitives, WAI-ARIA compliant, covers full set of components needed (dialog, dropdown, accordion, tabs, select, tooltip, popover). Built specifically for Vue 3, not a Vue port of a React library.
- Headless UI Vue: maintained by Tailwind Labs, fewer components (focuses on menu/listbox/combobox/disclosure/dialog/switch/radio), less Vue-native in its API, slower update cadence for Vue vs React counterpart.

**Rejected alternative:** Headless UI Vue — fewer components, relies on Tailwind Labs' React-first priorities for updates, less Vue-idiomatic API surface.

## ADR-019: Tailwind CSS v4 (not v3)

**Date:** Phase 0 implementation
**Status:** Accepted

**Context:** Tailwind CSS released v4 in 2025. Configuration syntax changed significantly — no more `tailwind.config.js`, CSS-first configuration via `@import "tailwindcss"` and `@variant`.

**Decision:** Use **Tailwind CSS v4** with the `@tailwindcss/vite` plugin. Configuration is CSS-first in `src/style.css` with `@import "tailwindcss"` and `@variant dark` for `.dark` class-based dark mode.

**Rejected alternative:** Tailwind v3 — older API, requires `tailwind.config.js` and PostCSS pipeline, no reason to use the older version for a new project in 2026.

**Migration note for future agents:** Tailwind v4 utility classes are largely backward-compatible with v3 but the configuration mechanism is entirely different. Do not attempt to generate a `tailwind.config.js` — it is not used in v4. Dark mode is configured via `@variant dark (&:where(.dark, .dark *))` in CSS.

## ADR-020: Password hashing algorithm — bcrypt

**Date:** Phase 1 implementation
**Status:** Accepted

**Context:** `docs/SECURITY.md` Section 2.1 deferred the bcrypt vs. argon2 choice to Phase 1 implementation. Laravel 13 supports both via its hashing abstraction. The `.env` from Phase 0 already shipped with `BCRYPT_ROUNDS=12`.

**Decision:** Use **bcrypt** with 12 rounds (Laravel's default, already configured via `BCRYPT_ROUNDS=12` in `.env`). No additional hashing configuration changes required — Laravel's default `Hash` facade uses bcrypt automatically.

**Rejected alternative:** Argon2id — memory-hard and generally considered more future-proof against GPU-based attacks, but:
1. The performance difference at this scale (single-tenant, low-concurrent-auth) is negligible.
2. Bcrypt at 12 rounds is accepted production-grade security for password storage.
3. The Phase 0 environment already configured `BCRYPT_ROUNDS=12`, establishing a clear intent.
4. No PHP extension changes are needed; bcrypt works out of the box on all supported PHP versions.

If the project scales significantly or GPU-based cracking becomes a realistic threat model, revisit and migrate to Argon2id — the Laravel `Hash` abstraction makes this a config-only change.

## ADR-021: Root path `/` menjadi landing page perusahaan, bukan redirect ke `/jobs`

**Date:** Phase 1 (post-implementation fix)
**Status:** Accepted

**Context:** Saat Phase 1 mengimplementasikan router Vue.js, path root `/` didefinisikan sebagai `redirect: '/jobs'` tanpa pertimbangan desain eksplisit — hanya asumsi implementasi. Tidak ada ADR yang dicatat, dan tidak ada dokumen di `docs/` yang menyebutkan bahwa root harus mengarah ke job listing. Setelah Phase 1 selesai, inkonsistensi ini diidentifikasi: `docs/DESIGN-SYSTEM.md` Section 6.1 menyebut "landing page perusahaan" sebagai entitas terpisah dengan karakter animasi berbeda dari `/jobs`, namun tidak pernah diimplementasikan.

**Decision:** Path root `/` menjadi **landing page publik perusahaan** yang terpisah dari job listing di `/jobs`. Landing page berisi: hero section, tentang perusahaan, benefit kerja, statistik live dari database (jumlah lowongan aktif, pelamar terdaftar), dan CTA ke `/jobs`. Konten teks statis di kode (konsisten dengan pendekatan branding env-var-only per ADR-011 — tidak ada CMS atau admin panel untuk konten landing page). Endpoint publik baru `GET /public/stats` menyediakan statistik agregat tanpa autentikasi.

**Catch-all route** yang sebelumnya diam-diam mengarah ke `/jobs` diganti dengan halaman 404 yang proper.

**Rejected alternative:** Tetap `redirect: '/' → '/jobs'` — ditolak karena menghilangkan entry point marketing yang sudah disebut di `docs/DESIGN-SYSTEM.md`, dan menyembunyikan keputusan desain (tidak ada landing page) sebagai perilaku diam-diam tanpa dokumentasi.

## ADR-022: E2E test infrastructure (Playwright) dan CI runner ditunda ke Phase 6

**Date:** Phase 2 handoff (2026-06-30)
**Status:** Accepted

**Context:** `docs/TESTING.md` Section 4 mensyaratkan minimal satu E2E test per core user journey, termasuk "an applicant successfully submitting an application end-to-end." Namun, environment E2E testing (Playwright) tidak pernah disetup sejak Phase 0 — direktori `apps/api/test/E2E/` dan `apps/web/test/e2e/` hanya berisi file `.gitkeep` placeholder. Tidak ada `playwright.config.ts`, tidak ada `@playwright/test` di `package.json` manapun, tidak ada browser binary Playwright yang terinstal. Ini adalah gap infrastruktur dari Phase 0, bukan kelalaian Phase 2.

**Decision:** Setup Playwright E2E testing infrastructure dan penulisan E2E test yang seharusnya dibuat di Phase 2 (application submission flow) **ditunda ke Phase 6**, digabung dengan CI/CD pipeline setup yang memang sudah menjadi scope Phase 6.

**Rationale:**
1. E2E test yang ditulis tanpa CI runner yang menjalankannya secara otomatis tidak memberi nilai praktis berarti — ia hanya file statis yang memberi rasa aman palsu. Test yang tidak pernah dijalankan bukanlah test yang bermakna.
2. Phase 6 sudah mencakup "hardening & deployment" termasuk produksi-ready Docker Compose, security review, dan final documentation pass. Menambahkan CI pipeline + E2E test runner ke scope Phase 6 adalah penggabungan yang koheren — semuanya adalah pekerjaan "production readiness" yang memang lebih natural dilakukan saat deployment pipeline sudah konkret.
3. Playwright memerlukan browser binary (~150MB Chromium) yang perlu diinstal di environment CI — setup ini paling efisien dilakukan bersamaan dengan konfigurasi GitHub Actions workflow, bukan sebagai pekerjaan terisolasi di Phase 2.

**Mitigasi sementara untuk Phase 2–5:**
- **Backend**: Feature test (`ApplicationTest`, 28 test; `CvUploadServiceTest`, 6 test) sudah memverifikasi kontrak API end-to-end — HTTP request → middleware → controller → database → JSON response — untuk setiap endpoint Phase 2. Setiap validasi, ownership check, dan efek samping (status history, notification queue) sudah tertutup.
- **Frontend**: TypeScript type-check (`vue-tsc -b`) dan build verification (`vite build`) memastikan tidak ada kesalahan kompilasi atau type mismatch di seluruh component tree.
- **Gap aktual yang tersisa**: Tidak ada yang memverifikasi integrasi frontend-backend sebagai satu kesatuan (mis. form di browser → API call → response dirender dengan benar). Gap ini akan ditutup di Phase 6 saat Playwright E2E test dijalankan melawan full stack yang berjalan.

**Rejected alternative:** Menulis E2E test sekarang (Phase 2) tanpa CI runner — ditolak karena:
1. Test akan menjadi dead code yang hanya bisa dijalankan manual oleh developer yang tahu cara menjalankan Playwright.
2. Tidak ada mekanisme yang memastikan test ini dijalankan sebelum merge ke `main`.
3. Browser binary Playwright yang tidak terinstal di environment siapapun berarti test ini bahkan tidak bisa dijalankan tanpa setup tambahan.
4. Risk: test yang tidak pernah dijalankan cenderung membusuk (test rot) — saat Phase 6 tiba, test yang ditulis sekarang mungkin sudah tidak relevan dengan implementasi yang berevolusi di Phase 3–5.

**Action items untuk Phase 6:**
1. Install Playwright (`@playwright/test`) di `apps/web` via Bun.
2. Install Chromium binary (`bunx playwright install chromium --with-deps`).
3. Buat `playwright.config.ts` dengan target `http://localhost:5173` (Vite dev) dan `http://localhost:8000` (Laravel API).
4. Tulis E2E test untuk flow yang seharusnya dicover di Phase 2 (applicant submission flow) dan flow dari phase-phase lain yang belum sempat di-cover.
5. Setup GitHub Actions workflow yang menjalankan E2E test secara otomatis pada setiap PR.
6. Verifikasi semua E2E test pass di CI sebelum Phase 6 dianggap selesai.

**Implikasi dokumentasi:** FR-019, FR-020 ditambahkan ke `docs/FR.md` (Modul 9); UC-11 ditambahkan ke `docs/USECASE.md`; Section 8 baru ditambahkan ke `docs/API.md`; UIR-005 ditambahkan ke `docs/SRS.md`; `docs/ROADMAP.md` diupdate.

## ADR-023: Google Calendar API — service account credentials, not OAuth web client

**Date:** Phase 3 implementation (2026-06-30)
**Status:** Accepted

**Context:** `docs/ENVIRONMENT.md` initially specified `GOOGLE_CALENDAR_CLIENT_ID` and `GOOGLE_CALENDAR_CLIENT_SECRET` — OAuth web client credentials — for the Calendar/Meet API integration. During Phase 3 implementation, this was reconsidered: OAuth web client credentials require a user-facing consent screen and a refresh token management flow, which is designed for web apps acting *on behalf of a specific user* who consents interactively. For a server-to-server application creating events on a company-owned calendar, the standard Google Cloud approach is a **service account**.

**Decision:** Use a Google Cloud **service account** with domain-wide delegation. The deploying company creates a service account in their Google Cloud project, enables the Calendar API, downloads the JSON key file, and sets `GOOGLE_CALENDAR_CREDENTIALS_PATH` to its absolute path. The service account impersonates the calendar owner (set via `GOOGLE_CALENDAR_ID`, defaults to `primary`).

**Rationale:**
1. Service accounts are the documented Google-recommended approach for server-to-server API calls — no interactive OAuth consent flow, no refresh token management, no token expiration handling in application code.
2. The Google API PHP client (`google/apiclient`) natively supports service account authentication via `setAuthConfig()` — one method call, no custom OAuth dance.
3. Each deploying company already needs their own Google Cloud project (per the existing `docs/ENVIRONMENT.md` Section 3); creating a service account and downloading its key is a standard documented step in the Google Cloud Console, no more complex than creating OAuth credentials.
4. A single JSON key file path (`GOOGLE_CALENDAR_CREDENTIALS_PATH`) replaces two env vars (`CLIENT_ID` + `CLIENT_SECRET`) — simpler configuration, fewer values to misconfigure.

**Rejected alternative:** OAuth web client credentials (`CLIENT_ID` + `CLIENT_SECRET`) — rejected because:
1. Requires implementing OAuth 2.0 refresh token management (initial consent, token storage, refresh-on-expiry) — significant extra code complexity for a supporting integration.
2. OAuth web client flow is designed for apps that act on behalf of many different end users, not a single company calendar; using it for server-to-server is technically possible but not the standard pattern.
3. The `useApplicationDefaultCredentials()` method (ADC) is better suited for Google Cloud environments (GCE, Cloud Run) where credentials are injected by the platform — but the portable Docker Compose deployment model (ADR-008) means ADC isn't reliably available. Explicit JSON key path is more portable.

**Implementation note:** If a future deployment runs on Google Cloud (GCE/GKE/Cloud Run), the service can be trivially adapted to use ADC by removing the `setAuthConfig()` call and adding a fallback — the rest of the integration code remains identical.

**Env var changes:**
- **Removed:** `GOOGLE_CALENDAR_CLIENT_ID`, `GOOGLE_CALENDAR_CLIENT_SECRET`
- **Added:** `GOOGLE_CALENDAR_CREDENTIALS_PATH` (path to service account JSON key file), `GOOGLE_CALENDAR_ID` (email to impersonate, defaults to `primary`)

## ADR-024: Interview scheduling — manual meeting link input, no external Calendar API integration

**Date:** Phase 3 revision (2026-06-30)
**Status:** Accepted — **supersedes ADR-003 and ADR-023**

**Context:** ADR-003 decided to auto-generate Google Meet/Zoom links via external API. Phase 3 initially implemented this with a Google Calendar service account (ADR-023). After implementation, project owner reconsidered the tradeoffs:

1. **Google Workspace dependency**: Service accounts with domain-wide delegation require Google Workspace (not available to free Gmail users), adding a hidden deployment prerequisite that contradicts the portable self-hosted delivery model (ADR-008).
2. **OAuth per-user complexity**: The alternative (OAuth per-HR with individual Google account connections) requires token storage, refresh management, and a "Connect Google Account" UI flow — estimated 9–12 hours of additional work — for a supporting feature, not the product's core value.
3. **Chat as sufficient coordination**: Phase 4's real-time chat (per-application thread) already provides direct HR-applicant communication, making auto-generated meeting links a nice-to-have rather than essential.

**Decision:** **Remove all external Calendar/Meet API integration entirely.** HR manually inputs the meeting link when scheduling an interview — they can use Google Meet (generated manually), Zoom, Microsoft Teams, or any other platform. The system stores and emails the link; it does not create, update, or delete any external calendar events.

**Implications:**
- No Google Cloud project setup required for deployment — simpler onboarding.
- `external_event_id` column dropped from `interviews` table (migration `2026_06_30_000003`).
- `GoogleCalendarService.php` and `google/apiclient` dependency removed.
- Interview scheduling endpoints (`POST`/`PATCH`) now accept `meeting_link` as required URL input, validated for format.
- No 502 "CALENDAR_API_FAILED" error scenario — failure surface reduced to standard validation errors.
- `docs/SEQUENCE-DIAGRAM.md` Alur 2 revised: no external API participant, HR provides link directly.

**Rejected alternative:** Keeping Google Calendar API integration (either Service Account or OAuth per-user) — rejected by project owner for the dependency and complexity reasons above.

**Superseded ADRs:** ADR-003 (original "use external Calendar API" decision) and ADR-023 (service account credential approach) are both superseded by this decision. They remain in the ADR log for historical record but are no longer in effect.
