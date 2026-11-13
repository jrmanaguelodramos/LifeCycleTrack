<?php
declare(strict_types=1);

class ai_integ
{
    private array $config;

    public function __construct()
    {
        $this->config = require dirname(__DIR__) . '/config/ai_config.php';
    }

    public function generate(array $analytics): array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $cacheKey = 'utility_ai_' . hash(
            'sha256',
            json_encode($analytics, JSON_PARTIAL_OUTPUT_ON_ERROR)
        );

        if (isset($_SESSION[$cacheKey])) {
            $cached = $_SESSION[$cacheKey];

            if (
                isset($cached['created_at'], $cached['result']) &&
                time() - $cached['created_at'] < ($this->config['cache_seconds'] ?? 3600)
            ) {
                return $cached['result'];
            }
        }

        $apiKey = trim((string)($this->config['api_key'] ?? ''));

        if ($apiKey === '' || $apiKey === 'YOUR_OPENROUTER_API_KEY') {
            return $this->failed('OpenRouter API key is not configured.');
        }

        // prompt para sa sugestion 
        $systemPrompt = <<<'PROMPT'
            You are a utility analytics assistant for a university facility management system.

            Analyze the supplied electricity and water billing statistics.

            Rules:
            1. Use only the supplied data.
            2. Never invent consumption, costs, dates, or missing records.
            3. Do not claim that a leak or equipment defect exists without evidence.
            4. Treat anomalies as indicators that require investigation.
            5. Consider consumption changes and cost changes.
            6. Explain unusual increases in simple language.
            7. If there are no anomalies, recommend regular monitoring.
            8. If records are missing, recommend verifying the records.
            9. Recommend practical actions for university facilities and equipment.
            10. Provide at most four concise suggestions.

            Return ONLY a valid JSON object.
            Do not include Markdown, code fences, or additional explanations.
            Every string value must be written on a single line: no line breaks, tabs, or bullet characters.

            Required format:
            {
                "title": "Short analysis title",
                "suggestions": [
                    "First recommendation",
                    "Second recommendation"
                ]
            }

            All JSON keys and string values must use double quotes.
            PROMPT;

        $userPrompt = "Analyze the following predefined utility analytics data:\n\n"
            . json_encode(
                $analytics,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
            );

        $payload = [
            'model' => $this->config['model'],
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $systemPrompt
                ],
                [
                    'role' => 'user',
                    'content' => $userPrompt
                ]
            ],
            'temperature' => 0.2,
            'max_tokens' => 1000,
            'response_format' => [
                'type' => 'json_object'
            ]
        ];

        $requestBody = json_encode($payload);

        if ($requestBody === false) {
            return $this->failed('Unable to encode AI request.');
        }

        $http = $this->sendRequest((string)$this->config['api_url'], $apiKey, $requestBody);

        if ($http['response'] === false) {
            return $this->failed(
                'Unable to connect to OpenRouter: ' . $http['error']
                . ' (cURL error ' . $http['errno'] . ', after ' . $http['attempts'] . ' attempt(s))'
            );
        }

        $response = $http['response'];
        $httpCode = $http['code'];

        $decoded = json_decode($response, true);

        if ($httpCode < 200 || $httpCode >= 300) {
            $apiMessage = $decoded['error']['message'] ?? 'Unknown API error';

            error_log('OpenRouter API response: ' . substr($response, 0, 2000));

            return $this->failed(
                'OpenRouter HTTP ' . $httpCode . ': ' . $apiMessage
            );
        }

        if (!is_array($decoded)) {
            return $this->failed('Invalid OpenRouter API response.');
        }

        $message = $decoded['choices'][0]['message'] ?? [];
        $content = $message['content'] ?? '';

        if (is_array($content)) {
            $textParts = [];

            foreach ($content as $item) {
                if (is_array($item) && isset($item['text'])) {
                    $textParts[] = $item['text'];
                }
            }

            $content = implode("\n", $textParts);
        }

        if (!is_string($content) || trim($content) === '') {
            $reason = $message['refusal']
                ?? $decoded['error']['message']
                ?? 'The AI returned an empty response.';

            return $this->failed($reason);
        }

        $result = $this->parseAiJson($content);

        if ($result === null) {
            error_log('OpenRouter JSON error: ' . json_last_error_msg());
            error_log('OpenRouter raw AI response: ' . substr($content, 0, 3000));

            return $this->failed(
                'The AI did not return valid JSON: ' . json_last_error_msg()
            );
        }

        $title = $result['title'] ?? 'Utility Analytics Recommendations';

        if (!is_string($title) || trim($title) === '') {
            $title = 'Utility Analytics Recommendations';
        }

        $suggestions = $result['suggestions'] ?? [];

        if (is_string($suggestions)) {
            $suggestions = [$suggestions];
        }

        if (!is_array($suggestions)) {
            return $this->failed('Invalid AI suggestions format.');
        }

        $cleanSuggestions = [];

        foreach ($suggestions as $suggestion) {
            if (is_string($suggestion) && trim($suggestion) !== '') {
                $cleanSuggestions[] = trim($suggestion);
            }

            if (count($cleanSuggestions) >= 4) {
                break;
            }
        }

        if (empty($cleanSuggestions)) {
            return $this->failed('No valid AI suggestions were returned.');
        }

        $finalResult = [
            'ok' => true,
            'title' => mb_substr(trim($title), 0, 150),
            'suggestions' => $cleanSuggestions
        ];

        $_SESSION[$cacheKey] = [
            'created_at' => time(),
            'result' => $finalResult
        ];

        return $finalResult;
    }

    private function sendRequest(string $url, string $apiKey, string $body): array
    {
        $retryableErrno = [6, 7, 28, 35, 52, 55, 56];
        $retryableHttp = [429, 500, 502, 503, 504];
        $maxAttempts = 3;

        $out = ['response' => false, 'code' => 0, 'errno' => 0, 'error' => '', 'attempts' => 0];

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $ch = curl_init($url);

            if ($ch === false) {
                $out['error'] = 'Unable to initialize cURL.';
                $out['attempts'] = $attempt;
                return $out;
            }

            $options = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $apiKey,
                    'Content-Type: application/json',
                    'HTTP-Referer: ' . ($this->config['site_url'] ?? ''),
                    'X-OpenRouter-Title: ' . ($this->config['site_name'] ?? 'Utility Analytics')
                ],
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_TIMEOUT => (int)($this->config['timeout'] ?? 60),
                CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_FORBID_REUSE => true,
                CURLOPT_FRESH_CONNECT => true,
            ];

            $caBundle = (string)($this->config['ca_bundle'] ?? '');
            if ($caBundle !== '' && is_file($caBundle)) {
                $options[CURLOPT_CAINFO] = $caBundle;
            }

            curl_setopt_array($ch, $options);

            $response = curl_exec($ch);
            $out['code'] = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $out['errno'] = curl_errno($ch);
            $out['error'] = curl_error($ch);
            $out['attempts'] = $attempt;
            curl_close($ch);

            if ($response !== false && $out['errno'] === 0) {
                $out['response'] = $response;

                if (in_array($out['code'], $retryableHttp, true) && $attempt < $maxAttempts) {
                    usleep(600000 * $attempt);
                    continue;
                }

                return $out;
            }

            $out['response'] = false;

            if (!in_array($out['errno'], $retryableErrno, true) || $attempt === $maxAttempts) {
                return $out;
            }

            usleep(600000 * $attempt);
        }

        return $out;
    }

    private function parseAiJson(string $content): ?array
    {
        $content = trim($content);

        $content = preg_replace('/^```(?:json)?\s*/i', '', $content);
        $content = preg_replace('/\s*```$/', '', $content);

        $start = strpos($content, '{');
        $end = strrpos($content, '}');

        if ($start !== false && $end !== false && $end >= $start) {
            $content = substr($content, $start, $end - $start + 1);
        }

        $result = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($result)) {
            return $result;
        }

        $clean = preg_replace_callback('/"(?:[^"\\\\]|\\\\.)*"/s', function (array $m): string {
            return strtr($m[0], ["\r" => '\\r', "\n" => '\\n', "\t" => '\\t']);
        }, $content);

        $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', (string)$clean);

        $result = json_decode((string)$clean, true, 512, JSON_INVALID_UTF8_SUBSTITUTE);
        if (json_last_error() === JSON_ERROR_NONE && is_array($result)) {
            return $result;
        }

        return null;
    }

    private function failed(string $message): array
    {
        error_log('Utility AI Insight: ' . $message);

        return [
            'ok' => false,
            'error' => $message,
            'title' => 'AI suggestions unavailable',
            'suggestions' => []
        ];
    }
}