remove delete patients
remove delete child records
cancel prescription change to cancecl icon not  trash
succecsfull referal if sucesfuly it should auto reload or much possible realtime apply after it says succes referal




referrals.php:2016 Uncaught ReferenceError: enrichLocally is not defined
    at referrals.php:2016:47
    at Array.map (<anonymous>)
    at HTMLDocument.<anonymous> (referrals.php:2016:38)

---

### Security Feature Update (Capstone Presentation Note):
- **Removed Static IP Whitelisting (`security.allowed_ips`)**:
  - **Reason**: Static IP/subnet restrictions cause accidental administrative lockouts during live presentations, mobile hotspot use, multi-clinic DHCP environments, and cloud deployments (e.g. `10.0.1.x` vs `10.0.0.x`).
  - **Production Security Retained**: The system relies on superior, dynamic security layers:
    1. **Brute Force Rate-Limiting & Auto-Lockout** (`security.max_login_attempts`)
    2. **Two-Factor Authentication / OTP Verification** (`security.two_factor_auth`)
    3. **Password Expiration & Session Idle Timeout** (`security.session_timeout`)
    4. **Audit Logging & Activity Tracking** (`activity_logs` records every sign-in attempt, user ID, role, and client IP)
    5. **Granular RBAC Authorization** (Module and path level permission gating)




