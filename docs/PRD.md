# PRD.md — Product Requirements Document

**Project:** e-recruitment
**Version:** 1.0
**Status:** Draft — Phase 0 planning

## 1. Vision

A company that currently manages recruitment through manual processes (spreadsheets, shared email inboxes, generic form tools) gets a single, self-contained recruitment system: one place where applicants discover and apply to open roles, and HR manages the entire pipeline from posting to interview to hire decision — without paying for a recurring SaaS subscription or sharing infrastructure with any other company.

This is not an attempt to compete with multi-tenant job marketplaces like LinkedIn, Glints, or JobStreet. It solves a narrower, more concrete problem: **a single company needs its own recruitment portal, deployed once, that they fully own.**

## 2. Problem Statement

Small-to-medium companies without a dedicated ATS (Applicant Tracking System) typically run recruitment through:
- A static "Careers" page or social media post for job announcements.
- Email or a generic form tool (Google Forms) for applications.
- A spreadsheet for tracking applicant status.
- Manual email/WhatsApp for scheduling interviews.

This works at small scale but breaks down as applicant volume grows: status tracking becomes inconsistent, CVs get lost across email threads, there's no structured communication channel with candidates, and HR has no visibility into how their pipeline is performing (how many applicants per role, how long selection takes, where candidates drop off).

Existing commercial ATS products solve this, but typically as a recurring-cost SaaS — and the company's data lives in a vendor's shared infrastructure, not their own.

## 3. Target Users

| User | Description |
|---|---|
| **Applicant** | External, public user. Browses open job postings, applies with a CV and basic information, tracks their own application status, communicates with HR about their application, and is invited to interviews. No account-creation friction beyond what's needed to track an application. |
| **HR Admin** | Internal company staff. Manages job postings end-to-end (create, edit, close), reviews incoming applications, makes screening decisions, schedules interviews, communicates with applicants, and views aggregate reporting on their hiring pipeline. |

There is currently one HR role type — no distinction between, e.g., a Recruiter and a Hiring Manager. See [`docs/DECISIONS.md`](DECISIONS.md) for the reasoning and [`docs/ROADMAP.md`](ROADMAP.md) for whether role granularity is considered for a future phase.

## 4. Scope

### 4.1 In Scope (this product)

- Single-tenant deployment: **one instance of this software serves exactly one company.** There is no multi-company aggregation, no shared tenant database, no concept of "Company A's jobs" vs "Company B's jobs" coexisting in the same instance.
- The eight functional modules detailed in [`docs/FR.md`](FR.md): Authentication, Job Listings, Applications, Screening, Notifications, Interview Scheduling, Real-time Chat, and Reporting.
- A distribution model where the software is licensed/delivered once per company (see Section 5), not sold as an ongoing subscription.

### 4.2 Out of Scope (explicitly not this product)

- **Multi-tenant / cross-company job marketplace.** This is not LinkedIn, Glints, or JobStreet. An applicant using one deployment only ever sees that one company's jobs.
- **SaaS subscription billing.** There is no built-in billing, plan management, or usage metering — because the product isn't sold that way.
- **Embedded video calling.** Interviews use auto-generated Google Meet/Zoom links (see [`docs/ARCHITECTURE.md`](ARCHITECTURE.md)); the interview itself happens outside this application.
- **Multiple HR role types / granular permissions** (e.g. Recruiter vs. Hiring Manager). Out of scope for the current phase; see [`docs/ROADMAP.md`](ROADMAP.md).
- **Applicant-to-applicant interaction.** No social graph, no public profiles, no networking features of any kind.

## 5. Business Model

This software is **licensed/delivered once per company**, similar to traditional custom or licensed software — not hosted as a recurring SaaS subscription by the project's developer. A company acquiring this software receives a deployable system that they run on their own infrastructure (a VPS, on-premise server, or other environment of their choosing). See [`docs/ARCHITECTURE.md`](ARCHITECTURE.md) for the dual deployment strategy (Railway for the current development phase, Docker Compose for portable production delivery).

The product is built generically first — there is no specific launch client at the time of this document. It is intended to be offered to whichever company needs it once development reaches a deliverable state. See [`docs/ROADMAP.md`](ROADMAP.md) for phase status.

## 6. Success Criteria (Qualitative, for v1)

A first deployable version is successful if an HR Admin at a real company could, without developer assistance:
- Post a job opening and have it appear publicly within the system.
- Receive applications with CVs reliably (no lost files, no format confusion for applicants).
- Move applicants through Pending → Shortlisted/Rejected with the applicant notified automatically at each step.
- Schedule an interview and have the applicant receive a working meeting link without manual coordination.
- Exchange messages with a specific applicant about their specific application.
- See, at a glance, how many applicants they've received per posting and where their pipeline currently stands.

## 7. Non-Goals

This document deliberately avoids quantified business metrics (revenue targets, number of companies to onboard, pricing) because, as of this writing, there is no specific client and no fixed go-to-market timeline. Those decisions belong to the project owner outside the scope of this technical PRD, and should be added here once decided rather than assumed.
