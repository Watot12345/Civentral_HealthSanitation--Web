<?php
// app/services/GeminiAiService.php

require_once __DIR__ . '/../../Core/Env.php';

class GeminiAiService
{
    private ?string $apiKey;
    private string $model;
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/';
    private string $cacheDir;
    private int $ttlSeconds = 1800; // 30 Minutes Cache TTL
    private int $maxCallsPerWindow = 5; // Max 5 external Gemini API calls per 30 mins

    public function __construct()
    {
        Env::load();
        $this->apiKey = Env::get('GEMINI_API_KEY') ?: (getenv('GEMINI_API_KEY') ?: ($_ENV['GEMINI_API_KEY'] ?? null));
        $this->model  = Env::get('GEMINI_MODEL') ?: 'gemini-3.5-flash-lite';
        $this->cacheDir = __DIR__ . '/../../storage/cache';
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * Enriches native database metrics with short, concise AI suggestions (STRICTLY 10 WORDS MAX EACH).
     * All math & metrics are calculated by PHP/Database; AI provides short actionable suggestions.
     */
    public function enrichInsights(array $nativeInsights, array $dbContext = []): array
    {
        // Enforce 10 words max on all native titles
        foreach ($nativeInsights as $idx => $item) {
            if (isset($item['title'])) {
                $nativeInsights[$idx]['title'] = $this->limitWords($item['title'], 10);
            }
        }

        if (empty($this->apiKey) || $this->apiKey === 'your_gemini_api_key_here') {
            return $nativeInsights; // Return offline database insights when no external API key is set
        }

        $cacheFile = $this->cacheDir . '/gemini_insights_cache.json';

        // 1. Check 30-Minute LLM Cache
        if (file_exists($cacheFile)) {
            $raw = @file_get_contents($cacheFile);
            $cached = json_decode($raw, true);
            if ($cached && isset($cached['expires_at']) && time() < $cached['expires_at']) {
                return $cached['data'];
            }
        }

        // 2. Check Rate Limiter
        if (!$this->canMakeApiCall()) {
            if (file_exists($cacheFile)) {
                $raw = @file_get_contents($cacheFile);
                $cached = json_decode($raw, true);
                if (!empty($cached['data'])) {
                    return $cached['data'];
                }
            }
            return $nativeInsights;
        }

        // 3. Execute Gemini Flash-Lite Call with full Database Snapshot context
        try {
            $systemInstruction = "STRICT INSTRUCTION: Analyze the live database snapshot and output a JSON array of 4 short operational suggestion strings (10 words MAX each). Keep all calculated DB numbers intact. Output raw JSON array only.";
            
            $prompt = $systemInstruction . "\nLive Database Snapshot: " . json_encode($dbContext) . "\nCalculated Database Insights: " . json_encode($nativeInsights);

            $endpoint = $this->baseUrl . urlencode($this->model) . ':generateContent?key=' . urlencode($this->apiKey);

            $payload = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.1,
                    'maxOutputTokens' => 150
                ]
            ];

            $ch = curl_init($endpoint);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $response) {
                $result = json_decode($response, true);
                $rawText = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
                
                // Parse JSON array from raw output
                $jsonStart = strpos($rawText, '[');
                $jsonEnd = strrpos($rawText, ']');
                if ($jsonStart !== false && $jsonEnd !== false) {
                    $jsonStr = substr($rawText, $jsonStart, $jsonEnd - $jsonStart + 1);
                    $suggestions = json_decode($jsonStr, true);

                    if (is_array($suggestions) && count($suggestions) >= 4) {
                        foreach ($nativeInsights as $i => $item) {
                            if (isset($suggestions[$i])) {
                                $nativeInsights[$i]['ai_suggestion'] = $this->limitWords($suggestions[$i], 10);
                            }
                        }
                    }
                }
                
                // Cache response for 30 minutes
                @file_put_contents($cacheFile, json_encode([
                    'expires_at' => time() + 1800,
                    'data' => $nativeInsights
                ]));
                $this->recordApiCall();
            }
        } catch (Throwable $e) {
            error_log('GeminiAiService API Error: ' . $e->getMessage());
        }

        return $nativeInsights;
    }

    private function limitWords(string $text, int $maxWords = 25): string
    {
        $clean = trim(strip_tags($text, '<span>'));
        return $clean;
    }

    private function canMakeApiCall(): bool
    {
        $limitFile = $this->cacheDir . '/gemini_rate_limit.json';
        $now = time();

        if (!file_exists($limitFile)) {
            return true;
        }

        $raw = @file_get_contents($limitFile);
        $data = json_decode($raw, true);

        if (!$data || !isset($data['reset_at'])) {
            return true;
        }

        if ($now > $data['reset_at']) {
            return true;
        }

        return ($data['calls'] ?? 0) < $this->maxCallsPerWindow;
    }

    private function recordApiCall(): void
    {
        $limitFile = $this->cacheDir . '/gemini_rate_limit.json';
        $now = time();

        $calls = 1;
        $resetAt = $now + $this->ttlSeconds;

        if (file_exists($limitFile)) {
            $raw = @file_get_contents($limitFile);
            $data = json_decode($raw, true);
            if ($data && isset($data['reset_at']) && $now < $data['reset_at']) {
                $calls = ($data['calls'] ?? 0) + 1;
                $resetAt = $data['reset_at'];
            }
        }

        @file_put_contents($limitFile, json_encode([
            'calls' => $calls,
            'reset_at' => $resetAt,
            'last_call_at' => $now
        ]));
    }

    private function saveToCache(string $cacheFile, array $data): void
    {
        $payload = [
            'created_at' => time(),
            'expires_at' => time() + $this->ttlSeconds,
            'data' => $data
        ];
        @file_put_contents($cacheFile, json_encode($payload, JSON_PRETTY_PRINT));
    }

    public function getModelName(): string
    {
        return $this->model;
    }
}
