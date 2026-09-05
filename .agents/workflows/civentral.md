---
description: 
---

# Civentral Precision Task Workflow

A token-efficient, zero-bloat, evidence-based execution workflow for Civentral.

**Objective**: Deliver minimal, correct, production-grade solutions by inspecting only what is necessary, asking clarifying questions when ambiguous ("grill first"), adhering strictly to Civentral stack rules, and verifying with executable commands.

---

## 1. Trigger & Philosophy

- **Trigger Command**: `/civentral`
- **Core Philosophy**:
  1. **Token Efficiency**: Inspect targeted snippets; never read full files unnecessarily; batch lookups.
  2. **Zero Hallucination**: Every table, column, class, permission, or route referenced must exist in code or migrations.
  3. **Zero Bloat**: Make the smallest focused change that solves the problem. No unsolicited refactors.
  4. **Anti-Assumption ("Grill First")**: If the prompt is ambiguous, underspecified, or contradicts repository evidence, STOP and question the user. Do not guess. Do not be biased toward agreeing with flawed premises.
  5. **Verification by Execution**: A change is only complete when verified by command execution, never by "should work" reasoning.

---

## 2. Step-by-Step Execution Phases

```
┌─────────────────────────────────────────────────────────────┐
│ 1. UNDERSTAND & INTERVIEW ("Grill First, Never Assume")     │
└──────────────────────────────┬──────────────────────────────┘
                               │
                               ▼
┌─────────────────────────────────────────────────────────────┐
│ 2. TARGETED DISCOVERY (Token-Efficient, No Bloat)           │
└──────────────────────────────┬──────────────────────────────┘
                               │
                               ▼
┌─────────────────────────────────────────────────────────────┐
│ 3. MINIMAL FOCUSED IMPLEMENTATION (Civentral Architecture)  │
└──────────────────────────────┬──────────────────────────────┘
                               │
                               ▼
┌─────────────────────────────────────────────────────────────┐
│ 4. STRICT VERIFICATION LOOP (Execution Over Assumption)     │
└──────────────────────────────┬──────────────────────────────┘
                               │
                               ▼
┌─────────────────────────────────────────────────────────────┐
│ 5. EVIDENCE-BASED SUMMARY & CITATION                        │
└─────────────────────────────────────────────────────────────┘
```

---

### Phase 1: Understand & Interview ("Grill First, Never Assume")

1. **Parse Intent vs Reality**:
   - Read the user's objective and cross-check against actual system behavior.
   - Do not assume requirements that were not stated.
2. **Ambiguity Gate**:
   - If a request has two plausible technical paths, STOP and ask before building.
   - If any requirement, parameter, role rule, or edge case is missing or ambiguous, use `ask_question` to grill the user with specific, structured options.
3. **Push Back on Flawed Assumptions**:
   - If the user's prompt contains an assumption that conflicts with codebase reality (e.g. wrong table name, assuming an autoloader exists, assuming a permission that doesn't exist), provide the file + line evidence and push back immediately.

---

### Phase 2: Targeted Discovery (Token-Efficient)

1. **Precision Searching**:
   - Never load large files blindly.
   - Use `grep_search` with specific directory filters (`Includes: ["*.php"]` or `SearchPath: ".../api"`).
   - Find exact functions, route definitions, or class symbols before viewing code.
2. **Surgical Inspection**:
   - Use `view_file` with narrow `StartLine` and `EndLine` ranges (e.g., 20–50 lines around the target).
   - Do NOT re-read files already present in context.
3. **Verify Existing Contracts**:
   - Check permissions in [app/Constants/Permissions.php](file:///opt/lampp/htdocs/capstone/app/Constants/Permissions.php).
   - Check database schema in [database/migrations/](file:///opt/lampp/htdocs/capstone/database/migrations/).
   - Check existing endpoints in [api/](file:///opt/lampp/htdocs/capstone/api/) or controllers in [app/Controllers/](file:///opt/lampp/htdocs/capstone/app/Controllers/).

---

### Phase 3: Minimal Focused Implementation

1. **Enforce Civentral Stack Rules**:
   - **No PSR-4 Autoloader**: Always include dependencies explicitly via `require_once __DIR__ . '/...'`. Never rely on bare `use` statements.
   - **Database Client**: Use `Database::getInstance()` methods (`select`, `insert`, `update`, `delete`, `multiSelect`, `count`). Never introduce ad-hoc SQL or bypass PostgREST abstraction.
   - **RBAC Enforcement**:
     - Check 1: Capability permission from `App\Constants\Permissions`.
     - Check 2: Department scope filter for non-admin users.
   - **URL Helper**: Always use `site_url($path)` from [config/paths.php](file:///opt/lampp/htdocs/capstone/config/paths.php) for links, endpoints, and assets.
   - **Spelling Gotchas**: Note filesystem spelling `modules/surveillence/` (with an `e`).
2. **Surgical Edits**:
   - Use `replace_file_content` for single contiguous changes.
   - Use `multi_replace_file_content` for non-contiguous edits.
   - Never perform full-file rewrites when small patches suffice.
   - Do not reformat or clean up unrelated code.

---

### Phase 4: Strict Verification Loop

1. **PHP Syntax Check (Fast & Reliable)**:
   Run syntax check over all relevant PHP files:
   ```bash
   find app Core config api modules pages includes management -name "*.php" -exec php -l {} +
   ```
2. **Behavioral Testing**:
   - If an API endpoint or controller was altered, run a targeted PHP CLI test or invoke the endpoint to inspect HTTP code and JSON output.
   - If scheduler logic was modified, test via:
     ```bash
     php bin/scheduler.php --help
     ```
   - If load or performance test scripts are touched:
     ```bash
     php tests/load/run-load-test.php --help
     ```
3. **Diff Review**:
   Run `git diff` on modified files:
   - Verify no credentials, API keys, or test tokens were committed.
   - Verify no accidental edits or trailing whitespace in unrelated files.

---

### Phase 5: Evidence-Based Summary

1. **Cite Every Claim**:
   - Reference every affected file with markdown links: `[file.php](file:///path/to/file.php#L10-L30)`.
2. **State Verification Evidence**:
   - Output the exact command executed and its result (e.g., `No syntax errors detected`).
   - Never state "should work" or "is working" without executed proof.
3. **Flag Incomplete / Out-of-Scope Items**:
   - If anything could not be fully tested in the sandbox (e.g., external Supabase network calls or live webhooks), explicitly flag it as unverified.
