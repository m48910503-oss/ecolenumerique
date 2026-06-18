<?php
declare(strict_types=1);

namespace App\Services\Web;

final class CertificateDocxBuilder
{
    /**
     * @param array<string,mixed> $view
     */
    public function build(array $view): ?string
    {
        $cert = is_array($view['cert'] ?? null) ? $view['cert'] : [];
        $siteName = trim((string) ($view['siteName'] ?? 'École Numérique'));
        $fullName = trim((string) ($view['fullName'] ?? 'Étudiant'));
        $teacherName = trim((string) ($view['teacherName'] ?? 'Formateur'));
        $dgName = trim((string) ($view['dgName'] ?? 'Direction générale'));
        $issuedLabel = trim((string) ($view['issuedLabel'] ?? 'n/c'));
        $verifyUrl = trim((string) ($view['verifyUrl'] ?? ''));

        $courseTitle = trim((string) ($cert['course_title'] ?? 'Cours'));
        $examTitle = trim((string) ($cert['exam_title'] ?? 'Examen'));
        $score = $this->formatScore($cert['score'] ?? '');
        $code = trim((string) ($cert['code'] ?? 'n/c'));

        $tmpBase = tempnam(sys_get_temp_dir(), 'cert_docx_');
        if ($tmpBase === false) {
            return null;
        }
        @unlink($tmpBase);
        $tmpPath = $tmpBase . '.docx';
        $documentXml = $this->documentXml(
            $siteName,
            $fullName,
            $courseTitle,
            $examTitle,
            $score,
            $code,
            $issuedLabel,
            $teacherName,
            $dgName,
            $verifyUrl
        );

        $written = false;
        if (class_exists(\ZipArchive::class)) {
            $written = $this->buildWithZipArchive($tmpPath, $documentXml);
        }
        if (!$written && class_exists(\PharData::class) && class_exists(\Phar::class)) {
            $written = $this->buildWithPharData($tmpPath, $documentXml);
        }
        if (!$written) {
            @unlink($tmpPath);
            return null;
        }

        $bytes = @file_get_contents($tmpPath);
        @unlink($tmpPath);
        if ($bytes === false || $bytes === '') {
            return null;
        }

        return $bytes;
    }

    private function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            . '</Types>';
    }

    private function rootRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
            . '</Relationships>';
    }

    private function buildWithZipArchive(string $tmpPath, string $documentXml): bool
    {
        $zip = new \ZipArchive();
        $openResult = $zip->open($tmpPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        if ($openResult !== true) {
            return false;
        }

        $zip->addFromString('[Content_Types].xml', $this->contentTypesXml());
        $zip->addFromString('_rels/.rels', $this->rootRelsXml());
        $zip->addFromString('word/document.xml', $documentXml);
        $zip->close();

        return is_file($tmpPath);
    }

    private function buildWithPharData(string $tmpPath, string $documentXml): bool
    {
        @unlink($tmpPath);
        try {
            $phar = new \PharData($tmpPath, 0, null, \Phar::ZIP);
            $phar->addFromString('[Content_Types].xml', $this->contentTypesXml());
            $phar->addFromString('_rels/.rels', $this->rootRelsXml());
            $phar->addFromString('word/document.xml', $documentXml);
            unset($phar);
        } catch (\Throwable) {
            return false;
        }

        return is_file($tmpPath);
    }

    private function documentXml(
        string $siteName,
        string $fullName,
        string $courseTitle,
        string $examTitle,
        string $score,
        string $code,
        string $issuedLabel,
        string $teacherName,
        string $dgName,
        string $verifyUrl
    ): string {
        $scoreLine = $score !== '' ? $this->paragraph('Score : ' . $score) : '';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:document xmlns:wpc="http://schemas.microsoft.com/office/word/2010/wordprocessingCanvas"'
            . ' xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006"'
            . ' xmlns:o="urn:schemas-microsoft-com:office:office"'
            . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"'
            . ' xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math"'
            . ' xmlns:v="urn:schemas-microsoft-com:vml"'
            . ' xmlns:wp14="http://schemas.microsoft.com/office/word/2010/wordprocessingDrawing"'
            . ' xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing"'
            . ' xmlns:w10="urn:schemas-microsoft-com:office:word"'
            . ' xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"'
            . ' xmlns:w14="http://schemas.microsoft.com/office/word/2010/wordml"'
            . ' xmlns:wpg="http://schemas.microsoft.com/office/word/2010/wordprocessingGroup"'
            . ' xmlns:wpi="http://schemas.microsoft.com/office/word/2010/wordprocessingInk"'
            . ' xmlns:wne="http://schemas.microsoft.com/office/word/2006/wordml"'
            . ' xmlns:wps="http://schemas.microsoft.com/office/word/2010/wordprocessingShape"'
            . ' mc:Ignorable="w14 wp14">'
            . '<w:body>'
            . $this->paragraph($siteName, true, 34)
            . $this->paragraph('CERTIFICAT DE RÉUSSITE', true, 44)
            . $this->paragraph('Ce certificat est attribué à', false, 24)
            . $this->paragraph($fullName, true, 40)
            . $this->paragraph('', false, 20)
            . $this->paragraph('Examen : ' . $examTitle)
            . $this->paragraph('Cours : ' . $courseTitle)
            . $scoreLine
            . $this->paragraph('Code certificat : ' . $code)
            . $this->paragraph("Date d'émission : " . $issuedLabel)
            . $this->paragraph('', false, 20)
            . $this->paragraph('Enseignant : ' . $teacherName)
            . $this->paragraph('Direction générale : ' . $dgName)
            . $this->paragraph('', false, 20)
            . $this->paragraph('Vérification : ' . $verifyUrl, false, 18)
            . '<w:sectPr>'
            . '<w:pgSz w:w="16838" w:h="11906" w:orient="landscape"/>'
            . '<w:pgMar w:top="720" w:right="720" w:bottom="720" w:left="720" w:header="708" w:footer="708" w:gutter="0"/>'
            . '</w:sectPr>'
            . '</w:body>'
            . '</w:document>';
    }

    private function paragraph(string $text, bool $bold = false, int $sizeHalfPt = 22): string
    {
        $boldXml = $bold ? '<w:b/>' : '';
        $size = max(14, $sizeHalfPt);

        return '<w:p>'
            . '<w:r>'
            . '<w:rPr>' . $boldXml . '<w:sz w:val="' . $size . '"/><w:szCs w:val="' . $size . '"/></w:rPr>'
            . '<w:t xml:space="preserve">' . $this->safeText($text) . '</w:t>'
            . '</w:r>'
            . '</w:p>';
    }

    private function safeText(string $value): string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return '';
        }
        return htmlspecialchars($trimmed, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }

    /**
     * @param mixed $value
     */
    private function formatScore($value): string
    {
        if (is_numeric($value)) {
            return (string) (int) $value . '/100';
        }

        return trim((string) $value);
    }
}
