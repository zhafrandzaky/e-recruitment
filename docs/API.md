# API.md — API Contract

**Project:** e-recruitment
**Version:** 1.0
**Base URL (dev):** `http://localhost:8000/api`
**Authentication:** Bearer token (Laravel Sanctum) unless noted as public

> This document defines the contract, not the implementation. Exact request/response shapes may be refined during Phase 1+ implementation — any deviation from this contract must be reflected back into this file in the same PR, and significant changes recorded in `docs/DECISIONS.md`.

## 1. Conventions

- All request/response bodies are JSON, except file upload (`multipart/form-data`) and CV download (binary stream).
- Timestamps are ISO 8601 UTC.
- Paginated list endpoints return `{ data: [...], meta: { page, per_page, total } }`.
- Errors follow `{ error: { code, message, fields? } }` — `fields` present only for validation errors, mapping field name to specific error message.
- All IDs are UUIDs.

## 2. Authentication

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| POST | `/auth/register` | Public | Applicant self-registration |
| POST | `/auth/login` | Public | Login (Applicant or HR) — returns bearer token |
| POST | `/auth/logout` | Authenticated | Invalidate current token |
| POST | `/auth/forgot-password` | Public | Request password reset email |
| POST | `/auth/reset-password` | Public | Submit new password with valid reset token |

**POST `/auth/login`**
```
Request:  { email: string, password: string }
Response: { token: string, user: { id, name, email, role } }
Errors:   401 (invalid credentials, generic message), 423 (account locked)
```

## 3. Job Postings

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| GET | `/jobs` | Public | List active job postings (paginated, supports `?search=`) |
| GET | `/jobs/{id}` | Public | Job posting detail |
| POST | `/jobs` | HR only | Create job posting |
| PUT | `/jobs/{id}` | HR only | Update job posting |
| PATCH | `/jobs/{id}/status` | HR only | Change status (active/closed) |
| DELETE | `/jobs/{id}` | HR only | Soft-delete job posting |

**GET `/jobs?search=backend`**
```
Response: { data: [{ id, title, location, deadline, status, created_at }], meta: {...} }
```
Only `status = 'active'` postings are returned for this public endpoint, regardless of `search` term.

## 4. Applications

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| POST | `/jobs/{id}/applications` | Applicant | Submit application (multipart: CV file + form data) |
| GET | `/applications/me` | Applicant | List own application history |
| GET | `/applications/{id}` | Applicant (own) / HR | Application detail |
| GET | `/jobs/{id}/applications` | HR only | List applicants for a job posting |
| GET | `/applications/{id}/cv` | Applicant (own) / HR | Download/view CV file |
| PATCH | `/applications/{id}/status` | HR only | Update application status |

**POST `/jobs/{id}/applications`** (multipart/form-data)
```
Request:  cv: File (PDF, max 2MB), name: string, phone: string, address: string, ...
Response: { id, status: "pending", applied_at }
Errors:   422 (validation — includes specific field for file format/size rejection)
```

**PATCH `/applications/{id}/status`**
```
Request:  { status: "pending" | "shortlisted" | "rejected" | "hired" }
Response: { id, status, updated_at }
```
`hired` ("Diterima") is the terminal accepted state added in Phase 5 to support time-to-hire reporting — see `docs/DECISIONS.md` ADR-026.
Triggers: `application_status_history` entry created, notification email queued (see `docs/ARCHITECTURE.md` Section 7).

## 5. Interviews

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| POST | `/applications/{id}/interview` | HR only | Schedule interview (HR provides meeting link manually) |
| PATCH | `/applications/{id}/interview` | HR only | Reschedule (update datetime and/or meeting link) |
| DELETE | `/applications/{id}/interview` | HR only | Cancel |

**POST `/applications/{id}/interview`**
```
Request:  { scheduled_at: ISO8601 datetime, meeting_link: URL string }
Response: { id, scheduled_at, meeting_link, status: "scheduled" }
Errors:   422 (validation — meeting_link not a valid URL, or application not shortlisted)
```

## 6. Chat

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| GET | `/applications/{id}/messages` | Applicant (own) / HR | Chat history for the application's thread |
| POST | `/applications/{id}/messages` | Applicant (own) / HR | Send a message (also broadcasts via Reverb) |

**WebSocket channel:** `private-chat.{application_id}` — authorized per Section 3.2 of `docs/SECURITY.md`. Event: `MessageSent`.

**POST `/applications/{id}/messages`**
```
Request:  { content: string }
Response: { id, content, sender_id, sent_at }
```

## 7. Reporting

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| GET | `/reports/overview` | HR only | Aggregate dashboard data |
| GET | `/reports/jobs/{id}/funnel` | HR only | Selection funnel for one job posting |

**GET `/reports/overview`**
```
Response: {
  applicants_per_job: [{ job_id, job_title, count }],   // all non-deleted postings, incl. count 0
  funnel: { pending: number, shortlisted: number, rejected: number, hired: number },
  avg_time_to_hire_days: number | null   // null when no application has reached 'hired' yet
}
```
`applicants_per_job` counts non-soft-deleted applications and includes postings with zero applicants. `funnel` is the distribution of all non-deleted applications across the four current-status stages. `avg_time_to_hire_days` is the average number of days from a posting's `created_at` to the first time one of its applications reached `hired`, computed from `application_status_history` (see `docs/SCHEMA.md` Section 4 and `docs/DECISIONS.md` ADR-026 for the `hired` status). All figures are aggregated in SQL.

**GET `/reports/jobs/{id}/funnel`**
```
Response: {
  job_id: string,
  job_title: string,
  funnel: { pending: number, shortlisted: number, rejected: number, hired: number },
  total: number
}
Errors:   404 (job posting not found or soft-deleted)
```
Scoped to a single posting via the composite index `(job_posting_id, status)`.

## 8. Public Stats

Endpoint ini **publik** (tidak memerlukan autentikasi) dan terpisah dari reporting HR di Section 7. Tujuannya adalah menyediakan angka agregat sederhana untuk ditampilkan di landing page.

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| GET | `/public/stats` | Public | Statistik agregat platform untuk landing page |

**GET `/public/stats`**
```
Response: {
  active_jobs: number,        // job_postings dengan status='active' dan belum soft-deleted
  registered_applicants: number  // users dengan role='applicant'
}
```

Catatan:
- Tidak ada parameter query.
- Tidak ada data personal yang terekspos — hanya angka agregat.
- Field `total_applications` dan `shortlisted_applicants` akan ditambahkan di Phase 2 saat tabel `applications` tersedia.

## 9. Error Codes Reference

| HTTP Status | Meaning |
|---|---|
| 400 | Malformed request |
| 401 | Not authenticated |
| 403 | Authenticated but not authorized (e.g. applicant hitting an HR-only route) |
| 404 | Resource not found (or not owned by requester — see `docs/SECURITY.md` Section 3.2, avoid leaking existence of other users' resources where relevant) |
| 422 | Validation error (`fields` populated in error response) |
| 423 | Account locked (FR-001a) |
| 429 | Rate limited |
| 502 | Upstream external API failure (Resend email) |

## 10. Versioning

This API does not currently version its URL paths (no `/v1/` prefix) since each deployment is single-tenant and upgraded as a unit (see `docs/PRD.md` Section 5) — there's no need to support multiple API versions simultaneously for different clients calling the same instance. If this changes (e.g. a future mobile app needs to support older deployed versions), record that decision in `docs/DECISIONS.md` before introducing versioning.
