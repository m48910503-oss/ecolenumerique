<?php
$certificateSiteName = trim((string) ($certificateSiteName ?? 'École Numérique'));
$certificateSiteLogoUrl = trim((string) ($certificateSiteLogoUrl ?? ''));
$certificateCode = trim((string) ($certificateCode ?? ''));
$certificateRecipient = trim((string) ($certificateRecipient ?? 'Étudiant'));
$certificateCourseTitle = trim((string) ($certificateCourseTitle ?? 'Cours'));
$certificateCourseDescription = trim((string) ($certificateCourseDescription ?? ''));
$certificateIssuedLabel = trim((string) ($certificateIssuedLabel ?? 'n/c'));
$certificateScoreLabel = trim((string) ($certificateScoreLabel ?? ''));
$certificateHoursLabel = trim((string) ($certificateHoursLabel ?? ''));
$certificateTeacherName = trim((string) ($certificateTeacherName ?? 'Formateur'));
$certificateTeacherSignatureUrl = trim((string) ($certificateTeacherSignatureUrl ?? ''));
$certificateDirectorName = trim((string) ($certificateDirectorName ?? 'Direction générale'));
$certificateDirectorSignatureUrl = trim((string) ($certificateDirectorSignatureUrl ?? ''));
$certificateVerifyUrl = trim((string) ($certificateVerifyUrl ?? ''));
$certificateQrUrl = trim((string) ($certificateQrUrl ?? $certificateVerifyUrl));
$certificateHostLabel = trim((string) ($certificateHostLabel ?? 'ecolenumerique.local'));

if ($certificateSiteName === '') {
    $certificateSiteName = 'École Numérique';
}
if ($certificateRecipient === '') {
    $certificateRecipient = 'Étudiant';
}
if ($certificateCourseTitle === '') {
    $certificateCourseTitle = 'Cours';
}
if ($certificateCourseDescription === '') {
    $certificateCourseDescription = 'Formation validée par la réalisation de projets pratiques et une évaluation finale.';
}
if ($certificateTeacherName === '') {
    $certificateTeacherName = 'Formateur';
}
if ($certificateDirectorName === '') {
    $certificateDirectorName = 'Direction générale';
}
if ($certificateIssuedLabel === '') {
    $certificateIssuedLabel = 'n/c';
}

$brandParts = preg_split('/\s+/u', $certificateSiteName, 2);
$brandFirstPart = trim((string) ($brandParts[0] ?? $certificateSiteName));
$brandSecondPart = trim((string) ($brandParts[1] ?? ''));
?>
<article id="certificate-container" class="certificate-design" aria-label="Certificat de réussite">
    <span class="certificate-design__top-bar" aria-hidden="true"></span>
    <span class="certificate-design__bottom-bar" aria-hidden="true"></span>

    <span class="certificate-design__watermark" aria-hidden="true">
        <i class="fas fa-graduation-cap"></i>
    </span>

    <div class="certificate-design__content">
        <header class="certificate-design__header">
            <div class="certificate-design__brand">
                <span class="certificate-design__logo-icon">
                    <?php if ($certificateSiteLogoUrl !== ''): ?>
                        <img src="<?php echo e($certificateSiteLogoUrl); ?>" alt="Logo <?php echo e($certificateSiteName); ?>">
                    <?php else: ?>
                        <i class="fas fa-graduation-cap" aria-hidden="true"></i>
                    <?php endif; ?>
                </span>
                <span class="certificate-design__brand-name">
                    <?php echo e($brandFirstPart); ?><?php if ($brandSecondPart !== ''): ?><strong><?php echo e($brandSecondPart); ?></strong><?php endif; ?>
                </span>
            </div>

            <div class="certificate-design__number">
                <span>Certificat N°</span>
                <strong><?php echo e($certificateCode !== '' ? $certificateCode : 'n/c'); ?></strong>
            </div>
        </header>

        <section class="certificate-design__title-section">
            <div class="certificate-design__badge">
                <i class="fas fa-crown" aria-hidden="true"></i>
                Félicitations
            </div>
            <h1>Certificat de Réussite</h1>
            <div class="certificate-design__divider" aria-hidden="true">
                <span></span>
                <i class="fas fa-star"></i>
                <span></span>
            </div>
            <p>Ce document officiel est décerné à</p>
        </section>

        <section class="certificate-design__recipient">
            <h2><?php echo e($certificateRecipient); ?></h2>
            <span aria-hidden="true"></span>
        </section>

        <p class="certificate-design__course-intro">pour avoir complété avec succès la formation</p>
        <h3 class="certificate-design__course-name"><?php echo e($certificateCourseTitle); ?></h3>
        <p class="certificate-design__description"><?php echo e($certificateCourseDescription); ?></p>

        <section class="certificate-design__meta" aria-label="Informations du certificat">
            <span>
                <i class="fas fa-calendar-check" aria-hidden="true"></i>
                Obtenu le <strong><?php echo e($certificateIssuedLabel); ?></strong>
            </span>
            <?php if ($certificateScoreLabel !== ''): ?>
                <span>
                    <i class="fas fa-trophy" aria-hidden="true"></i>
                    Note : <strong><?php echo e($certificateScoreLabel); ?></strong>
                </span>
            <?php endif; ?>
            <?php if ($certificateHoursLabel !== ''): ?>
                <span>
                    <i class="fas fa-hourglass-half" aria-hidden="true"></i>
                    Heures validées : <strong><?php echo e($certificateHoursLabel); ?></strong>
                </span>
            <?php endif; ?>
        </section>

        <section class="certificate-design__signatures">
            <div class="certificate-design__signature">
                <div class="certificate-design__signature-value">
                    <?php if ($certificateTeacherSignatureUrl !== ''): ?>
                        <img src="<?php echo e($certificateTeacherSignatureUrl); ?>" alt="Signature <?php echo e($certificateTeacherName); ?>">
                    <?php else: ?>
                        <span><?php echo e($certificateTeacherName); ?></span>
                    <?php endif; ?>
                </div>
                <span class="certificate-design__signature-line" aria-hidden="true"></span>
                <strong class="certificate-design__signer-name"><?php echo e($certificateTeacherName); ?></strong>
                <small class="certificate-design__signer-role">Instructeur principal</small>
            </div>

            <div class="certificate-design__seal" aria-label="Certificat certifié">
                <i class="fas fa-certificate" aria-hidden="true"></i>
                <strong>Certifié</strong>
                <span><?php echo e($certificateSiteName); ?></span>
                <i class="fas fa-check-circle" aria-hidden="true"></i>
            </div>

            <div class="certificate-design__signature">
                <div class="certificate-design__signature-value">
                    <?php if ($certificateDirectorSignatureUrl !== ''): ?>
                        <img src="<?php echo e($certificateDirectorSignatureUrl); ?>" alt="Signature <?php echo e($certificateDirectorName); ?>">
                    <?php else: ?>
                        <span><?php echo e($certificateDirectorName); ?></span>
                    <?php endif; ?>
                </div>
                <span class="certificate-design__signature-line" aria-hidden="true"></span>
                <strong class="certificate-design__signer-name"><?php echo e($certificateDirectorName); ?></strong>
                <small class="certificate-design__signer-role">Directeur général</small>
            </div>
        </section>

        <footer class="certificate-design__footer">
            <div class="certificate-design__verify">
                <span class="certificate-design__qr" data-certificate-qr="<?php echo e($certificateQrUrl); ?>" aria-label="QR de vérification">
                    <i class="fas fa-qrcode" aria-hidden="true"></i>
                </span>
                <span>
                    <strong>Vérification en ligne :</strong>
                    <a href="<?php echo e($certificateVerifyUrl !== '' ? $certificateVerifyUrl : '#'); ?>">
                        <?php echo e($certificateVerifyUrl !== '' ? $certificateVerifyUrl : 'Lien indisponible'); ?>
                    </a>
                </span>
            </div>

            <div class="certificate-design__site">
                <span><i class="fas fa-globe" aria-hidden="true"></i> <?php echo e($certificateHostLabel); ?></span>
                <span><i class="far fa-copyright" aria-hidden="true"></i> <?php echo e(date('Y')); ?> <?php echo e($certificateSiteName); ?></span>
            </div>
        </footer>
    </div>
</article>
