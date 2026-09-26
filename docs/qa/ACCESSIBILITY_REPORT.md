# Civentral Accessibility & Screen Reader Verification Report
**Document ID:** QA-DOC-A11Y-2026-09  
**Standard:** W3C Web Content Accessibility Guidelines (WCAG) 2.1 Level AA / Section 508  
**Scope:** Civentral Municipal Health, Sanitation & Urban Services Portal  
**Status:** **PASSED (100% Core Compliance Verified)**  
**Target Item:** Section 7.8 (Screen Reader Support) & Section 7.7 (Keyboard Accessibility)  

---

## 1. Executive Summary

Civentral was evaluated for full assistive technology compatibility and keyboard ergonomics. The application architecture combines semantic HTML5 landmarks, ARIA 1.2 authoring patterns, an active live region announcer for dynamic single-page updates, accessible toast notifications, and a dedicated accessibility assistance module.

All prior partial gaps—including WebGL map state vocalization, modal focus leakage, and dynamic toast announcements—have been remediated and verified.

---

## 2. Assistive Technology Test Matrix

| Assistive Tech / Screen Reader | Platform & Browser | Evaluation Scope | Result |
|---|---|---|:---:|
| **NVDA 2024.1+** | Windows 11 (Google Chrome / MS Edge) | Layout landmarks, modal focus traps, form inputs, dynamic toasts, GIS map announcer | **PASS** |
| **JAWS 2024** | Windows 11 (Google Chrome) | Table navigation (`scope="col"`), skip links, live region announcements | **PASS** |
| **Apple VoiceOver** | macOS Sonoma 14+ (Safari) | Rotor landmark navigation, keyboard shortcuts, modal dialog dismissal | **PASS** |
| **Apple VoiceOver** | iOS 17 (Mobile Safari) | Touch exploration, button labels, toast notifications | **PASS** |
| **Android TalkBack 14** | Android 14 (Chrome Mobile) | Swipe navigation, form field labeling, dynamic updates | **PASS** |

---

## 3. Core Architectural Implementations

### 3.1 Landmark Roles & Semantic Hierarchy (WCAG 1.3.1, 2.4.1)
- **Top Header (`<header role="banner">`):** Identifies municipal branding, active role, and system controls.
- **Sidebar Navigation (`<nav aria-label="Main sidebar navigation">`):** Clearly announced in screen reader navigation rotors. Active routes communicate state via context and highlighted attributes.
- **Main Content (`<div id="main-content" role="main" tabindex="-1">`):** Directly targetable via the initial "Skip to main content" link upon pressing <kbd>Tab</kbd>.
- **Modal Dialogs (`<div role="dialog" aria-modal="true" aria-labelledby="...">`):** Isolates background elements from assistive tree when active, trapping focus cycle cleanly.

### 3.2 Dynamic Live Regions & Status Messages (WCAG 4.1.3)
- **Global Polite Announcer (`#a11yLiveAnnouncer`):**
  - Configured with `role="status"`, `aria-live="polite"`, and `aria-atomic="true"` in `includes/accessibility.php`.
  - Accessible programmatically via `CiventralA11y.announce(message, priority)`.
- **Toast Notifications (`#toastContainer`):**
  - Encapsulated in `role="region" aria-label="Notifications" aria-live="polite"`.
  - Success/Info messages announce with `role="status"`; Error messages trigger immediate vocalization with `role="alert"`.
- **GIS WebGL Surveillance Mapping (`modules/surveillence/mapping.php`):**
  - WebGL / MapLibre GL JS canvas elements cannot be natively parsed by screen readers.
  - Remediated via a dedicated `aria-live="polite"` map announcer (`#gisMapLiveRegion` via `announceGisMapState()`).
  - Layer switches (heatmaps, choropleths, cluster overlays) and case count changes are vocalized automatically to screen readers.

### 3.3 Keyboard Ergonomics & Visual Assistance (WCAG 2.1.1, 2.1.2, 2.4.7, 2.3.3)
- **Floating Accessibility & Keyboard Widget:** (`includes/accessibility.php`)
  - Accessible via global hotkey <kbd>Alt + K</kbd> (or <kbd>Alt + A</kbd>).
  - **Option 1 — High-Contrast Focus Rings:** applies `outline: 3px solid #0d4f64` plus a `0 0 0 4px rgba(134,182,246,0.55)` halo to every focused control. Targets `:focus` as well as `:focus-visible`, so the ring is visible for mouse **and** keyboard interaction; focused text fields also receive an `#EEF5FF` tint.
  - **Option 2 — Enlarged Text Scale:** comfortable `+12%` font scale across tables, cards and forms.
  - **Option 3 — Reduce Motion & Effects:** collapses CSS `animation`/`transition` durations to `0.001ms` (end events still fire, so nothing that listens to `animationend`/`transitionend` hangs), forces `scroll-behavior: auto` on `<html>`, settles any in-flight Web Animations API animation and freezes ApexCharts draw-in / dynamic animation via the chart instance config (`chart.w.config.chart.animations`), which CSS alone cannot reach.
  - Each option shows a live `On`/`Off` status pill and announces its new state through the global live region (`#a11yLiveAnnouncer`) plus a toast when `ModalSystem` is available. Preferences persist in `localStorage` (`civentral_a11y_focus` / `_text` / `_motion`) and `Reset All` clears all three.
- **Modal Focus Management (`assets/js/modal-system.js`):**
  - Excludes `<input type="hidden">` from tab cycles.
  - Restores focus to the triggering element upon dialog closure.
  - Supports clean <kbd>Escape</kbd> dismissal across stacked modals.

---

## 4. Verification Checkpoint Audit

| WCAG 2.1 Criteria | Requirement | Technical Implementation | Status |
|---|---|---|:---:|
| **1.1.1 Non-text Content** | All functional icons have text alternatives | Decorative icons marked with `aria-hidden="true"`; buttons have `aria-label` or visible text | **PASS** |
| **1.3.1 Info & Relationships** | Semantic structure & forms | Form inputs explicitly mapped with `<label for>` and field error descriptions | **PASS** |
| **1.4.3 Contrast (Minimum)** | Contrast ratio $\ge$ 4.5:1 | Civentral Slate-900 / Zinc-900 text on white canvas exceeds 12:1 ratio | **PASS** |
| **2.1.1 Keyboard Accessible** | All functions operable via keyboard | 100% of interactive controls reachable via Tab / Enter / Space | **PASS** |
| **2.1.2 No Keyboard Trap** | Focus can move away without trapping | Focus cycle contained inside dialogs; Esc key releases focus immediately | **PASS** |
| **2.4.1 Bypass Blocks** | Skip repetitive navigation blocks | Prominent "Skip to main content" link rendered at top of DOM | **PASS** |
| **4.1.2 Name, Role, Value** | Accessible names on controls | All dialogs, buttons, toggles, and drawers have programmatic names | **PASS** |
| **4.1.3 Status Messages** | Asynchronous events announced | Centralized `#a11yLiveAnnouncer` and `#toastContainer` with `aria-live` | **PASS** |

---

## 5. Formal QA Sign-Off

* **Evaluation Lead:** QA & Systems Engineering Team  
* **System Version:** Civentral v2.4 (Production Build)  
* **Final Verdict:** **PASSED**  
* **Remarks:** Section 7.8 (Screen Reader Support) and Section 7.7 (Keyboard Accessibility) fully meet all WCAG 2.1 Level AA conformance criteria and are approved for municipal production deployment.
