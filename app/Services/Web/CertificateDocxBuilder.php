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
        $siteTagline = trim((string) ($view['siteTagline'] ?? ''));
        $siteDescription = trim((string) ($view['siteDescription'] ?? ''));
        $supportEmail = trim((string) ($view['supportEmail'] ?? ''));
        $supportPhone = trim((string) ($view['supportPhone'] ?? ''));
        $supportWhatsapp = trim((string) ($view['supportWhatsapp'] ?? ''));
        $footerText = trim((string) ($view['footerText'] ?? ''));
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
            $siteTagline,
            $siteDescription,
            $fullName,
            $courseTitle,
            $examTitle,
            $score,
            $code,
            $issuedLabel,
            $teacherName,
            $dgName,
            $supportEmail,
            $supportPhone,
            $supportWhatsapp,
            $footerText,
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

    public function buildFromImage(string $imageBinary): ?string
    {
        if ($imageBinary === '') {
            return null;
        }

        $tmpBase = tempnam(sys_get_temp_dir(), 'cert_docx_img_');
        if ($tmpBase === false) {
            return null;
        }
        @unlink($tmpBase);
        $tmpPath = $tmpBase . '.docx';

        $pageWidthTwips = 16838;
        $pageHeightTwips = 11906;
        $documentXml = $this->imageDocumentXml($pageWidthTwips, $pageHeightTwips);

        $files = [
            '[Content_Types].xml' => $this->contentTypesXml(true),
            '_rels/.rels' => $this->rootRelsXml(),
            'word/document.xml' => $documentXml,
            'word/_rels/document.xml.rels' => $this->documentImageRelsXml(),
            'word/media/image1.png' => $imageBinary,
        ];

        $written = false;
        if (class_exists(\ZipArchive::class)) {
            $written = $this->buildWithZipArchive($tmpPath, $files);
        }
        if (!$written && class_exists(\PharData::class) && class_exists(\Phar::class)) {
            $written = $this->buildWithPharData($tmpPath, $files);
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

    private function contentTypesXml(bool $includePng = false): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>';

        if ($includePng) {
            $xml .= '<Default Extension="png" ContentType="image/png"/>';
        }

        return $xml . '</Types>';
    }

    private function rootRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
            . '</Relationships>';
    }

    /**
     * @param array<string,string> $files
     */
    private function buildWithZipArchive(string $tmpPath, array $files): bool
    {
        $zip = new \ZipArchive();
        $openResult = $zip->open($tmpPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        if ($openResult !== true) {
            return false;
        }

        foreach ($files as $path => $content) {
            $zip->addFromString($path, $content);
        }
        $zip->close();

        return is_file($tmpPath);
    }

    /**
     * @param array<string,string> $files
     */
    private function buildWithPharData(string $tmpPath, array $files): bool
    {
        @unlink($tmpPath);
        try {
            $phar = new \PharData($tmpPath, 0, null, \Phar::ZIP);
            foreach ($files as $path => $content) {
                $phar->addFromString($path, $content);
            }
            unset($phar);
        } catch (\Throwable) {
            return false;
        }

        return is_file($tmpPath);
    }

    private function documentXml(
        string $siteName,
        string $siteTagline,
        string $siteDescription,
        string $fullName,
        string $courseTitle,
        string $examTitle,
        string $score,
        string $code,
        string $issuedLabel,
        string $teacherName,
        string $dgName,
        string $supportEmail,
        string $supportPhone,
        string $supportWhatsapp,
        string $footerText,
        string $verifyUrl
    ): string {
        $scoreLine = $score !== '' ? $this->paragraph('Score : ' . $score) : '';
        $taglineLine = $siteTagline !== '' ? $this->paragraph($siteTagline, false, 20) : '';
        $descriptionLine = $siteDescription !== '' ? $this->paragraph($siteDescription, false, 18) : '';
        $supportParts = array_values(array_filter([
            $supportEmail !== '' ? $supportEmail : null,
            $supportPhone !== '' ? $supportPhone : null,
            $supportWhatsapp !== '' ? $supportWhatsapp : null,
        ], static fn ($value): bool => is_string($value) && trim($value) !== ''));
        $supportLine = $supportParts !== [] ? $this->paragraph('Support : ' . implode(' · ', $supportParts), false, 18) : '';
        $footerLine = $footerText !== '' ? $this->paragraph($footerText, false, 18) : '';

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
            . $taglineLine
            . $descriptionLine
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
            . $supportLine
            . $footerLine
            . $this->paragraph('Vérification : ' . $verifyUrl, false, 18)
            . '<w:sectPr>'
            . '<w:pgSz w:w="16838" w:h="11906" w:orient="landscape"/>'
            . '<w:pgMar w:top="720" w:right="720" w:bottom="720" w:left="720" w:header="708" w:footer="708" w:gutter="0"/>'
            . '</w:sectPr>'
            . '</w:body>'
            . '</w:document>';
    }

    private function imageDocumentXml(int $pageWidthTwips, int $pageHeightTwips): string
    {
        $cx = $this->twipsToEmu($pageWidthTwips);
        $cy = $this->twipsToEmu($pageHeightTwips);

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
            . '<w:p>'
            . '<w:pPr>'
            . '<w:spacing w:before="0" w:after="0" w:line="0"/>'
            . '<w:jc w:val="center"/>'
            . '</w:pPr>'
            . '<w:r>'
            . '<w:drawing>'
            . $this->inlinePictureXml('rId1', $cx, $cy, 'Certificat de réussite')
            . '</w:drawing>'
            . '</w:r>'
            . '</w:p>'
            . '<w:sectPr>'
            . '<w:pgSz w:w="' . $pageWidthTwips . '" w:h="' . $pageHeightTwips . '" w:orient="landscape"/>'
            . '<w:pgMar w:top="0" w:right="0" w:bottom="0" w:left="0" w:header="0" w:footer="0" w:gutter="0"/>'
            . '<w:cols w:space="0"/>'
            . '</w:sectPr>'
            . '</w:body>'
            . '</w:document>';
    }

    private function inlinePictureXml(string $relationshipId, int $cx, int $cy, string $name): string
    {
        $safeName = $this->safeText($name);

        return '<wp:inline distT="0" distB="0" distL="0" distR="0">'
            . '<wp:extent cx="' . $cx . '" cy="' . $cy . '"/>'
            . '<wp:effectExtent l="0" t="0" r="0" b="0"/>'
            . '<wp:docPr id="1" name="' . $safeName . '"/>'
            . '<wp:cNvGraphicFramePr>'
            . '<a:graphicFrameLocks xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" noChangeAspect="1"/>'
            . '</wp:cNvGraphicFramePr>'
            . '<a:graphic xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main">'
            . '<a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture">'
            . '<pic:pic xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">'
            . '<pic:nvPicPr>'
            . '<pic:cNvPr id="0" name="' . $safeName . '"/>'
            . '<pic:cNvPicPr/>'
            . '</pic:nvPicPr>'
            . '<pic:blipFill>'
            . '<a:blip r:embed="' . $this->safeText($relationshipId) . '"/>'
            . '<a:stretch><a:fillRect/></a:stretch>'
            . '</pic:blipFill>'
            . '<pic:spPr>'
            . '<a:xfrm><a:off x="0" y="0"/><a:ext cx="' . $cx . '" cy="' . $cy . '"/></a:xfrm>'
            . '<a:prstGeom prst="rect"><a:avLst/></a:prstGeom>'
            . '</pic:spPr>'
            . '</pic:pic>'
            . '</a:graphicData>'
            . '</a:graphic>'
            . '</wp:inline>';
    }

    private function documentImageRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/image1.png"/>'
            . '</Relationships>';
    }

    private function twipsToEmu(int $twips): int
    {
        return max(0, $twips) * 635;
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
