<?php
declare(strict_types=1);

namespace App\Services\Web;

final class PreviewTokenService
{
    public function issueCoursePreviewToken(int $courseId, int $lessonId, string $viewerKey, int $ttlSeconds = 180): string
    {
        $courseId = max(0, $courseId);
        $lessonId = max(0, $lessonId);
        $viewerKey = trim($viewerKey);
        if ($courseId < 1 || $lessonId < 1 || $viewerKey === '') {
            return '';
        }
        $secret = $this->secret();
        if ($secret === '') {
            return '';
        }

        if ($ttlSeconds <= 0) {
            $expiresAt = time() - 1;
        } else {
            $ttl = max(30, min(900, $ttlSeconds));
            $expiresAt = time() + $ttl;
        }
        $viewerHash = hash('sha256', $viewerKey);
        $payload = $courseId . '|' . $lessonId . '|' . $viewerHash . '|' . $expiresAt;
        $signature = hash_hmac('sha256', $payload, $secret);

        return $this->base64UrlEncode($payload) . '.' . $signature;
    }

    /**
     * @return array{course_id:int,lesson_id:int,expires_at:int}|null
     */
    public function verifyCoursePreviewToken(int $courseId, string $viewerKey, string $token): ?array
    {
        $courseId = max(0, $courseId);
        $viewerKey = trim($viewerKey);
        $token = trim($token);
        if ($courseId < 1 || $viewerKey === '' || $token === '') {
            return null;
        }
        $secret = $this->secret();
        if ($secret === '') {
            return null;
        }

        $parts = explode('.', $token, 2);
        if (count($parts) !== 2) {
            return null;
        }

        $payload = $this->base64UrlDecode($parts[0]);
        if ($payload === null) {
            return null;
        }

        $providedSig = (string) $parts[1];
        $expectedSig = hash_hmac('sha256', $payload, $secret);
        if (!hash_equals($expectedSig, $providedSig)) {
            return null;
        }

        $payloadParts = explode('|', $payload);
        if (count($payloadParts) !== 4) {
            return null;
        }

        $tokenCourseId = (int) ($payloadParts[0] ?? 0);
        $tokenLessonId = (int) ($payloadParts[1] ?? 0);
        $tokenViewerHash = (string) ($payloadParts[2] ?? '');
        $expiresAt = (int) ($payloadParts[3] ?? 0);
        if (
            $tokenCourseId !== $courseId
            || $tokenLessonId < 1
            || $expiresAt < time()
            || !hash_equals(hash('sha256', $viewerKey), $tokenViewerHash)
        ) {
            return null;
        }

        return [
            'course_id' => $tokenCourseId,
            'lesson_id' => $tokenLessonId,
            'expires_at' => $expiresAt,
        ];
    }

    public function buildYoutubePreviewEmbedUrl(?string $youtubeUrl, ?string $origin = null): ?string
    {
        $videoId = $this->youtubeVideoId($youtubeUrl);
        if ($videoId === null) {
            return null;
        }
        $safeVideoId = htmlspecialchars($videoId, ENT_QUOTES, 'UTF-8');
        $query = [
            'autoplay' => '1',
            'controls' => '0',
            'enablejsapi' => '1',
            'modestbranding' => '1',
            'rel' => '0',
            'playsinline' => '1',
            'iv_load_policy' => '3',
            'disablekb' => '0',
            'fs' => '1',
        ];
        $origin = trim((string) $origin);
        if ($origin !== '') {
            $query['origin'] = $origin;
        }

        return 'https://www.youtube-nocookie.com/embed/' . $safeVideoId . '?' . http_build_query($query);
    }

    private function secret(): string
    {
        if (\defined('APP_KEY')) {
            $appKey = trim((string) \constant('APP_KEY'));
            if ($appKey !== '') {
                return $appKey;
            }
        }
        return '';
    }

    private function base64UrlEncode(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $encoded): ?string
    {
        $encoded = strtr($encoded, '-_', '+/');
        $padding = strlen($encoded) % 4;
        if ($padding > 0) {
            $encoded .= str_repeat('=', 4 - $padding);
        }
        $decoded = base64_decode($encoded, true);
        if ($decoded === false) {
            return null;
        }
        return $decoded;
    }

    private function youtubeVideoId(?string $url): ?string
    {
        if (!$url) {
            return null;
        }
        $parts = parse_url($url);
        if (!$parts || empty($parts['host'])) {
            return null;
        }

        $host = (string) $parts['host'];
        if (str_contains($host, 'youtu.be')) {
            $videoId = trim((string) ($parts['path'] ?? ''), '/');
            return $videoId !== '' ? $videoId : null;
        }

        if (str_contains($host, 'youtube.com') && !empty($parts['query'])) {
            parse_str((string) $parts['query'], $query);
            if (!empty($query['v']) && is_string($query['v'])) {
                return $query['v'];
            }
        }

        return null;
    }
}
