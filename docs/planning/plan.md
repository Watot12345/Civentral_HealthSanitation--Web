## Diagnosis: Why "Remember this device" always asks for OTP

I traced the full flow through `login.php`, `SessionAuthService.php`, `RememberMeService.php`, `config/database.php`, and the `user_sessions` migration. Here are the **most likely culprits**, ranked by probability:

---

### 🔴 1. `security.two_factor_auth` setting is likely ENABLED (Most Likely)

In `login.php` line 92:
```php
$twoFactorEnforced = class_exists('Settings') ? (bool)Settings::get('security.two_factor_auth', false) : false;
$requireOtp = $twoFactorEnforced || !$hasVerifiedDevice;
```

If `security.two_factor_auth` is set to `true` in your Settings panel, **OTP is ALWAYS required** — even on a verified device. The "Remember this device" bypass is completely overridden.

**How to check:** Go to `management/settings.php` → Security section → look for "Two-Factor Authentication" toggle. If it's ON, that's your answer.

---

### 🟠 2. RLS (Row Level Security) blocking `user_sessions` reads/writes

Your `user_sessions` migration (`2026_08_14_create_user_sessions_table.sql`) has **no RLS policies**. In Supabase, if the table was created via the Dashboard UI, RLS is enabled by default with **no policies** — meaning the `anon` key (used by `Database::query()` with `$useServiceKey = false`) **cannot read or write** to it.

**How to check:** In Supabase Dashboard → Authentication → Policies → select `user_sessions` table. If RLS is enabled with no policies, that's the problem.

**Fix:** Run this in Supabase SQL Editor:
```sql
ALTER TABLE public.user_sessions ENABLE ROW LEVEL SECURITY;
CREATE POLICY "anon_all_user_sessions" ON public.user_sessions
  FOR ALL USING (true) WITH CHECK (true);
```

---

### 🟡 3. `logout.php` doesn't clear the user-specific cookie

`logout.php` only clears `civentral_session` (line 40), but **not** `civentral_session_{user_id}`. This isn't the root cause of always getting OTP, but it's a bug — stale cookies accumulate.

---

### 🟡 4. `generateAndSendOtp()` hardcodes `remember_me => 1`

In `SessionAuthService.php` line ~40, the `$rememberMe` parameter is accepted but **ignored** — it always writes `'remember_me' => 1` to the DB. This doesn't break the bypass, but it's incorrect behavior.

---

### 🟢 5. Timezone mismatch on `expires_at`

`expires_at` is stored as `TIMESTAMPTZ` in Supabase (UTC), but PHP generates it with `date('Y-m-d H:i:s', strtotime('+10 days'))` using the server's local timezone (Asia/Manila, UTC+8). This causes an 8-hour discrepancy. If you test within 8 hours of the 10-day expiry, it could falsely report expired. Not the primary issue, but worth fixing.

---

## 📋 Proposed Fix Plan

| # | Action | File |
|---|--------|------|
| 1 | **Check & disable** `security.two_factor_auth` in Settings if it's ON | `management/settings.php` |
| 2 | **Add RLS policies** for `user_sessions` in Supabase SQL Editor | Supabase Dashboard |
| 3 | **Add debug logging** to `hasActiveVerifiedSession()` to log why it returns false (cookie value, session found, expires_at) | `app/services/SessionAuthService.php` |
| 4 | **Fix `logout.php`** to also clear `civentral_session_{user_id}` | `logout.php` |
| 5 | **Fix `generateAndSendOtp()`** to respect the `$rememberMe` parameter | `app/services/SessionAuthService.php` |
| 6 | **Fix timezone handling** — use `gmdate()` or store UTC timestamps consistently | `app/services/SessionAuthService.php` |

---

## 🔍 How to Test After Fixes

1. **Log in** → verify OTP with "Remember this device" ON.
2. **Check browser DevTools** → Application → Cookies → confirm `civentral_session_{user_id}` exists with a 10-day expiry.
3. **Check Supabase** → Table Editor → `user_sessions` → confirm a row exists with your `session_token` and `expires_at` in the future.
4. **Log out** → log back in → you should be **directly logged in** without OTP.

---

Would you like me to proceed with implementing these fixes? If so, please **toggle to Act mode** and I'll apply the code changes (debug logging, logout cookie fix, remember_me fix, timezone fix) and provide the SQL for the RLS policies.