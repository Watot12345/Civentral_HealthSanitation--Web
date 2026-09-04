<?php
// app/services/RateLimiterService.php

require_once __DIR__ . '/../../Core/Env.php';

class RateLimiterService
{
    private string $limitDir;
    private int $maxRequests;
    private int $decaySeconds;

    public function __construct(int $maxRequests = 30, int $decaySeconds = 60)
    {
        $this->maxRequests = $maxRequests;
        $this->decaySeconds = $decaySeconds;
        $this->limitDir = __DIR__ . '/../../storage/cache/rate_limits';
        if (!is_dir($this->limitDir)) {
            @mkdir($this->limitDir, 0755, true);
        }
    }

    /**
     * Check if client IP is within rate limits
     */
    public function check(?string $clientIp = null): array
    {
        Env::load();
        $enabled = Env::get('ENABLE_RATE_LIMITER');
        if ($enabled === 'false' || $enabled === '0' || $enabled === false) {
            return [
                'allowed'   => true,
                'limit'     => 99999,
                'remaining' => 99999,
                'reset'     => 0
            ];
        }

        $ip = $clientIp ?: $this->getClientIp();

        // Only allow local bypass if explicitly enabled in env AND remote address is truly local
        $bypassLocal = filter_var(Env::get('RATE_LIMITER_BYPASS_LOCAL', false), FILTER_VALIDATE_BOOLEAN);
        $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';
        if ($bypassLocal && in_array($remoteAddr, ['127.0.0.1', '::1'], true) && in_array($ip, ['127.0.0.1', '::1', 'localhost'], true)) {
            return [
                'allowed'   => true,
                'limit'     => 99999,
                'remaining' => 99999,
                'reset'     => 0
            ];
        }

        return $this->hit('ip_' . md5($ip), $this->maxRequests, $this->decaySeconds);
    }

    /**
     * Hit a specific rate limit key and check if allowed
     */
    public function hit(string $key, ?int $maxRequests = null, ?int $decaySeconds = null): array
    {
        $max = $maxRequests ?? $this->maxRequests;
        $decay = $decaySeconds ?? $this->decaySeconds;
        $file = $this->limitDir . '/' . hash('sha256', $key) . '.json';
        $now = time();

        $data = [
            'key'         => $key,
            'requests'    => 0,
            'reset_time'  => $now + $decay
        ];

        if (file_exists($file)) {
            $raw = @file_get_contents($file);
            $parsed = json_decode($raw, true);
            if ($parsed && isset($parsed['reset_time'])) {
                if ($now < $parsed['reset_time']) {
                    $data = $parsed;
                }
            }
        }

        $data['requests']++;
        @file_put_contents($file, json_encode($data));

        $remaining = max(0, $max - $data['requests']);
        $allowed = $data['requests'] <= $max;

        return [
            'allowed'    => $allowed,
            'requests'   => $data['requests'],
            'limit'      => $max,
            'remaining'  => $remaining,
            'reset'      => max(1, $data['reset_time'] - $now)
        ];
    }

    /**
     * Inspect status of a key without incrementing count
     */
    public function inspect(string $key, ?int $maxRequests = null, ?int $decaySeconds = null): array
    {
        $max = $maxRequests ?? $this->maxRequests;
        $decay = $decaySeconds ?? $this->decaySeconds;
        $file = $this->limitDir . '/' . hash('sha256', $key) . '.json';
        $now = time();

        $data = [
            'key'         => $key,
            'requests'    => 0,
            'reset_time'  => $now + $decay
        ];

        if (file_exists($file)) {
            $raw = @file_get_contents($file);
            $parsed = json_decode($raw, true);
            if ($parsed && isset($parsed['reset_time'])) {
                if ($now < $parsed['reset_time']) {
                    $data = $parsed;
                } else {
                    @unlink($file);
                }
            }
        }

        $remaining = max(0, $max - $data['requests']);
        $allowed = $data['requests'] < $max;

        return [
            'allowed'    => $allowed,
            'requests'   => $data['requests'],
            'limit'      => $max,
            'remaining'  => $remaining,
            'reset'      => max(1, $data['reset_time'] - $now)
        ];
    }

    /**
     * Clear rate limit for a key
     */
    public function clear(string $key): void
    {
        $file = $this->limitDir . '/' . hash('sha256', $key) . '.json';
        if (file_exists($file)) {
            @unlink($file);
        }
    }

    /**
     * Safely resolve client IP with trusted proxy verification
     */
    public function getClientIp(): string
    {
        $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        // Validate remote address
        if (!filter_var($remoteAddr, FILTER_VALIDATE_IP)) {
            $remoteAddr = '127.0.0.1';
        }

        // Only inspect proxy headers if the request is directly from a configured trusted proxy
        Env::load();
        $trustedProxiesStr = Env::get('TRUSTED_PROXIES', '');
        $trustedProxies = array_filter(array_map('trim', explode(',', (string)$trustedProxiesStr)));

        if (!empty($trustedProxies) && in_array($remoteAddr, $trustedProxies, true)) {
            if (!empty($_SERVER['HTTP_CLIENT_IP']) && filter_var($_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP)) {
                return $_SERVER['HTTP_CLIENT_IP'];
            }
            if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                foreach ($ips as $candidate) {
                    $candidate = trim($candidate);
                    if (filter_var($candidate, FILTER_VALIDATE_IP)) {
                        return $candidate;
                    }
                }
            }
        }

        return $remoteAddr;
    }
}
