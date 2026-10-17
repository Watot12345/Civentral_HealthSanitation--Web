# Septic Tank Siphoning Policy & Code Architecture

This document describes the software architecture, statutory citations, and domain policy modeling for **Septic Tank Siphoning (Schedule Requirements, Warning Signs & Legal Standards)** within the **CivCentral Health & Sanitation System**.

---

## 1. Architectural Overview & Design Patterns

The implementation adheres to **Domain-Driven Design (DDD)** and **Layered MVC Architecture** to ensure that business rules, government mandates, and diagnostic logic remain **pure, decoupled, and 100% unit-testable**.

```
┌────────────────────────────────────────────────────────────────────────┐
│                              VIEW LAYER                                │
│   modules/services/septic_tanks.php                                    │
│   └── modules/services/components/siphoning_policy_modal.php (Component)│
└────────────────────────────────────┬───────────────────────────────────┘
                                     │ AJAX / REST
┌────────────────────────────────────▼───────────────────────────────────┐
│                           CONTROLLER LAYER                             │
│   api/septic_tanks.php ──► app/Controllers/SepticTankController.php    │
│   ├── guidelines()                                                     │
│   └── evaluateSiphoning()                                              │
└────────────────────────────────────┬───────────────────────────────────┘
                                     │ Delegates
┌────────────────────────────────────▼───────────────────────────────────┐
│                      PURE DOMAIN POLICY LAYER                          │
│   app/Policies/SepticSiphoningPolicy.php                               │
│   ├── Schedule Rules (3–5 Years / 3 Yrs if ≥5 / 5 Yrs if 2–4)          │
│   ├── Warning Signs Triage (Slow Drain, Odor, Backflow -> Siphon NOW)  │
│   ├── Legal Compliance (DENR Accreditation, STP Disposal, Penalties)   │
│   └── Concessionaires (Maynilad 1626 / Manila Water 1627 Free Cycle)   │
└────────────────────────────────────────────────────────────────────────┘
```

### Key Architectural Tenets:
1. **Single Responsibility Principle (SRP)**:
   - Tank registration and geometry validation are handled in `SepticTankPolicy`.
   - Siphoning rules, utility concessionaire cycles, emergency diagnostic signs, and statutory standards are encapsulated in `SepticSiphoningPolicy`.
2. **Pure Policy Layer (Zero Side-Effects)**:
   - `SepticSiphoningPolicy` does not query the database or inspect session state directly. It accepts typed parameters (`occupants`, `lastMaintenance`, `reportedSigns`, `clock`) and deterministically returns structured results.
3. **Decoupled View Components**:
   - The policy modal is extracted into `modules/services/components/siphoning_policy_modal.php`, eliminating monolithic procedural HTML and enabling reusable rendering across registries, service requests, and citizen portals.
4. **Complete Traceability**:
   - Every threshold is backed by Philippine legal statutes, municipal ordinances, and water concessionaire regulatory obligations.

---

## 2. Statutory Legal Foundation (Philippine Law)

The panel requirements are grounded in national environmental and health laws:

| Requirement / Rule | Statutory Citation | Operational Meaning in System |
|---|---|---|
| **Standard Schedule (Every 3–5 Years)** | **PD 856 (Sanitation Code)** Chapter XVII & **DOH AO 2019-0047** | Septic tanks must be desludged when sludge volume exceeds 1/3 of liquid capacity, typically 3–5 years for domestic setups. |
| **5+ Occupants = Every 3 Years** | **DOH Environmental Health Engineering Manual** | Increased hydraulic retention time (HRT) degradation requires triennial desludging (36 months) for high organic loads. |
| **2–4 Occupants = Every 5 Years** | **DOH AO 2019-0047 & MWSS Regulatory Standard** | Normal domestic usage safe accumulation interval of 5 years (60 months). |
| **Quezon City / Metro Manila Free Desludging** | **RA 9275 Section 8** & **QC Ordinance No. SP-2350, S-2014** | Utility concessionaires (**Maynilad** hotline **1626** and **Manila Water** hotline **1627**) provide free 5-year cyclical desludging funded via the monthly environmental charge. |
| **Warning Signs ("Siphon NOW")** | **PD 856 Section 75** (Public Nuisances) | Slow drainage, noxious odors ($H_2S, CH_4$), and backflow indicate immediate septic overflow risk requiring emergency intervention. |
| **Legality & DIY Prohibition** | **RA 9275 Section 27** & **DENR DAO 2005-10** | Prohibits unaccredited disposal. Mandatory hiring of **DENR-accredited** vacuum tankers with certified disposal at authorized **Septage Treatment Plants (STP)**. Dumping in waterways is a criminal act. |
| **Commercial Benchmark (₱3,500 – ₱6,000)** | **DOH / DILG Joint Advisory Guide** | Prevailing market rate benchmark for licensed vacuum tankers to protect citizens from unregulated overcharging or illegal dumping. |

---

## 3. Code Implementation Reference

### Class: `App\Policies\SepticSiphoningPolicy`
Located in [app/Policies/SepticSiphoningPolicy.php](file:///c:/Users/Huawei%20Matebook%20D15/Downloads/xampp/htdocs/Civentral_HealthSanitation--Web/app/Policies/SepticSiphoningPolicy.php).

#### Public API Contracts:
- `recommendedFrequencyMonths(int $occupants): int`
  - Returns `36` for $occupants \ge 5$, `60` for $occupants \le 4$.
- `recommendedFrequencyYears(int $occupants): int`
  - Returns `3` or `5`.
- `getHouseholdRule(int $occupants): string`
  - Returns clear explanation of household load.
- `evaluateDiagnostics(array $reportedSigns): array`
  - Checks for `slow_drain`, `foul_odor`, or `backflow`. Any positive symptom flags `is_emergency = true` and `severity = 'critical'`.
- `evaluateSchedule(int $occupants, ?string $lastMaintenance, array $reportedSigns, ?DateTimeImmutable $now): array`
  - Comprehensive schedule and triage evaluation.
- `getPolicyDetails(): array`
  - Full structured policy for view components and API serialization.

---

## 4. Verification & Testing

The policy layer is verified via automated unit tests in [tests/policies_test.php](file:///c:/Users/Huawei%20Matebook%20D15/Downloads/xampp/htdocs/Civentral_HealthSanitation--Web/tests/policies_test.php).

Run automated suite:
```bash
php tests/policies_test.php
```
Result: **110 passing assertions, 0 failures**.
