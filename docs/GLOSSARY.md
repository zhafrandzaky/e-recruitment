# GLOSSARY.md — Domain Terminology

**Project:** e-recruitment
**Version:** 1.0

This glossary defines domain-specific terms used throughout `docs/` and the codebase. Terms are listed in both Indonesian and English where the project documentation uses both (requirement documents lean Indonesian per academic origin; code/technical docs lean English).

| Term (ID) | Term (EN) | Definition |
|---|---|---|
| Pelamar | Applicant | External, public user who browses job postings and submits applications. |
| HR Admin | HR Admin | Internal company staff who manage job postings and screen applications. Single role type — see `docs/DECISIONS.md` for why no sub-roles exist currently. |
| Lowongan (Pekerjaan) | Job Posting | A single open position published by HR, with title, description, qualifications, deadline. |
| Lamaran | Application | A single applicant's submission for one specific job posting — includes CV and form data. |
| Seleksi Berkas | Document/CV Screening | The process of HR reviewing an applicant's CV and supporting data to decide Shortlisted/Rejected. |
| Status Lamaran | Application Status | One of: Pending (Menunggu), Shortlisted (Lolos Seleksi Berkas), Rejected (Ditolak), Hired (Diterima). `hired` is the terminal accepted state added in Phase 5 for time-to-hire reporting — see `docs/DECISIONS.md` ADR-026. |
| Single-tenant | Single-tenant | Architecture where one deployment instance serves exactly one company — no shared infrastructure or data across companies. Contrast with multi-tenant (e.g. `zhire`, an unrelated project). |
| Tenant | Tenant | Not used in this system's architecture — included here only to clarify its *absence* is intentional (see `docs/ARCHITECTURE.md` Section 4). |
| CV | CV / Resume | Curriculum Vitae, submitted as a PDF file, max 2MB (see `docs/NFR.md` NFR-008). |
| ATS | Applicant Tracking System | The general category of software this project belongs to. |
| Thread Chat | Chat Thread | A single real-time conversation tied to one specific `Application` — not a general-purpose messaging feature. |
| Interview | Interview | A scheduled meeting between HR and an Applicant, conducted via a meeting link from an external platform (Google Meet, Zoom, etc.) **entered manually by HR** — not embedded in this application and not auto-generated via any API (see `docs/DECISIONS.md` ADR-024). |
| Reporting/Dashboard | Reporting/Analytics | Aggregate views for HR: applicant counts, selection funnel, time-to-hire. |
| Funnel Seleksi | Selection Funnel | The distribution of applications across status stages (Pending/Shortlisted/Rejected) for a given job posting, used in reporting. |
| Time-to-Hire | Time-to-Hire | The average number of days from a job posting's creation to an applicant reaching the final accepted status `hired` (Diterima). Computed from the earliest `application_status_history` row with `new_status = 'hired'`. |
| ADR | Architecture Decision Record | A dated, reasoned record of a significant technical decision, logged in `docs/DECISIONS.md`. |
| FR / NFR | Functional / Non-Functional Requirement | See `docs/FR.md` and `docs/NFR.md` respectively. |
| Branding Configurability | Branding Configurability | The system's ability to display a different product name/logo per deployment via environment variables, without code changes (see `docs/ARCHITECTURE.md` Section 6). |
| Self-contained deployment | Self-contained deployment | A Docker Compose stack bundling all services (app, database, cache, storage) that a company can run independently on their own infrastructure (see `docs/ARCHITECTURE.md` Section 5.2). |
