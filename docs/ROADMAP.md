# ROADMAP.md — Phase Plan & Status

**Project:** e-recruitment
**Version:** 1.0

This roadmap tracks phase status. Each phase has a corresponding detailed prompt at `.agents/prompts/phase-N.md` (local-only, not in this public document) and, once started, a corresponding branch named `phase-N/short-description` (see `AGENTS.md` Section 2).

## Status Legend
- **Not started** — no work has begun
- **In progress** — branch exists, work underway
- **Ready for review** — implementation complete, awaiting human review/merge
- **Merged** — completed and merged to `main`

## Phases

| Phase | Scope | Status |
|---|---|---|
| **Phase 0** | Project setup: monorepo structure, governance files, `copy-skills.sh` execution, logo color variants, Docker Compose (dev), Laravel + Vue.js scaffolding | Not started |
| **Phase 1** | Authentication (login, lockout, password reset) + Job Posting management (HR CRUD, public listing/search) + Landing page publik di `/` dengan statistik live (`GET /public/stats`) + halaman 404 | Not started |
| **Phase 2** | Application submission (CV upload to S3-compatible storage, application form) + Screening (HR review, status changes). E2E test untuk flow ini ditunda ke Phase 6 (ADR-022). | Ready for review |
| **Phase 3** | Automated notifications (email on status change) + Interview scheduling (manual meeting link — no external Calendar/Meet API, see ADR-024) | Not started |
| **Phase 4** | Real-time chat (Laravel Reverb setup, per-application chat thread UI) | Not started |
| **Phase 5** | Reporting/Analytics dashboard (applicants per posting, selection funnel, time-to-hire). Added `hired` status for accurate time-to-hire; dependency-free charts (ADR-026). | Ready for review |
| **Phase 6** | Hardening & deployment: security review against `docs/SECURITY.md`, production-ready Docker Compose, dual deployment verification, final documentation pass. **Setup Playwright E2E testing infrastructure + GitHub Actions CI workflow yang menjalankannya, termasuk menulis E2E test yang seharusnya sudah dibuat sejak Phase 2 (application submission flow) dan ditunda ke sini (lihat `docs/DECISIONS.md` ADR-022).** **Evaluasi dan migrasi token auth dari localStorage ke pendekatan yang lebih aman (cookie-based Sanctum SPA auth, BFF proxy, atau short-lived token + silent refresh).** Phase 4 (chat) dan Phase 5 (reporting) akan dibangun di atas pola auth localStorage saat ini — migrasi di Phase 6 perlu mempertimbangkan dampak susulan ke kode yang sudah ada. **Tulis UI test untuk halaman Vue Phase 1–4 yang belum tercakup, lalu aktifkan coverage gate frontend.** `bun run test:coverage` (threshold 80% di `apps/web/vite.config.ts`) saat ini gagal karena halaman Vue dari Phase 1–4 belum punya test UI (0% coverage pada `HrJobListPage`, `HrApplicantListPage`, `HrJobFormPage`, `ApplyPage`, `JobListPage`, `JobDetailPage`, dan composables seperti `useJobs`/`useInterviews`). Ini bukan bug dan belum pernah ditegakkan oleh CI manapun sejak awal project (belum ada `.github/workflows`). Kode Phase 5 sendiri sudah 90–100% coverage. Tindak lanjut Phase 6: tulis test untuk halaman/composable Phase 1–4 yang belum tercakup hingga threshold 80% terpenuhi, sebagai bagian hardening sebelum coverage gate benar-benar ditegakkan di CI (sejalan dengan setup CI dari ADR-022). | Not started |

## Module-to-Phase Mapping

| FR Module (`docs/FR.md`) | Phase |
|---|---|
| Modul 1: Autentikasi & Manajemen Akun | Phase 1 |
| Modul 2: Lowongan Pekerjaan | Phase 1 |
| Modul 9: Landing Page Publik (FR-019, FR-020) | Phase 1 |
| Modul 3: Lamaran | Phase 2 |
| Modul 4: Seleksi Pelamar | Phase 2 |
| Modul 5: Notifikasi | Phase 3 |
| Modul 6: Penjadwalan Interview | Phase 3 |
| Modul 7: Chat Real-time | Phase 4 |
| Modul 8: Reporting & Analytics | Phase 5 |

## Future Considerations (Not Yet Scheduled)

These are explicitly **not** in the current 7-phase plan. If any of these becomes a real requirement (e.g. a specific deploying client needs it), it should be evaluated, scoped, and added here as a new phase — with a corresponding ADR in `docs/DECISIONS.md` explaining the change — rather than silently folded into an existing phase's scope.

- Granular HR role types (Recruiter vs. Hiring Manager) — see `docs/DECISIONS.md` ADR-006
- In-app branding settings UI (currently env-var only) — see `docs/DECISIONS.md` ADR-011
- Multi-stage interview support (beyond one active interview per application) — see `docs/SCHEMA.md` note on the `interviews` table's unique constraint
- Audit log beyond application status history — see `docs/SECURITY.md` Section 9
