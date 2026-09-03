# Civentral — Remaining Features Implementation Plan

Scope: 9 QA gaps → mapped to 10 build phases. Stack: PHP MVC, Supabase/PostgreSQL, Docker, GitHub Actions, Gemini API.

---

## Phase 1 — Database Encryption
**Goal:** protect data at rest + in transit.
- Enable PostgreSQL `pgcrypto` ext for column-level encryption (sensitive fields: patient records, national IDs, contact info).
- Force SSL on Supabase connection string (`sslmode=require`).
- Encrypt secrets/API keys via `.env` + Docker secrets, not hardcoded.
- Hash passwords: `password_hash()` (bcrypt/argon2), never plaintext.
**Deliverable:** `db_encryption_report.md` listing encrypted columns + method.

## Phase 2 — Backup Procedures
**Goal:** scheduled, verifiable backups.
- Supabase automated daily backups (or `pg_dump` cron via GitHub Actions).
- Store backups off-platform (encrypted, e.g. S3-compatible bucket or Hostinger storage).
- Retention policy: daily x7, weekly x4, monthly x3.
**Deliverable:** `backup_policy.md` + working backup script.

## Phase 3 — Restore Procedure
**Goal:** tested recovery path, not just backup existence.
- Write `restore.sh` — pulls latest backup, restores to staging DB.
- Document RTO/RPO targets.
- Run 1 full dry-run restore, log result.
**Deliverable:** `restore_procedure.md` + dry-run log as proof.

## Phase 4 — Offline Synchronizing
**Goal:** handle intermittent connectivity (health workers in field).
- Local queue (IndexedDB or localStorage) for pending writes.
- Sync worker: push queued reqs on reconnect, conflict resolution (last-write-wins or timestamp merge).
- UI indicator: online/offline/syncing state.
**Deliverable:** `offline_sync_module` + conflict-handling doc.

## Phase 5 — Source Code Security
**Goal:** harden codebase against common vulns.
- Audit for SQLi (parameterized queries only), XSS (output escaping), CSRF (token per form).
- `.env` never committed — confirm `.gitignore`.
- Dependency audit (`composer audit` / `npm audit`).
- Enforce RBAC (`hasPermission()`) on every controller action.
**Deliverable:** `source_code_audit_report.md`.

## Phase 6 — Security Scan Report
**Goal:** formal vuln scan for defense panel.
- Run OWASP ZAP or similar against staging.
- Static analysis (PHPStan / Psalm for PHP layer).
- Log findings + remediation status (fixed/accepted risk).
**Deliverable:** `security_scan_report.pdf` (exportable, panel-ready).

## Phase 7 — AI Security Report
**Goal:** document Gemini API integration risk surface.
- API key handling (server-side only, never client-exposed).
- Input sanitization before sending to Gemini (prevent prompt injection from user data).
- Rate limiting on AI endpoints.
- Data sent to Gemini — confirm no raw PII leaves system unmasked.
**Deliverable:** `ai_security_report.md`.

## Phase 8 — Personal Data Privacy (RA 10173 / Data Privacy Act)
**Goal:** compliance mapping for LGU health data.
- Data classification: sensitive personal info (health records) vs regular.
- Access logs — who viewed/edited patient data, timestamped.
- Data minimization check — only collect what's needed per module.
- Anonymization for AI analytics dataset (aggregate, not per-citizen).
**Deliverable:** `data_privacy_compliance.md`.

## Phase 9 — Consent Management
**Goal:** citizen consent capture + audit trail.
- Consent checkbox at booking/registration — versioned consent text.
- Store: user_id, consent_version, timestamp, IP (optional).
- Withdraw-consent flow (soft-delete / anonymize on request).
**Deliverable:** `consent_management` module + DB table `consent_records`.

## Phase 10 — Keyboard Accessibility
**Goal:** WCAG 2.1 AA baseline, keyboard-only nav.
- Tab order audit across all modules (Health Center, Sanitation, Immunization, Wastewater, Surveillance).
- Focus states visible (`:focus-visible` styling via Tailwind).
- Skip-to-content link, ARIA labels on icon-only buttons.
- Modal/dialog trap focus + Esc to close.
**Deliverable:** `accessibility_audit.md` + fixed components list.

---

## Cross-phase deliverables (for defense panel)
- All reports → consolidate into `docs/qa_compliance/` folder.
- Each phase closes only when report exists + verified by test/dry-run, not just "done" claim.