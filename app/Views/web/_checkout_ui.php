<?php
declare(strict_types=1);

return [
    'icon' => static function (string $name, string $classes = 'h-5 w-5'): string {
        $classAttr = htmlspecialchars($classes, ENT_QUOTES, 'UTF-8');

        $paths = match ($name) {
            'arrow-left' => '<path d="M15 18 9 12l6-6"></path><path d="M9 12h11"></path>',
            'badge' => '<circle cx="12" cy="12" r="9"></circle><path d="m9.5 12 1.6 1.6 3.4-3.6"></path>',
            'book' => '<path d="M5 4.5A2.5 2.5 0 0 1 7.5 2H20v18H7.5A2.5 2.5 0 0 0 5 22Z"></path><path d="M5 4.5V22"></path><path d="M9 7h7"></path><path d="M9 11h5"></path>',
            'building' => '<path d="M4 21h16"></path><path d="M7 21V8l5-3 5 3v13"></path><path d="M10 11h.01"></path><path d="M14 11h.01"></path><path d="M10 15h.01"></path><path d="M14 15h.01"></path>',
            'cart' => '<circle cx="9" cy="20" r="1.5"></circle><circle cx="17" cy="20" r="1.5"></circle><path d="M3 4h2l2.2 10.2a1 1 0 0 0 1 .8h8.9a1 1 0 0 0 1-.8L20 7H7.2"></path>',
            'check' => '<path d="m5 12 4.2 4.2L19 6.5"></path>',
            'credit-card' => '<rect x="2.5" y="5" width="19" height="14" rx="2.5"></rect><path d="M2.5 10h19"></path><path d="M7 15h3"></path>',
            'gift' => '<path d="M20 12v7a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-7"></path><path d="M21 7H3v5h18V7Z"></path><path d="M12 7v14"></path><path d="M7.5 7A2.5 2.5 0 1 1 10 4.5C10 6.2 12 7 12 7"></path><path d="M16.5 7A2.5 2.5 0 1 0 14 4.5C14 6.2 12 7 12 7"></path>',
            'layers' => '<path d="m12 3 8 4.5-8 4.5-8-4.5L12 3Z"></path><path d="m4 12 8 4.5 8-4.5"></path><path d="m4 16.5 8 4.5 8-4.5"></path>',
            'receipt' => '<path d="M6 3h12v18l-2.5-1.5L13 21l-2.5-1.5L8 21l-2-1.5V3Z"></path><path d="M9 8h6"></path><path d="M9 12h6"></path><path d="M9 16h4"></path>',
            'shield' => '<path d="m12 3 7 3v5c0 4.6-2.9 8.7-7 10-4.1-1.3-7-5.4-7-10V6l7-3Z"></path><path d="m9.5 12 1.8 1.8 3.7-3.7"></path>',
            'spark' => '<path d="m12 3 1.8 4.6L18.5 9l-4.7 1.4L12 15l-1.8-4.6L5.5 9l4.7-1.4L12 3Z"></path><path d="M19 4v2"></path><path d="M20 5h-2"></path>',
            'trash' => '<path d="M4 7h16"></path><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12"></path><path d="M9 7V4h6v3"></path>',
            'wallet' => '<path d="M4 7.5A2.5 2.5 0 0 1 6.5 5H18a2 2 0 0 1 2 2v2H6.5A2.5 2.5 0 0 0 4 11.5v6A2.5 2.5 0 0 0 6.5 20H19a2 2 0 0 0 2-2V9"></path><path d="M17 14h4"></path>',
            default => '<circle cx="12" cy="12" r="9"></circle>',
        };

        return sprintf(
            '<svg class="%s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">%s</svg>',
            $classAttr,
            $paths 
        );
    },
];
 