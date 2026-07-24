<?php
declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Controller;
use App\Models\WebSeoModel;

final class SitemapController extends Controller
{
    public function __invoke(): void
    {
        $model = new WebSeoModel($this->pdo());
        try {
            $courses = $model->fetchSitemapCourses();
        } catch (\Throwable $e) {
            $courses = [];
        }

        header('Content-Type: application/xml; charset=UTF-8');

        $entries = [
            ['loc' => $this->absUrl(''), 'lastmod' => gmdate('Y-m-d\TH:i:s\Z'), 'priority' => '1.0'],
            ['loc' => $this->absUrl('catalogue'), 'lastmod' => gmdate('Y-m-d\TH:i:s\Z'), 'priority' => '0.9'],
            ['loc' => $this->absUrl('cgu'), 'lastmod' => null, 'priority' => '0.4'],
            ['loc' => $this->absUrl('privacy'), 'lastmod' => null, 'priority' => '0.4'],
        ];

        foreach ($courses as $course) {
            $slug = trim((string) ($course['slug'] ?? ''));
            $courseUrl = $slug !== ''
                ? $this->absUrl('course?slug=' . rawurlencode($slug))
                : $this->absUrl('course?id=' . (int) ($course['id'] ?? 0));
            $lastmod = $this->formatLastmod(
                (string) (($course['updated_at'] ?? '') !== '' ? $course['updated_at'] : ($course['created_at'] ?? ''))
            );
            $entries[] = [
                'loc' => $courseUrl,
                'lastmod' => $lastmod,
                'priority' => '0.8',
            ];
        }

        echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        echo "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
        foreach ($entries as $entry) {
            echo "  <url>\n";
            echo '    <loc>' . $this->xml((string) $entry['loc']) . "</loc>\n";
            if (!empty($entry['lastmod'])) {
                echo '    <lastmod>' . $this->xml((string) $entry['lastmod']) . "</lastmod>\n";
            }
            echo '    <priority>' . $this->xml((string) $entry['priority']) . "</priority>\n";
            echo "  </url>\n";
        }
        echo "</urlset>";
    }

    private function formatLastmod(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return null;
        }
        return gmdate('Y-m-d\TH:i:s\Z', $timestamp);
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
}
