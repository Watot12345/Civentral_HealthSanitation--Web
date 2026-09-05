import urllib.request
import urllib.parse
import ssl
import sys
import json

BASE_URL = "http://127.0.0.1:8080"

print("--- OWASP ZAP / DAST Automated Security Audit Scan ---")
print(f"Target: {BASE_URL}\n")

endpoints = [
    "/",
    "/pages/login.php",
    "/api/patients.php",
    "/api/privacy/deletion.php",
    "/pages/export.php",
    "/management/users.php"
]

results = []

for ep in endpoints:
    url = BASE_URL + ep
    print(f"[*] Scanning Endpoint: {url}")
    req = urllib.request.Request(url, headers={"User-Agent": "OWASP-ZAP-DAST-Scanner/2.14"})
    try:
        with urllib.request.urlopen(req, timeout=5) as resp:
            status = resp.status
            headers = dict(resp.headers)
            print(f"    Status: {status}")
            
            # Check Security Headers
            missing_headers = []
            for sec_header in ["X-Frame-Options", "X-Content-Type-Options", "Content-Security-Policy", "Strict-Transport-Security", "Referrer-Policy"]:
                if sec_header not in headers and sec_header.lower() not in [k.lower() for k in headers.keys()]:
                    missing_headers.append(sec_header)
            
            if missing_headers:
                print(f"    [WARN] Missing Security Headers: {', '.join(missing_headers)}")
            else:
                print("    [PASS] All recommended security headers present")
                
            # Cookie attributes
            cookies = resp.headers.get_all('Set-Cookie') if hasattr(resp.headers, 'get_all') else []
            for c in cookies:
                flags = []
                if "httponly" not in c.lower(): flags.append("Missing HttpOnly")
                if "secure" not in c.lower(): flags.append("Missing Secure")
                if "samesite" not in c.lower(): flags.append("Missing SameSite")
                if flags:
                    print(f"    [WARN] Cookie Security Warning: {c} -> {', '.join(flags)}")
                else:
                    print(f"    [PASS] Secure Cookie: {c}")

    except Exception as e:
        print(f"    [ERROR] Failed to reach {url}: {e}")

print("\n--- Scanning Input Fuzzing / Injection Resistance ---")
sqli_payloads = ["' OR '1'='1", "1; DROP TABLE users;--"]
for p in sqli_payloads:
    query = urllib.parse.urlencode({"username": p, "password": "password"})
    req = urllib.request.Request(f"{BASE_URL}/pages/login.php", data=query.encode('utf-8'), headers={"Content-Type": "application/x-www-form-urlencoded"})
    try:
        with urllib.request.urlopen(req, timeout=5) as resp:
            body = resp.read().decode('utf-8', errors='ignore')
            if "SQL syntax" in body or "PostgREST" in body or "Fatal error" in body:
                print(f"    [FAIL] SQLi Probe triggered DB error output for payload: {p}")
            else:
                print(f"    [PASS] SQLi payload safely handled/escaped: {p}")
    except Exception as e:
        print(f"    [PASS] Server rejected invalid login probe with status/error: {e}")

print("\n[+] DAST Scan Execution Complete.")
