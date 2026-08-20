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

        $ip = $clientIp ?: ($this->getClientIp());
        if ($ip === '127.0.0.1' || $ip === '::1' || $ip === 'localhost') {
            return [
                'allowed'   => true,
                'limit'     => 99999,
                'remaining' => 99999,
                'reset'     => 0
            ];
        }

        $file = $this->limitDir . '/' . md5($ip) . '.json';
        $now = time();

        $data = [
            'ip'          => $ip,
            'requests'    => 0,
            'reset_time'  => $now + $this->decaySeconds
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

        $remaining = max(0, $this->maxRequests - $data['requests']);
        $allowed = $data['requests'] <= $this->maxRequests;

        return [
            'allowed'    => $allowed,
            'limit'      => $this->maxRequests,
            'remaining'  => $remaining,
            'reset'      => max(1, $data['reset_time'] - $now)
        ];
    }

    private function getClientIp(): string
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
}
