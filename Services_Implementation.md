# Implementation Plan: Wastewater Management Module Security, Backend AJAX Integration & Linear Workflow

## Overview
Comprehensive security hardening, XSS prevention, CSRF protection, input validation, DOM selection fixes, Leaflet map integration, CSV export functionality, backend AJAX fetch integration, and linear status workflow enforcement across all 5 Wastewater Service modules.

## Completed Changes

### Shared Infrastructure
- **[common.js](file:///d:/xampp/htdocs/Civentral_HealthSanitation--Web/assets/js/common.js)**
  - Centralized `sanitizeHTML()`, `sanitizeAttr()`, `getCsrfToken()`, `isValidEmail()`, `openModal()`, `closeModal()`, backdrop click handlers, CSV export helpers, and `sendAjaxRequest()` async fetch helper.

### Module Hardening & Backend Integration
- **[maintenance.php](file:///d:/xampp/htdocs/Civentral_HealthSanitation--Web/modules/services/maintenance.php)**
  - Backend POST AJAX endpoint handler added at top of PHP file.
  - Enforced strict linear status workflow: Scheduled (`scheduled`) -> In Progress (`in_progress`) -> Completed (`completed`).
  - Scheduled items display a "Start Service" button; completion modal is restricted to items in progress.
  - Asynchronous `fetch()` calls added to `saveCompletionReport`, `saveServiceEdit`, `saveScheduleService`, `startService`, and `rateService`.
- **[providers.php](file:///d:/xampp/htdocs/Civentral_HealthSanitation--Web/modules/services/providers.php)**
  - Backend POST AJAX endpoint handler added at top of PHP file.
  - Verified registration form closure, buttons, and `saveProviderRegistration` handler.
  - Asynchronous `fetch()` calls added to `saveProviderRegistration`, `saveProviderEdit`, `saveProviderAssignment`, and `saveEquipment`.
- **[septic_tanks.php](file:///d:/xampp/htdocs/Civentral_HealthSanitation--Web/modules/services/septic_tanks.php)**
  - Backend POST AJAX endpoint handler added at top of PHP file.
  - Leaflet interactive map integration in location modal.
  - Asynchronous `fetch()` calls added to `saveTankRegistration` and `saveTankEdit`.
- **[service_requests.php](file:///d:/xampp/htdocs/Civentral_HealthSanitation--Web/modules/services/service_requests.php)**
  - Backend POST AJAX endpoint handler added at top of PHP file.
  - Priority filter bug resolved (`data-priority` added to TRs).
  - Asynchronous `fetch()` calls added to `saveNewRequest`, `saveStatusUpdate`, `saveFeedback`, and `saveRequestEdit`.
- **[wastewater_billing.php](file:///d:/xampp/htdocs/Civentral_HealthSanitation--Web/modules/services/wastewater_billing.php)**
  - Backend POST AJAX endpoint handler added at top of PHP file.
  - Sanitized printable invoice contents in `downloadInvoice()`.
  - Asynchronous `fetch()` calls added to `savePayment`, `saveQuotation`, and `saveInvoiceEdit`.
