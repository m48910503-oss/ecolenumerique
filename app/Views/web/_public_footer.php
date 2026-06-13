<footer class="mk-footer">
    <div class="mk-shell">
        <div class="mk-footer__grid">
            <div class="mk-footer__brand">
                <div class="mk-brand__title">École Numérique</div>
                <p class="mk-footer__lead">Une plateforme éducative claire et structurée.</p>
                <p class="mk-footer__note"><?php echo e($publicFooterNote); ?></p>
            </div>

            <?php foreach ($publicFooterColumns as $column): ?>
                <nav class="mk-footer__column" aria-label="<?php echo e((string) ($column['title'] ?? 'Navigation')); ?>">
                    <h2 class="mk-footer__title"><?php echo e((string) ($column['title'] ?? 'Navigation')); ?></h2>
                    <ul class="mk-footer__links">
                        <?php foreach ((array) ($column['links'] ?? []) as $link): ?>
                            <?php $isPost = !empty($link['post']); ?>
                            <li>
                                <?php if ($isPost): ?>
                                    <form method="post" action="<?php echo e((string) ($link['href'] ?? '#')); ?>">
                                        <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                        <button type="submit" class="mk-footer__link-button">
                                            <?php $linkIcon = trim((string) ($link['icon'] ?? '')); ?>
                                            <?php if ($linkIcon !== ''): ?><span class="app-icon" aria-hidden="true"><?php echo function_exists('dashboard_icon_svg') ? dashboard_icon_svg($linkIcon) : ''; ?></span><?php endif; ?>
                                            <span><?php echo e((string) ($link['label'] ?? 'Lien')); ?></span>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <a href="<?php echo e((string) ($link['href'] ?? '#')); ?>">
                                        <?php $linkIcon = trim((string) ($link['icon'] ?? '')); ?>
                                        <?php if ($linkIcon !== ''): ?><span class="app-icon" aria-hidden="true"><?php echo function_exists('dashboard_icon_svg') ? dashboard_icon_svg($linkIcon) : ''; ?></span><?php endif; ?>
                                        <span><?php echo e((string) ($link['label'] ?? 'Lien')); ?></span>
                                    </a>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </nav>
            <?php endforeach; ?>
        </div>

        <div class="mk-footer__bottom">
            <p><?php echo e($footerText !== '' ? $footerText : ('© ' . date('Y') . ' École Numérique.')); ?></p>
            <div class="mk-footer__bottom-links">
                <a href="<?php echo e($privacyUrl); ?>">Confidentialité</a>
                <a href="<?php echo e($cguUrl); ?>">CGU</a>
                <a href="<?php echo e($supportUrl); ?>">Support</a>
            </div>
        </div>
    </div>
</footer>
