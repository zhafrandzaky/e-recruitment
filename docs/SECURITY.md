# SECURITY.md (Internal) — Threat Model & Security Controls

**Project:** e-recruitment
**Version:** 1.0

> This document covers **internal security architecture** — the threat model and concrete controls built into the system. For the public vulnerability disclosure process, see [`SECURITY.md`](../SECURITY.md) at the repository root — that is a different, public-facing document.

## 1. Threat Model Overview

This system has two main attack surfaces, given its public-facing nature:

1. **Public, unauthenticated surface** — job listing browsing, search, and the application submission form. This is the highest-risk surface since anyone on the internet can interact with it without an account.
2. **Authenticated surface** — HR dashboard, applicant status pages, chat. Risk here centers on authorization (making sure a user only sees what's theirs) rather than pure unauthenticated abuse.

## 2. Authentication

### 2.1 Password Storage
- Passwords hashed with **bcrypt** (Laravel default) or **argon2** — final algorithm choice recorded as an ADR in `docs/DECISIONS.md` during Phase 1 implementation.
- Never log raw passwords, even at debug level. Never include password fields in error reports or exception traces.

### 2.2 Account Lockout (FR-001a)
- 3 consecutive failed login attempts → account locked for a configurable cooldown period (default 15 minutes, see `docs/ENVIRONMENT.md`).
- Lockout state stored server-side (`users.failed_login_attempts`, `users.locked_until`) — never trust client-side attempt counting.
- Login error messages are **generic** ("Email atau password salah") — never reveal whether the email exists in the system or whether the email or password specifically was wrong. This prevents account enumeration.

### 2.3 Password Reset
- Reset tokens are single-use, time-limited (expire after a short window — see `docs/ENVIRONMENT.md` for the configured duration).
- The "forgot password" endpoint always returns the same success response regardless of whether the submitted email exists in the system — prevents enumeration via response timing/content differences.

### 2.4 Rate Limiting
- Login endpoint: rate-limited per IP and per account to slow down brute-force attempts, independent of the account lockout mechanism (defense in depth).
- Application submission endpoint (public, unauthenticated-adjacent): rate-limited per IP to prevent spam/automated mass-application abuse.
- Password reset request endpoint: rate-limited to prevent email-bombing a target address.

## 3. Authorization

### 3.1 Role-Based Access
- Two roles: `applicant`, `hr_admin` (see `docs/SCHEMA.md` `users.role`). Every authenticated route is guarded by middleware checking the appropriate role.
- HR-only routes (job management, applicant screening, interview scheduling, reporting) reject any request from a user with role `applicant`, regardless of how the request was crafted (not just hidden in the UI — enforced server-side on every request).

### 3.2 Resource Ownership Checks
- An applicant can only view/modify **their own** applications — never another applicant's, even if they guess/enumerate an application ID. Every applicant-facing endpoint that takes an `application_id` must verify `application.applicant_id === current_user.id` before returning data.
- Chat channel authorization (Laravel Reverb private channels): only the specific applicant who owns the `Application` and HR can subscribe to that `ChatThread`'s broadcast channel — verified server-side at channel authorization time, not assumed from the frontend's request.

## 4. File Upload Security (CV)

This is the highest-risk input surface in the system, since it accepts arbitrary files from unauthenticated-adjacent public users.

- **Extension allowlist:** only `.pdf` accepted.
- **MIME type verification:** the actual file content is inspected (not just the client-reported `Content-Type` header or file extension, both of which are trivially spoofable) to confirm it is genuinely a PDF before storage.
- **Size limit:** 2MB maximum, enforced server-side (not just a client-side form attribute, which can be bypassed).
- **Storage isolation:** uploaded files are stored in S3-compatible object storage, not on the application server's local filesystem — this avoids any risk of an uploaded file being placed somewhere it could be executed or served as application code.
- **No execution risk:** the application never executes, includes, or evaluates uploaded file content in any way — it is only ever stored and later served back as a download/view, with explicit `Content-Type: application/pdf` headers on download.
- **Filename handling:** the original filename is stored separately as metadata (`applications.cv_original_filename`) for display purposes; the actual storage key/path is system-generated (e.g. a UUID-based path), never derived directly from user-supplied input — this prevents path traversal or filename-injection issues.

## 5. Input Validation

- All form input validated **server-side** as the authoritative check — client-side validation exists only for UX responsiveness, never trusted as a security boundary.
- Standard protections against:
  - **SQL Injection** — via Eloquent ORM's parameterized queries; no raw SQL string concatenation with user input anywhere in the codebase.
  - **XSS** — Vue's default template escaping handles most output encoding; any use of `v-html` (raw HTML rendering) must be justified and reviewed, since it bypasses this default protection.
  - **SSRF** — the only outbound external API the system calls is Resend (email). No user-supplied URL is ever fetched server-side: the interview `meeting_link` that HR provides is validated for URL **format** only, then stored and emailed — it is never used as the target of an outbound HTTP request (there is no Calendar/Meet API integration; see `docs/DECISIONS.md` ADR-024). External API calls use fixed, code-defined endpoints with only validated parameters substituted in.

## 6. Secrets Management

- All credentials (database password, Redis password, S3 access keys, Resend API key) live in environment variables, never hardcoded in source code or committed to the repository.
- `.env` files are git-ignored; `.env.example` files (committed) document required variable names without real values — see [`docs/ENVIRONMENT.md`](ENVIRONMENT.md).
- Production secrets (Railway phase or Docker Compose self-hosted phase) are injected via the platform's secret management (Railway's environment variable UI, or a `.env` file with restricted filesystem permissions on a self-hosted server) — never baked into a Docker image layer.

## 7. Security Headers

Standard security headers applied at the web server/application level in production:
- `Content-Security-Policy` — restrict script/style sources
- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: DENY` (or equivalent via CSP `frame-ancestors`)
- `Strict-Transport-Security` — enforce HTTPS
- HTTPS enforced end-to-end in production; no plaintext HTTP for authenticated traffic

## 8. Container Isolation

Since this system makes outbound calls to one external service (Resend, for email), but does **not** execute arbitrary user-supplied code or shell out to external tools based on user input, the container isolation requirement is more limited than systems that run subprocesses dynamically. Standard Docker container isolation (non-root user inside containers, minimal base images, no unnecessary host filesystem mounts) is sufficient — there is no dynamic subprocess execution surface in this system's design (see `docs/ARCHITECTURE.md` Section 11 — explicitly out of scope).

## 9. Audit Considerations

- `application_status_history` (see `docs/SCHEMA.md`) serves a dual purpose: reporting (FR-018) **and** a basic audit trail of who changed an applicant's status and when.
- Consider whether a broader audit log (login events, permission changes) is needed as the product matures — not in the current phase scope, but flagged here for future consideration rather than silently omitted.

## 10. Known Limitations (Documented, Not Hidden)

- There is currently no granular permission system beyond the two roles (`applicant`, `hr_admin`) — any HR admin can see/modify any job posting or application within their deployment. This is an accepted simplification for the current scope (see `docs/PRD.md` Section 4.2 and `docs/DECISIONS.md`), not an oversight.
- Branding configuration (`APP_NAME`, `APP_LOGO_URL`) is environment-variable based, not protected by any additional access control beyond server access — this is intentional given the simplicity tradeoff documented in `docs/ARCHITECTURE.md` Section 6.
- **Sanctum API tokens stored in `localStorage`** (keys: `auth_token`, `auth_user`) — this exposes the bearer token to JavaScript access, making it vulnerable to XSS-based token theft. Any script executing on the same origin can read `localStorage` and exfiltrate the token. The standard defense (HttpOnly cookies) is not currently used because Sanctum cookie-based SPA auth requires additional CSP/CORS configuration and a same-domain deployment model. **Remediation scheduled for Phase 6** — will evaluate cookie-based Sanctum auth, BFF proxy pattern, or short-lived token + silent refresh as alternatives. This limitation is documented here (not hidden) so that any security auditor or future developer is aware of the tradeoff before Phase 6 remediation.
