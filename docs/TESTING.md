# TESTING.md — Testing Standards

**Project:** e-recruitment
**Version:** 1.0

## 1. Principle

All tests must pass before any phase is considered complete (see `AGENTS.md` Section 7 — validation before handoff). "I didn't have time to test this" is not an acceptable handoff state; if something genuinely couldn't be tested, that must be stated explicitly with a reason, not silently omitted.

## 2. Test Organization

Per the governance requirement that test code stays fully separate from source code (not co-located `__tests__` folders), but within the same monorepo:

```
apps/api/test/
├── Unit/          # Isolated logic — model methods, validators, service classes
├── Feature/        # Laravel's term for integration tests — HTTP requests through routes/middleware/controllers
└── E2E/             # End-to-end flows spanning multiple requests/components

apps/web/test/
├── unit/            # Component logic, composables, utility functions in isolation
├── integration/      # Component interaction, store + component integration
└── e2e/               # Full user flows (e.g. via Playwright), against a running app
```

## 3. Backend Testing (Laravel)

- **Unit tests**: Eloquent model methods, custom validation rules, service classes (e.g. `CvUploadService`, `ReportingService`, or the email notification classes) tested with external dependencies (object storage, mail) mocked.
- **Feature tests**: every API endpoint in `docs/API.md` needs at least one Feature test covering the success path and at least one covering a documented error case (validation failure, unauthorized access, not-found).
- **Critical paths requiring explicit test coverage:**
  - File upload validation (FR-007) — test rejection of non-PDF files, oversized files, and files with a spoofed extension/MIME mismatch (ties to `docs/SECURITY.md` Section 4).
  - Account lockout (FR-001a) — test that the 3rd consecutive failure locks the account and that a 4th attempt during cooldown is rejected.
  - Resource ownership (`docs/SECURITY.md` Section 3.2) — test that an applicant cannot access another applicant's application data via direct ID manipulation.
  - Status change side effects (FR-013) — test that updating an application's status creates the corresponding `application_status_history` entry and queues a notification job.
  - Interview scheduling validation (`docs/SEQUENCE-DIAGRAM.md` Alur 2) — test that an invalid `meeting_link` (not a valid URL) is rejected and does not create a partial `interviews` record, and that scheduling requires a shortlisted application (no external Calendar/Meet API call exists — ADR-024).

## 4. Frontend Testing (Vue + Bun)

- Test runner and assertions run via Bun's tooling (`bun test` or a Bun-compatible test runner such as Vitest run through Bun) — never via `npm test`/`npx`, consistent with `AGENTS.md` Section 8.
- **Unit tests**: composables (e.g. form validation logic, date formatting for interview scheduling) tested in isolation.
- **Integration tests**: components that combine state and rendering — e.g. confirm the application form correctly disables submit until both CV and required fields are valid.
- **E2E tests**: at minimum, one E2E test per core user journey: an applicant successfully submitting an application end-to-end, and an HR admin successfully changing an applicant's status end-to-end. Real-time chat (Phase 4) should have an E2E test confirming a message sent by one party appears for the other without a page reload.

## 5. Test Data

- Tests must never depend on real external services (Resend) — mock these at the service boundary. A test suite that fails because an external API was unreachable indicates missing mocking, not a legitimate test failure.
- Use factories/seeders for test data generation (Laravel model factories on the backend) rather than hand-writing repetitive fixture data.

## 6. CI Expectations

- All tests (Unit, Feature/integration, E2E where feasible in CI) run automatically on every pull request via the CI pipeline (see `docker/` and any CI config introduced in Phase 0/6).
- A pull request cannot be merged with failing tests, per `CONTRIBUTING.md` and the PR template's checklist.

## 7. Reporting Test Status (Phase Handoff)

Per `AGENTS.md` Section 7, every phase completion report must explicitly state:
- Which test suites were run (Unit/Feature/E2E, backend/frontend)
- Pass/fail counts
- Any tests skipped or not written, and why

This applies even when the answer is "no new tests were needed because this phase only touched documentation" — say that explicitly rather than omitting the section.
