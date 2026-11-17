<?php

declare(strict_types=1);

function gemini_configured(): bool
{
    return GEMINI_API_KEY !== '';
}

/**
 * Sends one prompt to Gemini and returns the summary text or an error.
 *
 * Uses the Interactions API, the current recommended endpoint.
 *
 * @return array{ok: bool, text: string, error: string}
 */
function gemini_summarize(string $instruction, string $records): array
{
    if (!gemini_configured()) {
        return ['ok' => false, 'text' => '', 'error' => 'Gemini API key is not set.'];
    }

    $url = 'https://generativelanguage.googleapis.com/v1beta/interactions';

    $payload = [
        'model' => GEMINI_MODEL,
        'input' => $instruction . "\n\n" . $records,
        'store' => false,
        'generation_config' => [
            'max_output_tokens' => 2048,
        ],
    ];

    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'x-goog-api-key: ' . GEMINI_API_KEY,
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 60,
    ]);

    $response = curl_exec($curl);
    $curlError = curl_error($curl);
    $curlErrno = curl_errno($curl);
    $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($response === false) {
        if ($curlErrno === CURLE_OPERATION_TIMEDOUT) {
            return [
                'ok' => false,
                'text' => '',
                'error' => 'Gemini did not respond within 60 seconds. Try again or switch to a faster model.',
            ];
        }

        return ['ok' => false, 'text' => '', 'error' => 'Request failed: ' . $curlError];
    }

    $data = json_decode($response, true);

    if ($httpCode !== 200) {
        $message = gemini_error_message($data) ?? 'HTTP ' . $httpCode;

        return ['ok' => false, 'text' => '', 'error' => 'Gemini error: ' . $message];
    }

    if (is_array($data) && ($data['status'] ?? '') === 'failed') {
        $message = $data['errors'][0]['message'] ?? 'The interaction failed.';

        return ['ok' => false, 'text' => '', 'error' => 'Gemini error: ' . $message];
    }

    if (is_array($data) && ($data['status'] ?? '') === 'incomplete') {
        return [
            'ok' => false,
            'text' => '',
            'error' => 'Gemini stopped before finishing the summary. Try again.',
        ];
    }

    $text = gemini_output_text($data);

    if (trim($text) === '') {
        return ['ok' => false, 'text' => '', 'error' => 'Gemini returned an empty summary.'];
    }

    return ['ok' => true, 'text' => trim($text), 'error' => ''];
}

/**
 * Finds the error message whether the API returns an object or an array.
 */
function gemini_error_message(?array $data): ?string
{
    if (!is_array($data)) {
        return null;
    }

    $error = $data['error'] ?? $data[0]['error'] ?? null;

    if (!is_array($error)) {
        return null;
    }

    return isset($error['message']) && is_string($error['message']) ? $error['message'] : null;
}

/**
 * Pulls the text out of an interaction response.
 *
 * Text is split across model_output steps, so every text block is joined.
 * The SDKs expose the same thing as output_text.
 */
function gemini_output_text(?array $data): string
{
    if (!is_array($data)) {
        return '';
    }

    if (isset($data['output_text']) && is_string($data['output_text'])) {
        return $data['output_text'];
    }

    $parts = [];

    foreach ($data['steps'] ?? [] as $step) {
        if (($step['type'] ?? '') !== 'model_output') {
            continue;
        }

        foreach ($step['content'] ?? [] as $block) {
            if (($block['type'] ?? '') === 'text' && isset($block['text'])) {
                $parts[] = $block['text'];
            }
        }
    }

    return implode("\n", $parts);
}
