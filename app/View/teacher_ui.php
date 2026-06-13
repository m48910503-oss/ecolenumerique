<?php
declare(strict_types=1);

if (!function_exists('teacher_ui_escape')) {
    /**
     * @param mixed $value
     */
    function teacher_ui_escape($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('teacher_ui_icon')) {
    function teacher_ui_icon(string $name): string
    {
        return function_exists('dashboard_icon_svg') ? (string) dashboard_icon_svg($name) : '';
    }
}

if (!function_exists('teacher_ui_status_tone')) {
    function teacher_ui_status_tone(string $label): string
    {
        $value = strtolower(trim($label));

        if ($value === '') {
            return 'neutral';
        }
        if (
            str_contains($value, 'urgent')
            || str_contains($value, 'alerte')
            || str_contains($value, 'retard')
            || str_contains($value, 'masqu')
            || str_contains($value, 'rejet')
        ) {
            return 'danger';
        }
        if (
            str_contains($value, 'attente')
            || str_contains($value, 'planifi')
            || str_contains($value, 'surveiller')
            || str_contains($value, 'pending')
        ) {
            return 'warning';
        }
        if (
            str_contains($value, 'actif')
            || str_contains($value, 'valid')
            || str_contains($value, 'visible')
            || str_contains($value, 'publ')
            || str_contains($value, 'confirm')
            || str_contains($value, 'termin')
            || str_contains($value, 'ok')
        ) {
            return 'success';
        }
        if (
            str_contains($value, 'analyse')
            || str_contains($value, 'qualit')
            || str_contains($value, 'pilotage')
            || str_contains($value, 'info')
        ) {
            return 'info';
        }

        return 'neutral';
    }
}

if (!function_exists('teacher_ui_status_badge')) {
    function teacher_ui_status_badge(string $label, ?string $tone = null, string $extraClass = ''): string
    {
        $resolvedTone = $tone !== null && $tone !== '' ? $tone : teacher_ui_status_tone($label);
        $classes = 'teacher-status-badge teacher-status-badge--' . preg_replace('/[^a-z0-9_-]/i', '', $resolvedTone);
        if ($extraClass !== '') {
            $classes .= ' ' . trim($extraClass);
        }

        return '<span class="' . teacher_ui_escape($classes) . '">' . teacher_ui_escape($label) . '</span>';
    }
}

if (!function_exists('teacher_ui_render_feedback')) {
    /**
     * @param array<int,string>|string $messages
     */
    function teacher_ui_render_feedback(string $tone, string $title, $messages): string
    {
        $items = is_array($messages) ? $messages : [trim((string) $messages)];
        $items = array_values(array_filter(array_map(static fn ($item): string => trim((string) $item), $items), static fn (string $item): bool => $item !== ''));
        if ($items === []) {
            return '';
        }

        $iconByTone = [
            'success' => 'check-circle',
            'warning' => 'alert-circle',
            'error' => 'alert-triangle',
            'danger' => 'alert-triangle',
            'info' => 'info',
        ];
        $resolvedTone = in_array($tone, ['success', 'warning', 'error', 'danger', 'info'], true) ? $tone : 'info';
        $icon = $iconByTone[$resolvedTone] ?? 'info';

        ob_start();
        ?>
        <section class="teacher-feedback teacher-feedback--<?php echo teacher_ui_escape($resolvedTone); ?>" role="<?php echo $resolvedTone === 'success' ? 'status' : 'alert'; ?>">
            <div class="teacher-feedback__icon app-icon" aria-hidden="true"><?php echo teacher_ui_icon($icon); ?></div>
            <div class="teacher-feedback__body">
                <p class="teacher-feedback__title"><?php echo teacher_ui_escape($title); ?></p>
                <?php foreach ($items as $item): ?>
                    <p class="teacher-feedback__line"><?php echo teacher_ui_escape($item); ?></p>
                <?php endforeach; ?>
            </div>
        </section>
        <?php
        return trim((string) ob_get_clean());
    }
}

if (!function_exists('teacher_ui_render_metric_strip')) {
    /**
     * @param array<int,array<string,mixed>> $metrics
     */
    function teacher_ui_render_metric_strip(array $metrics, string $class = ''): string
    {
        if ($metrics === []) {
            return '';
        }

        ob_start();
        ?>
        <div class="teacher-metric-strip <?php echo teacher_ui_escape(trim($class)); ?>">
            <?php foreach ($metrics as $metric): ?>
                <?php
                $label = trim((string) ($metric['label'] ?? 'Repère'));
                $value = trim((string) ($metric['value'] ?? '0'));
                $meta = trim((string) ($metric['meta'] ?? ''));
                $icon = trim((string) ($metric['icon'] ?? ''));
                $tone = trim((string) ($metric['tone'] ?? 'neutral'));
                ?>
                <article class="teacher-metric-card teacher-metric-card--<?php echo teacher_ui_escape($tone !== '' ? $tone : 'neutral'); ?>">
                    <p class="teacher-metric-card__label">
                        <?php if ($icon !== ''): ?>
                            <span class="teacher-metric-card__icon app-icon" aria-hidden="true"><?php echo teacher_ui_icon($icon); ?></span>
                        <?php endif; ?>
                        <span><?php echo teacher_ui_escape($label); ?></span>
                    </p>
                    <p class="teacher-metric-card__value"><?php echo teacher_ui_escape($value); ?></p>
                    <?php if ($meta !== ''): ?>
                        <p class="teacher-metric-card__meta"><?php echo teacher_ui_escape($meta); ?></p>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
        <?php
        return trim((string) ob_get_clean());
    }
}

if (!function_exists('teacher_ui_render_actions')) {
    /**
     * @param array<int,array<string,mixed>> $actions
     */
    function teacher_ui_render_actions(array $actions, string $class = ''): string
    {
        if ($actions === []) {
            return '';
        }

        ob_start();
        ?>
        <div class="teacher-page-header__actions <?php echo teacher_ui_escape(trim($class)); ?>">
            <?php foreach ($actions as $action): ?>
                <?php
                $label = trim((string) ($action['label'] ?? 'Action'));
                $url = trim((string) ($action['url'] ?? '#'));
                $tone = trim((string) ($action['tone'] ?? 'secondary'));
                $icon = trim((string) ($action['icon'] ?? ''));
                $attrs = trim((string) ($action['attrs'] ?? ''));
                ?>
                <a href="<?php echo teacher_ui_escape($url); ?>" class="teacher-action-btn teacher-action-btn--<?php echo teacher_ui_escape($tone !== '' ? $tone : 'secondary'); ?>"<?php echo $attrs !== '' ? ' ' . $attrs : ''; ?>>
                    <?php if ($icon !== ''): ?>
                        <span class="app-icon" aria-hidden="true"><?php echo teacher_ui_icon($icon); ?></span>
                    <?php endif; ?>
                    <span><?php echo teacher_ui_escape($label); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
        <?php
        return trim((string) ob_get_clean());
    }
}

if (!function_exists('teacher_ui_render_header')) {
    /**
     * @param array<string,mixed> $config
     */
    function teacher_ui_render_header(array $config): string
    {
        $eyebrow = trim((string) ($config['eyebrow'] ?? ''));
        $title = trim((string) ($config['title'] ?? ''));
        $summary = trim((string) ($config['summary'] ?? ''));
        $icon = trim((string) ($config['icon'] ?? ''));
        $variant = trim((string) ($config['variant'] ?? 'default'));
        $chips = is_array($config['chips'] ?? null) ? $config['chips'] : [];
        $actions = is_array($config['actions'] ?? null) ? $config['actions'] : [];
        $metrics = is_array($config['metrics'] ?? null) ? $config['metrics'] : [];
        $aside = trim((string) ($config['aside'] ?? ''));
        $extraClass = trim((string) ($config['class'] ?? ''));

        ob_start();
        ?>
        <section class="teacher-page-header teacher-page-header--<?php echo teacher_ui_escape($variant !== '' ? $variant : 'default'); ?><?php echo $extraClass !== '' ? ' ' . teacher_ui_escape($extraClass) : ''; ?>">
            <div class="teacher-page-header__main">
                <div class="teacher-page-header__copy">
                    <?php if ($icon !== ''): ?>
                        <div class="teacher-page-header__icon app-icon" aria-hidden="true"><?php echo teacher_ui_icon($icon); ?></div>
                    <?php endif; ?>
                    <div class="teacher-page-header__copy-body">
                        <?php if ($eyebrow !== ''): ?>
                            <p class="teacher-page-header__eyebrow"><?php echo teacher_ui_escape($eyebrow); ?></p>
                        <?php endif; ?>
                        <?php if ($title !== ''): ?>
                            <h1 class="teacher-page-header__title"><?php echo teacher_ui_escape($title); ?></h1>
                        <?php endif; ?>
                        <?php if ($summary !== ''): ?>
                            <p class="teacher-page-header__summary"><?php echo teacher_ui_escape($summary); ?></p>
                        <?php endif; ?>
                        <?php if ($chips !== []): ?>
                            <div class="teacher-page-header__chips">
                                <?php foreach ($chips as $chip): ?>
                                    <?php if (is_array($chip)): ?>
                                        <?php echo teacher_ui_status_badge((string) ($chip['label'] ?? ''), (string) ($chip['tone'] ?? 'neutral')); ?>
                                    <?php else: ?>
                                        <?php echo teacher_ui_status_badge((string) $chip); ?>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($actions !== [] || $aside !== ''): ?>
                    <div class="teacher-page-header__aside">
                        <?php if ($actions !== []): ?>
                            <?php echo teacher_ui_render_actions($actions); ?>
                        <?php endif; ?>
                        <?php if ($aside !== ''): ?>
                            <div class="teacher-page-header__note"><?php echo $aside; ?></div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php if ($metrics !== []): ?>
                <?php echo teacher_ui_render_metric_strip($metrics); ?>
            <?php endif; ?>
        </section>
        <?php
        return trim((string) ob_get_clean());
    }
}

if (!function_exists('teacher_ui_render_empty_state')) {
    /**
     * @param array<string,mixed> $config
     */
    function teacher_ui_render_empty_state(array $config): string
    {
        $title = trim((string) ($config['title'] ?? 'Aucune donnée'));
        $body = trim((string) ($config['body'] ?? ''));
        $icon = trim((string) ($config['icon'] ?? 'inbox'));
        $tone = trim((string) ($config['tone'] ?? 'neutral'));
        $actions = is_array($config['actions'] ?? null) ? $config['actions'] : [];
        $extraClass = trim((string) ($config['class'] ?? ''));

        ob_start();
        ?>
        <section class="teacher-empty-state teacher-empty-state--<?php echo teacher_ui_escape($tone !== '' ? $tone : 'neutral'); ?><?php echo $extraClass !== '' ? ' ' . teacher_ui_escape($extraClass) : ''; ?>">
            <div class="teacher-empty-state__icon app-icon" aria-hidden="true"><?php echo teacher_ui_icon($icon !== '' ? $icon : 'inbox'); ?></div>
            <div class="teacher-empty-state__body">
                <h2 class="teacher-empty-state__title"><?php echo teacher_ui_escape($title); ?></h2>
                <?php if ($body !== ''): ?>
                    <p class="teacher-empty-state__text"><?php echo teacher_ui_escape($body); ?></p>
                <?php endif; ?>
            </div>
            <?php if ($actions !== []): ?>
                <?php echo teacher_ui_render_actions($actions, 'teacher-empty-state__actions'); ?>
            <?php endif; ?>
        </section>
        <?php
        return trim((string) ob_get_clean());
    }
}
