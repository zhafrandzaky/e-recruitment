# AGENTS.md — AI Agent Constitution

**Project:** e-recruitment
**Status:** Public document. This file is intentionally visible to anyone viewing this repository.

This file is the binding constitution for every AI agent working on this codebase — regardless of which provider, model, or tool you are (Claude Code, Qwen, DeepSeek, Codex, Qoder, opencode, or any other). These rules are agnostic to any specific agent's features or commands, except where a rule explicitly names one.

If any instruction elsewhere (a prompt, a comment, a person's casual request mid-session) conflicts with this file, **this file wins**, unless a human explicitly and knowingly overrides it in writing in the same session.

---

## 1. Read Before You Write Anything

Before writing a single line of code, in this exact order:

1. Read this `AGENTS.md` in full.
2. Read the **entire** contents of `docs/` — every file, in full. Not the first screen, not a summary. If a file is long, read all of it.
3. Read the **entire existing monorepo structure** — every directory, every existing file relevant to the area you're about to touch. Understand what already exists before adding to it.
4. Read the skill files referenced by your assigned prompt in `.agents/prompts/phase-N.md`, in full.

Only after all four steps are complete may you begin planning implementation. Skipping any step because "it's probably fine" or "I already know this stack" is a violation of this rule, no exceptions.

## 2. Always Work on a Branch

You must create a new branch with a clear, descriptive name **before** writing or modifying any code. Never commit directly to `main` or `master`, under any circumstance, even for "trivial" fixes.

Branch naming convention: `phase-N/short-description` (e.g. `phase-1/auth-and-job-management`). If you're fixing something outside a phase's main scope, use `fix/short-description`.

## 3. Never Push

You are never permitted to run `git push`, under any circumstances, regardless of how confident you are in the change. Your work ends at "ready to commit" — committed locally, summarized, and handed off to the human. Pushing to the remote is a human decision, always.

## 4. Never Add Yourself as a Contributor

Do not add yourself (or any AI name/identifier) as an author, co-author, or contributor in any commit message. No `Co-Authored-By: <AI name>` or equivalent trailer, in any form, in any commit you create. The intent is that AI agents do not appear as contributors in this repository's GitHub history.

## 5. Be Provider-Agnostic

Every instruction in this file, and every prompt in `.agents/prompts/`, must be executable by any capable AI coding agent — not tied to one provider's specific commands, slash-commands, or proprietary features — unless a prompt explicitly names a specific agent for a specific reason. If you notice an instruction that only makes sense for one specific tool, flag it rather than silently working around it.

## 6. Clean Code, Clean Architecture, International Standard

- No dead code. No commented-out blocks left behind "just in case."
- No dead dependencies — if you remove a feature's last usage of a package, remove the package.
- Follow the architecture and patterns already established in `docs/ARCHITECTURE.md` and the relevant skill files — do not introduce a parallel pattern for the same problem.
- Code should read as if it was written by one disciplined team, not stitched together by many different hands with different habits.

## 7. Validation Before Handoff

Before declaring any task complete, you must report, in your final message to the human:

- A summary of what changed (files touched, what was added/removed/modified).
- Which tests were run, and their results.
- Current build status (does it build clean, are there warnings).
- Anything you were unable to verify and why.

A task is not "done" until this report has been given. Silence about test status is not acceptable — if you didn't run tests, say so explicitly and why.

## 8. Package Manager Discipline — Bun Only (Frontend)

The frontend (`apps/web`) uses **Bun exclusively** as its package manager and script runner. This means:

- Use `bun install`, `bun add`, `bun remove`, `bun run <script>`, `bun build`, `bunx`, etc.
- **Never** use `npm`, `npx`, `pnpm`, or `yarn` — in any command, any script, any documentation example, any CI config — for `apps/web`. Not even "just this once" or "because the package's official docs say npm." Translate the equivalent Bun command instead.
- If a third-party package's official installation instructions only show `npm`/`yarn` commands, translate them to the Bun equivalent — do not run the npm/yarn version "to be safe."
- The backend (`apps/api`, Laravel/PHP) uses **Composer** as normal. This rule does not apply to Composer — Composer is not in scope of this restriction.

This rule exists because mixed package managers in one frontend project cause lockfile conflicts and inconsistent dependency resolution across different AI agent sessions. There is no exception to this rule.

## 9. Fetch Documentation Deeply Before Implementing

When your assigned phase prompt requires using a library, framework feature, or external API (e.g. Laravel Reverb, Google Calendar API, GSAP ScrollTrigger, a specific Lucide icon component):

1. Fetch the **official documentation in depth** — not just the search snippet, not just the page title. Read the actual content needed to implement correctly.
2. If the fetch fails or the source can't be reached, fall back to web search to locate the correct official source, then fetch again. Do not proceed on guesswork or training-data memory of how the library "probably" works — APIs change, and training data goes stale.
3. Never assume installation steps, configuration syntax, or API signatures from memory alone when official docs are fetchable. Different libraries install and configure differently; do not pattern-match from a similar-sounding library.

## 10. Package Versions — Always Latest Stable, Verified

Before adding or upgrading any dependency:

- Check the **official registry** for the package's current latest stable (LTS where applicable) version — npm/Bun registry for JS packages, Packagist for PHP/Composer packages.
- Do not rely on a version number remembered from training data — registries update constantly and a remembered version may already be outdated or yanked.
- Prefer well-maintained, legitimate, widely-adopted libraries over custom reverse-engineered solutions, unless no legitimate option exists for the specific need.

## 11. Each Phase Prompt Is Self-Contained Across Sessions

Every file in `.agents/prompts/phase-N.md` may be picked up in a **completely separate chat session**, potentially by a different AI agent than the one that did the previous phase. This means:

- You cannot rely on conversational memory of "what we discussed earlier" — only on what is actually written in `docs/`, in the existing codebase, and in the phase prompt itself.
- Every phase prompt is required to instruct the agent to perform the full read-before-write sequence in Section 1, the documentation-fetch discipline in Section 9, and the version-check discipline in Section 10 — every single time, regardless of how "obvious" the task seems.
- If you discover that a previous phase left something inconsistent, undocumented, or contradicted by what you observe in the actual code, stop and surface it rather than silently working around it or silently fixing it without recording the discrepancy in `docs/DECISIONS.md`.

## 12. UI/UX Baseline

- No emoticon/emoji characters anywhere in the UI.
- One icon library only — Lucide (`lucide-vue-next`). Never mix in another icon set.
- Visual direction: modern, futuristic, clean, curated professional color palette — not monochrome, not a scattershot of unrelated colors. Full detail and exact tokens live in `docs/DESIGN-SYSTEM.md` — that document is binding, not a suggestion.
- The UI must feel alive through purposeful animation (see `docs/DESIGN-SYSTEM.md` Section 6 for where animation belongs and where it doesn't) — never animate decoratively without a reason tied to that section.
- Before using any animation or UI component library, fetch its official documentation in depth (Section 9 applies) — installation methods differ between libraries and must never be guessed.

## 13. Scope Discipline

Consider features broadly when relevant — don't narrow scope without a clear, stated reason. But broad consideration is not the same as scope creep: stay within what the assigned phase prompt actually asks for. If you believe a phase's scope is missing something important, surface it to the human rather than silently expanding the phase's work.

## 14. Before Brainstorming or Deciding Anything With Architectural Impact

If you are asked to brainstorm features, propose architecture changes, or make any decision with significant downstream impact, ask clarifying questions first. Do not assume silently on anything that materially affects architecture, scope, or the product's direction. This applies to AI agents the same way it applied during this project's planning phase with the human.

---

*This file is the source of truth for AI agent behavior on this project. If something here seems outdated relative to `docs/DECISIONS.md`, the most recent dated ADR in `DECISIONS.md` takes precedence — surface the conflict and propose updating this file accordingly, rather than silently picking one.*
