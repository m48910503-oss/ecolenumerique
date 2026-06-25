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

$studentName = trim((string) ($sampleCertificate['studentName'] ?? 'Aïcha Mensah'));
$courseTitle = trim((string) ($sampleCertificate['courseTitle'] ?? 'Développement Web 2026'));
$examTitle = trim((string) ($sampleCertificate['examTitle'] ?? 'Évaluation finale'));
$teacherName = trim((string) ($sampleCertificate['teacherName'] ?? 'Michaël Ouwade'));
$score = trim((string) ($sampleCertificate['score'] ?? '94% (Excellent)'));
$hours = trim((string) ($sampleCertificate['hours'] ?? '42 h'));
$description = trim((string) ($sampleCertificate['description'] ?? 'Une formation intensive couvrant les compétences clés du parcours et validée par une évaluation finale.'));
$code = trim((string) ($sampleCertificate['code'] ?? 'CERT-DEMO-2026-0001'));
$issuedLabel = trim((string) ($sampleCertificate['issuedLabel'] ?? date('d/m/Y')));
$verifyUrl = trim((string) ($sampleCertificate['verifyUrl'] ?? site_url('certificate?code=' . urlencode($code))));
$publicHost = parse_url($verifyUrl, PHP_URL_HOST);
$publicHost = is_string($publicHost) && $publicHost !== '' ? $publicHost : 'ecolenumerique.local';
$adminSupportLine = trim(implode(' · ', array_values(array_filter([
    $supportEmail !== '' ? $supportEmail : null,
    $supportPhone !== '' ? $supportPhone : null,
    $supportWhatsapp !== '' ? $supportWhatsapp : null,
]))));
$adminFooterNote = trim($footerText !== '' ? $footerText : $siteAddress);
?>
<article id="certificate-container" class="admin-certificate-card admin-certificate-card--landscape">
    <span class="admin-certificate-corner admin-certificate-corner--tl" aria-hidden="true"></span>
    <span class="admin-certificate-corner admin-certificate-corner--tr" aria-hidden="true"></span>
    <span class="admin-certificate-corner admin-certificate-corner--bl" aria-hidden="true"></span>
    <span class="admin-certificate-corner admin-certificate-corner--br" aria-hidden="true"></span>

    <div class="admin-certificate-bg-icon" aria-hidden="true">
        <i class="fas fa-graduation-cap"></i>
    </div>

    <div class="admin-certificate-content">
        <header class="admin-certificate-top">
            <div class="admin-certificate-brand-main">
                <span class="admin-certificate-brand-icon">
                    <?php if ($siteLogoUrl !== ''): ?>
                        <img src="<?php echo e($siteLogoUrl); ?>" alt="Logo <?php echo e($siteName); ?>">
                    <?php else: ?>
                        <i class="fas fa-graduation-cap" aria-hidden="true"></i>
                    <?php endif; ?>
                </span>
                <span class="flex flex-col min-w-0">
                    <span class="admin-certificate-brand-name">
                        <?php echo e($siteName); ?>
                    </span>
                    <?php if ($siteTagline !== '' || $siteDescription !== ''): ?>
                        <span class="mt-1 text-xs font-medium text-slate-500 dark:text-slate-300">
                            <?php echo e($siteTagline !== '' ? $siteTagline : $siteDescription); ?>
                        </span>
                    <?php endif; ?>
                </span>
            </div>
            <div class="admin-certificate-number">
                <p>Certificat N°</p>
                <strong><?php echo e($code); ?></strong>
            </div>
        </header>

        <section class="admin-certificate-title-block">
            <span class="admin-certificate-congrats">
                <i class="fas fa-crown" aria-hidden="true"></i>
                Félicitations
            </span>
            <h1>Certificat de Réussite</h1>
            <div class="admin-certificate-title-rule" aria-hidden="true">
                <span></span>
                <i class="fas fa-star"></i>
                <span></span>
            </div>
            <p>Ce document officiel est décerné à</p>
        </section>

        <section class="admin-certificate-recipient">
            <h2><?php echo e($studentName); ?></h2>
            <span></span>
        </section>

        <p class="admin-certificate-course-intro">pour avoir complété avec succès la formation</p>
        <h3 class="admin-certificate-course-title"><?php echo e($courseTitle); ?></h3>
        <p class="admin-certificate-course-description"><?php echo e($description); ?></p>

        <section class="admin-certificate-meta">
            <span>
                <i class="fas fa-calendar-check" aria-hidden="true"></i>
                Obtenu le <strong><?php echo e($issuedLabel); ?></strong>
            </span>
            <span>
                <i class="fas fa-trophy" aria-hidden="true"></i>
                Note : <strong><?php echo e($score); ?></strong>
            </span>
            <span>
                <i class="fas fa-hourglass-half" aria-hidden="true"></i>
                Heures validées : <strong><?php echo e($hours); ?></strong>
            </span>
        </section>

        <section class="admin-certificate-seal-row">
            <div class="admin-certificate-sign-block">
                <p class="admin-certificate-signature-text"><?php echo e($teacherName); ?></p>
                <span class="admin-certificate-sign-line"></span>
                <small>Instructeur principal</small>
            </div>

            <div class="admin-certificate-seal">
                <i class="fas fa-certificate" aria-hidden="true"></i>
                <strong>Certifié</strong>
                <span><?php echo e($siteName); ?></span>
                <i class="fas fa-check-circle" aria-hidden="true"></i>
            </div>

            <div class="admin-certificate-sign-block">
                <div class="admin-certificate-signature-media">
                    <?php if ($dgSignatureUrl !== ''): ?>
                        <img src="<?php echo e($dgSignatureUrl); ?>" alt="Signature <?php echo e($dgName); ?>">
                    <?php else: ?>
                        <p class="admin-certificate-signature-text"><?php echo e($dgName); ?></p>
                    <?php endif; ?>
                </div>
                <span class="admin-certificate-sign-line"></span>
                <small>Direction générale</small>
            </div>
        </section>

        <footer class="admin-certificate-footer">
            <div class="admin-certificate-verify">
                <span class="admin-certificate-qr" data-certificate-qr="<?php echo e($verifyUrl); ?>" aria-label="QR de vérification">
                    <i class="fas fa-qrcode" aria-hidden="true"></i>
                </span>
                <div>
                    <p>Vérification en ligne :</p>
                    <strong><?php echo e($verifyUrl); ?></strong>
                    <?php if ($adminSupportLine !== ''): ?>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            Support : <?php echo e($adminSupportLine); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="admin-certificate-site">
                <p><i class="fas fa-globe" aria-hidden="true"></i> <?php echo e($publicHost); ?></p>
                <p><i class="far fa-copyright" aria-hidden="true"></i> <?php echo e(date('Y')); ?> <?php echo e($siteName); ?></p>
                <?php if ($adminFooterNote !== ''): ?>
                    <p class="text-xs text-slate-500 dark:text-slate-400"><?php echo e($adminFooterNote); ?></p>
                <?php endif; ?>
            </div>
        </footer>
    </div>
</article>
