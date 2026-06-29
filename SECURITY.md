# Security Policy

This document covers how to report a security vulnerability in **e-recruitment**. For the internal threat model, authentication design, and security controls used inside the system, see [`docs/SECURITY.md`](docs/SECURITY.md) — this file is specifically about *responsible disclosure*, not internal architecture.

## Reporting a Vulnerability

If you discover a security vulnerability in this project, please report it privately rather than opening a public issue. Public issues are visible to everyone immediately, including potential bad actors, before a fix is available.

**Please do not:**
- Open a public GitHub issue describing the vulnerability.
- Disclose the vulnerability publicly (blog posts, social media, mailing lists) before it has been addressed.

**Please do:**
- Use GitHub's private vulnerability reporting feature on this repository (Security tab → "Report a vulnerability"), if enabled.
- Include as much detail as possible: steps to reproduce, affected version/commit, potential impact, and any suggested remediation.

## What to Expect

- Acknowledgment of your report within a reasonable timeframe.
- An assessment of severity and a plan for remediation.
- Credit in the eventual fix's changelog entry, if you would like it (let us know your preference when reporting).

## Supported Versions

As this project is distributed as a one-time licensed deployment per company (see [`docs/PRD.md`](docs/PRD.md) for the distribution model), there is no centrally hosted "latest version" that all deployments automatically receive. Security fixes are published as new releases; each deploying organization is responsible for applying updates to their own instance. See [`docs/ROADMAP.md`](docs/ROADMAP.md) for release status.

## Scope

This policy covers the `e-recruitment` codebase itself. It does not cover:
- Vulnerabilities in third-party dependencies (report those upstream to the relevant project, though we welcome a heads-up so we can update our dependency).
- Misconfiguration of a specific deployment that deviates from the documented setup in [`docs/ENVIRONMENT.md`](docs/ENVIRONMENT.md) and [`docs/SECURITY.md`](docs/SECURITY.md).
