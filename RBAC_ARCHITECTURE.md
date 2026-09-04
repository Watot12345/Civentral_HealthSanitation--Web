ARCHITECTURE PLAN v2 — Civentral RBAC: Dept-Scoped Permission System
(revised: explicit matrix fields, capability vs scope separation, admin
gates as hard exclusions)

CORE PRINCIPLE:
Capability (what action) and department scope (whose data) are separate
checks. Admin bypasses scope entirely, unchanged. Non-admin gets same
shared functions, auto-filtered.

1. PERMISSION SLUGS (PermissionService.php) — two kinds, don't merge
   CAPABILITY slugs (existing, keep as-is):
     patients.view, patients.create, permits.approve, etc.
   SCOPE slugs (new, department gate):
     dashboard.health_center / dashboard.sanitation / dashboard.immunization
     dashboard.wastewater / dashboard.surveillance
     analytics.{same 5 depts}
     reports.{same 5 depts}
     compliance.admin_only  (no dept variant, ever)

2. DEPARTMENT MATRIX — explicit fields per role, no "inherit from director"
   shortcut. Each Position gets full row:

   { position, department_scope, dashboard_slug, analytics_slug,
     reports_slug, compliance_allowed, modules[] }

   System Administrator → dept: null(all), compliance: true, modules: all
   Health Center Director → dept: health_center, compliance: false,
     modules: [patients, consultations, triage, prescriptions]
   Doctor/Nurse/Dentist/Lab Tech (staff) → dept: health_center,
     compliance: false, modules: [subset of above, no admin widgets]
   Sanitation Director → dept: sanitation, compliance: true(sanitation-only),
     modules: [permits, inspections]
   Inspector/Permit Clerk/Cashier (staff) → dept: sanitation,
     compliance: false, modules: [subset, no compliance]
   Wastewater Officer → dept: wastewater, compliance: false,
     modules: [wastewater]
   Immunization Coordinator/Midwife → dept: immunization, compliance: false,
     modules: [immunization, nutrition]
   Nutritionist → dept: immunization, compliance: false, modules: [nutrition]
   Surveillance Coordinator/Officer → dept: surveillance, compliance: false,
     modules: [surveillance]

   Rule: staff rows always compliance:false, module list explicitly written
   (not inherited) — same dept as their Director, narrower modules only.

3. HELPER FUNCTION (single source of truth)
   function getUserScope($employeeId): array {
       returns [
         'department'  => null|string,   // null = admin, no filter
         'is_admin'    => bool,
         'modules'     => string[],
         'compliance'  => bool,
       ]
   }

4. QUERY-LAYER FILTER (shared functions, one-line injection)
   if (!empty($scope['department'])) {
       $query->where('department', $scope['department']);
   }
   // admin: department=null → no filter → unchanged full access

5. NAV FILTER (sidebar.php:394-471)
   - Admin-only items (compliance, settings, roles.manage, users.view if
     restricted) → check $scope['compliance'] or explicit is_admin flag ONLY.
     Never fall through to department logic. Hard exclusion, no bypass.
   - Department items → check $scope['is_admin'] || item.dept === $scope['department']
   - Module-level buttons (create/edit/delete) → still gated by CAPABILITY
     slug (patients.create etc), separate from scope check.

6. IMPLEMENTATION ORDER
   a. Add SCOPE slugs to PermissionService.php (additive, no removal)
   b. Build explicit matrix array (per position, all fields written out —
      no inheritance shortcuts)
   c. Add getUserScope() helper
   d. Wire sidebar.php: admin-only hard exclusion first, then dept check
   e. Inject one-line filter into shared query functions
   f. Test admin (unchanged, full access confirmed)
   g. Test one director role (dept-locked, compliance per matrix)
   h. Test one staff role (dept-locked, narrower modules, compliance=false)

CONSTRAINT:
   - Admin code path untouched, zero risk
   - Do not fork/duplicate functions per department
   - Do not touch Role.php primaryRoleNames() order/ids
   - Compliance/settings/roles.manage = admin-only, always, no dept fallback
   - Matrix rows explicit per position, no "same as director" shorthand
     in code — write full row even if repetitive, avoids ambiguity bugs