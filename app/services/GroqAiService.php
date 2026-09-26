<?php
// app/services/GroqAiService.php

require_once __DIR__ . '/../../Core/Env.php';

class GroqAiService
{
    private ?string $apiKey;
    private string $model;
    private string $baseUrl = 'https://api.groq.com/openai/v1/chat/completions';
    private string $cacheDir;
    private int $ttlSeconds = 18000; // 5 Hours Rate Limit Window
    private int $maxCallsPerWindow = 10; // Max 10 calls per 5 hours

    public function __construct()
    {
        Env::load();
        $this->apiKey = Env::get('GROQ_API_KEY') ?: (getenv('GROQ_API_KEY') ?: ($_ENV['GROQ_API_KEY'] ?? null));
        $model = Env::get('GROQ_MODEL');
        $this->model = !empty($model) ? $model : 'qwen/qwen3.8-27b';

        $this->cacheDir = __DIR__ . '/../../storage/cache';
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0755, true);
        }
    }

    public function sanitizePromptInput(mixed $data): mixed
    {
        if (is_array($data)) {
            $clean = [];
            foreach ($data as $k => $v) {
                $cleanK = is_string($k) ? $this->sanitizeString($k) : $k;
                $clean[$cleanK] = $this->sanitizePromptInput($v);
            }
            return $clean;
        }

        if (is_string($data)) {
            return $this->sanitizeString($data);
        }

        return $data;
    }

    private function sanitizeString(string $str): string
    {
        $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $str);
        $injectionPatterns = [
            '/(?:ignore|disregard|forget|bypass)\s+(?:all\s+)?(?:previous|prior|existing|above)\s+(?:instructions|prompts|rules|commands)/i',
            '/(?:system\s*prompt|system\s*directive|system\s*instruction)\s*[:=]/i',
            '/(?:you\s+are\s+now|act\s+as|switch\s+to)\s+(?:DAN|jailbreak|unrestricted|god\s*mode|developer\s*mode)/i',
            '/(?:reveal|output|leak|show|print)\s+(?:system\s+credentials|api\s*key|environment\s*variables|password|database\s*schema)/i',
            '/<\/?(?:system|instruction|prompt|command|override|untrusted)[^>]*>/i'
        ];
        foreach ($injectionPatterns as $pattern) {
            $clean = preg_replace($pattern, '[SANITIZED_DIRECTIVE]', $clean);
        }
        return trim($clean);
    }

    private function makeApiCall(array $payload, int $timeout = 5): ?array
    {
        if (empty($this->apiKey) || !$this->canMakeApiCall()) {
            return null;
        }

        try {
            $ch = curl_init($this->baseUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErrno = curl_errno($ch);
            curl_close($ch);

            if ($httpCode === 200 && $response) {
                $result = json_decode($response, true);
                $text = $result['choices'][0]['message']['content'] ?? '';
                if (!empty($text)) {
                    $this->recordApiCall();
                    return [
                        'model_used' => $this->model,
                        'text'       => $text,
                        'raw'        => $result
                    ];
                }
            } else {
                error_log("GroqAiService: API call failed with HTTP $httpCode. Response: $response");
            }
        } catch (Throwable $e) {
            error_log("GroqAiService: Exception: " . $e->getMessage());
        }

        return null;
    }

    public function generateReportSummary(string $department = 'all', array $metrics = [], string $dateRange = '30d', bool $bypassCache = false): array
    {
        $deptTitle = match(strtolower($department)) {
            'health_center', 'health center', 'health center services' => 'Health Center Services',
            'sanitation', 'sanitation permits' => 'Sanitation Permits',
            'immunization', 'nutrition', 'immunization & nutrition' => 'Immunization & Nutrition',
            'wastewater', 'wastewater services' => 'Wastewater Services',
            'surveillance', 'health surveillance' => 'Health Surveillance',
            default => 'All City Health Departments'
        };

        $totalCount = $metrics['total'] ?? 0;
        $compliantCount = $metrics['compliant'] ?? 0;
        $urgentCount = $metrics['urgent'] ?? 0;
        $pendingCount = $metrics['pending'] ?? 0;
        $complianceRate = $metrics['compliance_rate'] ?? ($totalCount > 0 ? round(($compliantCount / $totalCount) * 100, 1) : 0);

        $recordLabel = match(strtolower($department)) {
            'health_center', 'health center', 'health center services' => 'patient encounters/consultations',
            'sanitation', 'sanitation permits' => 'permits/inspections',
            'immunization', 'nutrition', 'immunization & nutrition' => 'immunization records',
            'wastewater', 'wastewater services' => 'invoices/services',
            'surveillance', 'health surveillance' => 'disease cases',
            default => 'operational records'
        };

        if ($totalCount === 0) {
            $complianceRate = 0;
            $fallback = [
                'department' => $deptTitle,
                'executive_summary' => "No operational data or records were found for {$deptTitle} during the selected period ({$dateRange}). Please adjust your date filters or check the department modules to ensure data is being logged correctly.",
                'key_findings' => [
                    "0 records found within the specified date range.",
                    "Department metrics cannot be calculated due to lack of data."
                ],
                'recommendations' => [
                    "Ensure staff are properly logging encounters and tasks.",
                    "Expand the date range to verify historical operations."
                ],
                'risk_level' => 'Optimal',
                'ai_generated' => false
            ];
            
            // Return early without hitting the Groq API if there's absolutely no data
            return array_merge(['success' => true], $fallback);
        }

        $fallback = [
            'department' => $deptTitle,
            'executive_summary' => "Operational evaluation for {$deptTitle} demonstrates a {$complianceRate}% overall success rate over the past {$dateRange}. A total of {$totalCount} {$recordLabel} were logged in the system, with {$urgentCount} urgent items flagged for immediate attention.",
            'key_findings' => [
                "{$deptTitle} maintains an operational index of {$complianceRate}%.",
                "Identified {$urgentCount} urgent priority records requiring rapid response team intervention.",
                "Managing {$pendingCount} active queue items currently undergoing processing."
            ],
            'recommendations' => [
                "Review current procedures for {$deptTitle} to ensure optimal workflow.",
                "Address the {$pendingCount} pending items to improve resolution rate.",
                "Monitor any urgent items closely to mitigate potential risks."
            ],
            'risk_level' => ($urgentCount > 5) ? 'High Risk' : (($urgentCount > 0) ? 'Moderate Risk' : 'Optimal'),
            'ai_generated' => false
        ];

        $cacheFile = $this->cacheDir . '/groq_report_summary_' . md5(strtolower($department) . '_' . $dateRange . '_' . json_encode($metrics)) . '.json';
        if ($bypassCache && file_exists($cacheFile)) {
            @unlink($cacheFile);
        } elseif (!$bypassCache && file_exists($cacheFile)) {
            $raw = @file_get_contents($cacheFile);
            $cached = json_decode($raw, true);
            if ($cached && isset($cached['expires_at']) && time() < $cached['expires_at'] && !empty($cached['data'])) {
                return array_merge($cached['data'], ['cached' => true]);
            }
        }

        $rateLimited = !$this->canMakeApiCall();
        if (empty($this->apiKey) || $rateLimited) {
            if ($rateLimited && file_exists($cacheFile)) {
                $raw = @file_get_contents($cacheFile);
                $cached = json_decode($raw, true);
                if ($cached && !empty($cached['data'])) {
                    return array_merge($cached['data'], [
                        'cached' => true,
                        'rate_limited' => true,
                        'message' => 'AI is rate limited (10 requests per 5 hours). Showing cached data.'
                    ]);
                }
            }
            return array_merge($fallback, [
                'rate_limited' => $rateLimited,
                'message' => $rateLimited ? 'AI is rate limited (10 requests per 5 hours). Showing fallback data.' : ''
            ]);
        }

        try {
            $sanitizedMetrics = $this->sanitizePromptInput($metrics);
            $cleanDept = htmlspecialchars(strip_tags($deptTitle));

            $systemPrompt = "You are an expert Public Health Analyst for the Caloocan City Health Department. Your job is to analyze the provided database metrics and write a professional operational summary.\n" .
                            "CRITICAL RULES:\n" .
                            "1. Base your entire analysis STRICTLY on the numerical data provided. Do NOT hallucinate, invent, or guess any statistics, dates, or names.\n" .
                            "2. The metrics represent: 'total' (total transactions), 'compliant' (successfully resolved/approved), 'urgent' (critical issues needing immediate action), 'pending' (currently queued/under review), and 'compliance_rate' (percentage of compliant vs total).\n" .
                            "3. Your output MUST be a raw JSON object ONLY (no markdown, no backticks) with these exact keys:\n" .
                            "   - 'executive_summary' (string, 2-3 sentences summarizing the operational status)\n" .
                            "   - 'key_findings' (array of 3 short strings highlighting the most important numbers)\n" .
                            "   - 'recommendations' (array of 3 short actionable strings tailored SPECIFICALLY to the '{$cleanDept}' department, based on the numbers. Do not mention other departments or generic database logging.)\n" .
                            "   - 'risk_level' (string: strictly 'Optimal', 'Moderate Risk', or 'High Risk' based on the 'urgent' count).";

            $userPrompt = "Please analyze the following live database metrics for the '{$cleanDept}' department:\n" .
                          json_encode($sanitizedMetrics, JSON_PRETTY_PRINT) . "\n" .
                          "Output the result strictly in the requested JSON format.";

            $payload = [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt]
                ],
                'temperature' => 0.2,
                'max_tokens' => 300,
                'response_format' => ['type' => 'json_object']
            ];

            $apiResult = $this->makeApiCall($payload, 5);

            if ($apiResult) {
                $rawText = trim($apiResult['text']);
                $jsonStart = strpos($rawText, '{');
                $jsonEnd = strrpos($rawText, '}');
                if ($jsonStart !== false && $jsonEnd !== false) {
                    $jsonStr = substr($rawText, $jsonStart, $jsonEnd - $jsonStart + 1);
                    $aiData = json_decode($jsonStr, true);

                    if (is_array($aiData) && !empty($aiData['executive_summary'])) {
                        $this->recordApiCall();
                        $finalData = array_merge($fallback, $aiData, [
                            'ai_generated' => true,
                            'model_used'   => $apiResult['model_used']
                        ]);
                        $cachePayload = [
                            'created_at' => time(),
                            'expires_at' => time() + 300,
                            'data'       => $finalData
                        ];
                        @file_put_contents($cacheFile, json_encode($cachePayload, JSON_PRETTY_PRINT));
                        return $finalData;
                    }
                }
            }
        } catch (Throwable $e) {
            error_log('Groq Report Summary Error: ' . $e->getMessage());
        }

        return $fallback;
    }

    private function canMakeApiCall(): bool
    {
        $limitFile = $this->cacheDir . '/groq_rate_limit.json';
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

    public function getRemainingRequests(): int
    {
        $limitFile = $this->cacheDir . '/groq_rate_limit.json';
        $now = time();

        if (!file_exists($limitFile)) {
            return $this->maxCallsPerWindow;
        }

        $fp = @fopen($limitFile, 'r');
        if (!$fp) {
            return $this->maxCallsPerWindow;
        }

        flock($fp, LOCK_SH);
        $raw = stream_get_contents($fp);
        flock($fp, LOCK_UN);
        fclose($fp);

        $data = json_decode($raw, true);

        if (!$data || !isset($data['reset_at']) || $now > $data['reset_at']) {
            return $this->maxCallsPerWindow;
        }

        $calls = $data['calls'] ?? 0;
        return max(0, $this->maxCallsPerWindow - $calls);
    }

    private function recordApiCall(): void
    {
        $limitFile = $this->cacheDir . '/groq_rate_limit.json';
        $now = time();
        $calls = 1;
        $resetAt = $now + $this->ttlSeconds;

        $fp = @fopen($limitFile, 'c+');
        if ($fp) {
            flock($fp, LOCK_EX);
            $raw = stream_get_contents($fp);
            if (!empty($raw)) {
                $data = json_decode($raw, true);
                if ($data && isset($data['reset_at']) && $now < $data['reset_at']) {
                    $calls = ($data['calls'] ?? 0) + 1;
                    $resetAt = $data['reset_at'];
                }
            }

            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, json_encode([
                'calls' => $calls,
                'reset_at' => $resetAt,
                'last_call_at' => $now
            ]));
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }
}
