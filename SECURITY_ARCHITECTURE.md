# 🛡️ CIVENTRAL: Complete System Security Architecture & Checklist

> **Document Classification:** Official Technical Documentation & Capstone Panel Defense Reference  
> **System Name:** Civentral Public Health & Municipal Sanitation ERP Platform  
> **Last Updated:** August 28, 2026  
> **Standards Reference:** OWASP Top 10, NIST SP 800-63B, ISO/IEC 27001  

---

## 📋 Simple Master List of ALL Security Features in Civentral

Here is the complete, straightforward list of every security mechanism built into the project:

```
┌────────────────────────────────────────────────────────────────────────┐
│                   ALL INCLUDED SECURITIES AT A GLANCE                  │
├────────────────────────────────────────────────────────────────────────┤
│ 1. Login & Brute-Force Rate Limiting (5 Attempts -> 15-Min Lockout)    │
│ 2. Two-Factor Authentication (2FA Email OTP with 3-Min Expiration)     │
│ 3. OTP Attempt Limiter (Max 5 Incorrect Tries -> Code Invalidation)    │
│ 4. BCRYPT Password Hashing (Cost Factor 12 + 128-bit Salt)             │
│ 5. Cross-Origin Resource Sharing (CORS) & Preflight Security Headers   │
│ 6. Inbound IP Rate Limiting (30 Requests / Minute on APIs)             │
│ 7. HMAC-SHA256 Cryptographic Trusted Device Tokens                     │
│ 8. Automatic Device Token Revocation upon Password Change              │
│ 9. HttpOnly & SameSite Cookie Security (XSS & CSRF Mitigation)         │
│ 10. Granular Role-Based Access Control (RBAC with 19 Roles)            │
│ 11. Multi-Department Data Isolation (Zero Cross-Department Leaks)      │
│ 12. Server-Side Identity Resolution (Zero Trust in Client Parameters)  │
│ 13. SQL Injection Immunity (100% Parameterized PDO & Supabase APIs)    │
│ 14. Cross-Site Scripting (XSS) Sanitization (htmlspecialchars + DOM)   │
│ 15. AJAX & CSRF Request Validation (X-Requested-With + SameSite)       │
│ 16. Noise-Filtered Operational Activity Logging (Clinical & Permits)  │
│ 17. Report Generation & Export Audit Trail (Tracks User, Format, Date) │
│ 18. Outbound AI Rate Governor (60 Calls / 30 Min Sliding Window)       │
│ 19. 4-Tier Autonomous AI Model Fallback Queue                          │
│ 20. Deterministic Mathematical Fail-Safe (Zero-Downtime Fallback)      │
│ 21. TLS 1.3 / HTTPS in Transit & Supabase AES-256 Encryption at Rest   │
│ 22. Zero-Exposure Environment Configuration (.env with 0600 Perms)     │
└────────────────────────────────────────────────────────────────────────┘
```

---

## 🔍 Detailed Breakdown by Category

### 1. 🔐 Login & Authentication Security
1. **Login Brute-Force Rate Limiting:**
   * Tracks failed login attempts per IP and Employee ID in `login.php`.
   * **Lockout Policy:** 5 consecutive failed login attempts activate a **15-minute security lockout**.
2. **Two-Factor Authentication (2FA / OTP):**
   * Requires a dynamic 6-digit cryptographic code sent via TLS SMTP email.
3. **3-Minute OTP Expiration Window:**
   * Verification codes expire strictly after **180 seconds (3 minutes)**.
4. **OTP Attempt Rate Limiter (Brute-Force Shield):**
   * Limits incorrect OTP submissions to a maximum of **5 attempts**.
   * On the 5th failed try, the code is immediately invalidated (`otp_code = NULL`) and locked.
5. **BCRYPT Password Hashing:**
   * Utilizes `PASSWORD_BCRYPT` with cost factor **12** (4,096 hashing rounds) and unique cryptographic salts.
6. **PII Shoulder-Surfing Masking:**
   * User emails are masked on-screen (e.g. `jo***@health.gov.ph`) during authentication.
7. **3-Step Tokenized Password Reset:**
   * Requires Employee ID verification $\rightarrow$ 3-minute OTP verification $\rightarrow$ temporary single-use reset token.

---

### 2. 🌐 CORS & HTTP Header Security
1. **CORS Policy Enforcement:**
   * Sets strict `Access-Control-Allow-Origin`, `Access-Control-Allow-Methods`, and `Access-Control-Allow-Headers` across API endpoints.
2. **HTTP OPTIONS Preflight Handling:**
   * Automatically handles preflight CORS handshakes and terminates unauthorized cross-origin requests.
3. **MIME-Type & Content-Type Enforcement:**
   * Enforces `Content-Type: application/json; charset=utf-8` on all JSON API responses to prevent MIME-sniffing attacks.
4. **AJAX Request Verification:**
   * Validates `X-Requested-With: XMLHttpRequest` on sensitive POST handlers.

---

### 3. 🍪 Session & Trusted Device Security
1. **HMAC-SHA256 Device Trust Tokenization:**
   * When *"Remember this device"* is checked, generates a tamper-proof digital signature binding User ID + Employee ID + Server Secret.
2. **Automatic Invalidation on Password Reset:**
   * The device token embeds a cryptographic slice of the user's active password hash. Changing or resetting a password **instantly revokes all device cookies** across all hardware.
3. **HttpOnly Cookie Protection:**
   * `HttpOnly = true` prevents JavaScript (`document.cookie`) from accessing session tokens, defeating XSS cookie theft.
4. **SameSite Cookie Attribute:**
   * `SameSite = Lax` protects cookies from being sent in malicious third-party cross-site requests (CSRF).
5. **Dual Shift Lifecycles:**
   * Standard sessions automatically expire after **12 hours** (1 shift), while trusted devices last **7 days**.

---

### 4. 🚦 Inbound & Outbound Rate Limiting
1. **Inbound IP Rate Limiting (`RateLimiterService.php`):**
   * Restricts API calls to **30 requests per minute** per IP address.
   * Returns `HTTP 429 Too Many Requests` with `X-RateLimit-Limit`, `X-RateLimit-Remaining`, and `X-RateLimit-Reset` headers.
2. **Outbound AI Rate Governor (`GeminiAiService.php`):**
   * Restricts external Google Gemini AI API calls to **60 requests per 30-minute sliding window** to prevent API exhaustion.
3. **30-Minute Multi-Tier Response Caching:**
   * Caches heavy analytical queries and AI forecasts in `storage/cache/` to minimize external requests and deliver sub-50ms speeds.

---

### 5. 👥 Role-Based Access Control (RBAC) & Authorization
1. **19 System Roles Permission Matrix (`PermissionService.php`):**
   * Granular permission slugs (`patients.view`, `permits.approve`, `surveillance.manage`, `users.create`, etc.).
2. **Multi-Department Data Isolation:**
   * Strictly isolates data between the 5 municipal health directorates:
     1. Health Center Services
     2. Sanitation & Food Safety
     3. Disease Surveillance & Epidemiology
     4. Immunization & Nutrition
     5. Wastewater & Septage Management
3. **Server-Side Identity Resolution:**
   * All queries resolve permissions directly from `$_SESSION` on the server—never trusting client-supplied role parameters.
4. **Hierarchical Feed Filtering:**
   * Admins see city-wide data; Department Heads see only their staff's operational actions; regular staff see only their own tasks.

---

### 6. 🛡️ Data Sanitization & Injection Defense
1. **SQL Injection (SQLi) Immunity:**
   * 100% of queries use **Supabase RESTful APIs** or **PDO Prepared Statements** with strictly bound parameters (zero string concatenation).
2. **Cross-Site Scripting (XSS) Sanitization:**
   * Dynamic variables rendered to HTML pass through `htmlspecialchars($var, ENT_QUOTES, 'UTF-8')`.
   * Frontend JavaScript uses `escapeHtml()` and safe DOM property bindings (`textContent`).
3. **Input Trimming & Normalization:**
   * All user inputs are sanitized with `trim()` and type validation before database persistence.

---

### 7. 📜 Comprehensive Audit Trails & Compliance
1. **Operational Activity Logging (`ActivityLog.php`):**
   * Records operational events (Patient Intake, Consultation Recorded, Sanitary Permit Approved, Vaccine Dose Administered) with User ID, Role, IP Address, Device, and Timestamp.
2. **Authentication Noise Filter:**
   * Excludes routine login/logout spam from operational feeds so department heads monitor real clinical/sanitary operations.
3. **Report Generation & Export Audit Trail (`api/reports/log_export.php`):**
   * Specifically logs **who generated/exported reports**, format used (`PDF`, `Excel`, `Word`, `CSV`), department, and timestamp for COA/DOH regulatory audits.

---

### 8. 🤖 AI Resilience & Cryptography
1. **4-Tier Autonomous AI Model Fallback Queue:**
   * Auto-rotates models on rate limits or errors:
     $$\text{gemini-3.6-flash} \quad \xrightarrow{429/503} \quad \text{gemini-3.5-flash-lite} \quad \xrightarrow{429/503} \quad \text{gemini-3.1-flash-lite} \quad \xrightarrow{429/503} \quad \text{gemini-2.0-flash-lite}$$
2. **Deterministic Mathematical Fail-Safe:**
   * If AI service is unreachable, transparently falls back to **PHP Linear Regression ($y = mx + b$) & $R^2$ Variance** so dashboards never crash.
3. **Encryption in Transit (TLS 1.3 / HTTPS):**
   * Encrypts all data between browser, Apache server, and Supabase cloud.
4. **Encryption at Rest (AES-256):**
   * Supabase PostgreSQL database volumes are protected by AES-256 block-level encryption.
5. **Zero Hardcoded Secrets Architecture:**
   * Secrets, DB keys, and SMTP credentials live strictly in `.env` protected by `0600` file permissions.

---

## 🎯 Panel Defense Quick Answers

* **"What security prevents hackers from guessing passwords or OTPs?"**
  * *We have dual rate limiting: 5 failed logins triggers a 15-minute account lockout, and 5 wrong OTP entries permanently invalidates the 3-minute code.*
* **"How do you handle CORS and unauthorized API requests?"**
  * *All APIs enforce CORS headers, handle preflight OPTIONS requests, verify `X-Requested-With` AJAX headers, and validate the active session server-side.*
* **"How is user data protected across departments?"**
  * *RBAC enforced by `PermissionService` scopes all database queries using server-side session variables, preventing users from crossing department boundaries.*
* **"What protects against SQL Injection and XSS?"**
  * *100% of queries use PDO Prepared Statements and Supabase REST APIs with parameterized values. All UI output passes through `htmlspecialchars()` and UTF-8 encoding.*
