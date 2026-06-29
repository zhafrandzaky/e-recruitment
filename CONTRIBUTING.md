# Contributing to e-recruitment

Thanks for your interest in contributing. This document covers how to propose changes, the standards your contribution is expected to meet, and how the review process works.

## Before you start

- Read [`README.md`](README.md) for a project overview.
- Read [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) and [`docs/DECISIONS.md`](docs/DECISIONS.md) to understand why the system is built the way it is, before proposing a structural change.
- If you are an AI coding agent, [`AGENTS.md`](AGENTS.md) is binding and takes precedence over anything in this file that conflicts with it.

## Reporting bugs / requesting features

Use the issue templates in `.github/ISSUE_TEMPLATE/`. Include enough detail to reproduce a bug (steps, expected vs actual behavior, environment) or to evaluate a feature request (problem it solves, who it affects).

## Making changes

1. **Fork and branch.** Branch off the latest `main`. Use a descriptive branch name (e.g. `fix/cv-upload-validation`, `feature/interview-reschedule`).
2. **Keep changes focused.** One logical change per pull request. Unrelated cleanup belongs in its own PR.
3. **Follow existing patterns.** Match the architecture and conventions already established in the codebase and documented in `docs/`. If you think an existing pattern should change, open a discussion first — don't introduce a parallel pattern silently.
4. **No dead code.** Remove commented-out blocks and unused code as part of your change, not as a follow-up "later."
5. **Package manager discipline.** The frontend (`apps/web`) uses **Bun exclusively** — no npm, pnpm, or yarn commands, lockfiles, or examples. The backend (`apps/api`) uses Composer as normal.
6. **Write/update tests.** All new functionality needs test coverage per [`docs/TESTING.md`](docs/TESTING.md). All existing tests must still pass.
7. **Update documentation.** If your change affects requirements, schema, API contracts, or architecture, update the relevant file in `docs/` in the same PR — documentation drift is treated as a defect, not a follow-up task.

## Commit messages

Write clear, descriptive commit messages explaining *why*, not just *what*. Do not add AI tool co-author trailers (e.g. `Co-Authored-By: <AI name>`) to any commit, regardless of how the change was produced — see [`AGENTS.md`](AGENTS.md) Section 4.

## Pull request process

1. Fill out the PR template completely, including a summary of changes, tests run, and build status.
2. Ensure CI passes before requesting review.
3. A maintainer will review for correctness, architectural fit, and adherence to `docs/`. Expect feedback — review comments are about the code, not the contributor.
4. Once approved, a maintainer will merge. Contributors should not merge their own PRs unless explicitly granted permission.

## Code of conduct

All contributors are expected to follow [`CODE_OF_CONDUCT.md`](CODE_OF_CONDUCT.md).

## Questions

Open a discussion or issue if anything in this document or the project's documentation is unclear — unclear contribution guidelines are themselves worth fixing.
