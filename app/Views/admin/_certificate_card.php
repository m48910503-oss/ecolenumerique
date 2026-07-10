<?php
$siteLogoUrl = trim((string) ($siteLogoUrl ?? ''));
$siteName = trim((string) ($siteName ?? 'École Numérique'));
$siteTagline = trim((string) ($siteTagline ?? ''));
$siteDescription = trim((string) ($siteDescription ?? ''));
$supportEmail = trim((string) ($supportEmail ?? ''));
$supportPhone = trim((string) ($supportPhone ?? ''));
$supportWhatsapp = trim((string) ($supportWhatsapp ?? ''));
$siteAddress = trim((string) ($siteAddress ?? ''));
$footerText = trim((string) ($footerText ?? ''));
$dgName = trim((string) ($dgName ?? 'Directeur général'));
$dgSignatureUrl = trim((string) ($dgSignatureUrl ?? ''));
$sampleCertificate = is_array($sampleCertificate ?? null) ? $sampleCertificate : [];

$verifyUrl = trim((string) ($sampleCertificate['verifyUrl'] ?? ''));
$supportParts = array_values(array_filter([
    $supportEmail !== '' ? $supportEmail : null,
    $supportPhone !== '' ? $supportPhone : null,
    $supportWhatsapp !== '' ? $supportWhatsapp : null,
], static fn ($value): bool => is_string($value) && trim($value) !== ''));
$supportLine = implode(' · ', $supportParts);
$publicHost = parse_url($verifyUrl, PHP_URL_HOST);
$publicHost = is_string($publicHost) && $publicHost !== '' ? $publicHost : 'ecolenumerique.local';

$certificateSiteName = $siteName;
$certificateBrandSubtitle = $siteTagline !== '' ? $siteTagline : $siteDescription;
$certificateSiteLogoUrl = $siteLogoUrl;
$certificateCode = trim((string) ($sampleCertificate['code'] ?? 'CERT-DEMO-2026-0001'));
$certificateRecipient = trim((string) ($sampleCertificate['studentName'] ?? 'Étudiant'));
$certificateCourseTitle = trim((string) ($sampleCertificate['courseTitle'] ?? 'Cours'));
$certificateCourseDescription = trim((string) ($sampleCertificate['description'] ?? ''));
$certificateExamTitle = trim((string) ($sampleCertificate['examTitle'] ?? 'Examen final'));
$certificateIssuedLabel = trim((string) ($sampleCertificate['issuedLabel'] ?? date('d/m/Y')));
$certificateScoreLabel = trim((string) ($sampleCertificate['score'] ?? ''));
$certificateHoursLabel = trim((string) ($sampleCertificate['hoursLabel'] ?? ($sampleCertificate['hours'] ?? '')));
$certificateTeacherName = trim((string) ($sampleCertificate['teacherName'] ?? 'Formateur'));
$certificateTeacherSignatureUrl = trim((string) ($sampleCertificate['teacherSignatureUrl'] ?? ''));
$certificateDirectorName = $dgName;
$certificateDirectorSignatureUrl = $dgSignatureUrl;
$certificateVerifyUrl = $verifyUrl;
$certificateQrUrl = $verifyUrl;
$certificateSupportLine = $supportLine;
$certificateFooterNote = trim($footerText !== '' ? $footerText : $siteAddress);
$certificateHostLabel = $publicHost;

require __DIR__ . '/../shared/_certificate_premium_model.php';
