import http from 'k6/http';
import { check, sleep, group } from 'k6';

// ============================================================================
// Civentral Health & Sanitation Management Information System
// Industry-Standard k6 Load & Concurrency Test Script
// ============================================================================

export const options = {
  stages: [
    { duration: '20s', target: 20 },  // Ramp-up to 20 virtual users
    { duration: '40s', target: 50 },  // Sustained load at 50 virtual users
    { duration: '30s', target: 100 }, // Stress test spike at 100 virtual users
    { duration: '20s', target: 0 },   // Ramp-down to 0 users
  ],
  thresholds: {
    http_req_duration: ['p(95)<500'], // 95% of requests must complete below 500ms
    http_req_failed: ['rate<0.02'],    // Failure rate under 2%
  },
};

const BASE_URL = __ENV.BASE_URL || 'http://127.0.0.1:8080';

export default function () {
  const params = {
    headers: {
      'Accept': 'application/json, text/html',
      'User-Agent': 'k6-Civentral-LoadTester/1.0',
    },
  };

  group('Public Gateway & Pages', function () {
    const res = http.get(`${BASE_URL}/index.php`, params);
    check(res, {
      'gateway status is 200': (r) => r.status === 200,
    });
  });

  group('Health & Sanitation Reporting API', function () {
    const res = http.get(`${BASE_URL}/api/reports/schedule.php`, params);
    check(res, {
      'schedule api status is 200': (r) => r.status === 200,
    });
  });

  group('Scheduler Telemetry', function () {
    const res = http.get(
      `${BASE_URL}/api/scheduler/run.php?stats=1&secret=civentral_health_cron_secret_2026`,
      params
    );
    check(res, {
      'scheduler status is 200': (r) => r.status === 200,
    });
  });

  group('Appointments & Queue Endpoint', function () {
    const res = http.get(`${BASE_URL}/api/appointments.php`, params);
    check(res, {
      'appointments handled (200 or 429 rate-limited)': (r) => r.status === 200 || r.status === 429,
    });
  });

  group('Patients Paginated Directory', function () {
    const res = http.get(`${BASE_URL}/api/patients.php?limit=20`, params);
    check(res, {
      'patients status is 200': (r) => r.status === 200,
    });
  });

  sleep(0.5);
}
