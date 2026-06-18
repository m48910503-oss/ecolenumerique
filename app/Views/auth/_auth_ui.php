<?php
declare(strict_types=1);

return [
    'icon' => static function (string $name, string $classes = 'h-5 w-5'): string {
        $classAttr = htmlspecialchars($classes, ENT_QUOTES, 'UTF-8');
        $iconAttr = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');

        if ($name === 'google') {
            return sprintf(
                '<svg class="%s" viewBox="0 0 24 24" aria-hidden="true"><path fill="#4285F4" d="M21.8 12.2c0-.7-.1-1.3-.2-1.9H12v3.7h5.5a4.7 4.7 0 0 1-2 3.1v2.6h3.2c1.9-1.8 3.1-4.4 3.1-7.5Z"></path><path fill="#34A853" d="M12 22c2.7 0 4.9-.9 6.6-2.4l-3.2-2.6c-.9.6-2.1 1-3.4 1-2.6 0-4.8-1.8-5.6-4.2H3.1v2.7A10 10 0 0 0 12 22Z"></path><path fill="#FBBC04" d="M6.4 13.8A6 6 0 0 1 6 12c0-.6.1-1.2.3-1.8V7.5H3.1A10 10 0 0 0 2 12c0 1.6.4 3.2 1.1 4.5l3.3-2.7Z"></path><path fill="#EA4335" d="M12 6c1.5 0 2.8.5 3.8 1.5l2.9-2.9A10 10 0 0 0 3.1 7.5l3.3 2.7C7.2 7.8 9.4 6 12 6Z"></path></svg>',
                $classAttr
            ); 
        } 
$paths = match ($name) {
            'spark' => '<path d="m12 3 1.8 4.6L18.5 9l-4.7 1.4L12 15l-1.8-4.6L5.5 9l4.7-1.4L12 3Z"></path><path d="M19 4v2"></path><path d="M20 5h-2"></path>',
            'mail' => '<rect x="3" y="5" width="18" height="14" rx="3"></rect><path d="m4 7 8 6 8-6"></path>',
            'lock' => '<rect x="5" y="11" width="14" height="10" rx="2"></rect><path d="M8 11V8a4 4 0 1 1 8 0v3"></path>',
            'shield' => '<path d="m12 3 7 3v5c0 4.6-2.9 8.7-7 10-4.1-1.3-7-5.4-7-10V6l7-3Z"></path><path d="m9.5 12 1.8 1.8 3.7-3.7"></path>',
            'book' => '<path d="M5 4.5A2.5 2.5 0 0 1 7.5 2H20v18H7.5A2.5 2.5 0 0 0 5 22Z"></path><path d="M5 4.5V22"></path><path d="M9 7h7"></path><path d="M9 11h5"></path>',
            'bell' => '<path d="M15 17H5.8a1 1 0 0 1-.8-1.6l1.1-1.5A5 5 0 0 0 7 11V9a5 5 0 1 1 10 0v2a5 5 0 0 0 .9 2.9l1.1 1.5A1 1 0 0 1 18.2 17H15"></path><path d="M9.5 19a2.5 2.5 0 0 0 5 0"></path>',
            'user' => '<circle cx="12" cy="8" r="4"></circle><path d="M4 20a8 8 0 0 1 16 0"></path>',
            'building' => '<path d="M4 21h16"></path><path d="M7 21V8l5-3 5 3v13"></path><path d="M10 11h.01"></path><path d="M14 11h.01"></path><path d="M10 15h.01"></path><path d="M14 15h.01"></path>',
            'gift' => '<path d="M20 12v7a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-7"></path><path d="M21 7H3v5h18V7Z"></path><path d="M12 7v14"></path><path d="M7.5 7A2.5 2.5 0 1 1 10 4.5C10 6.2 12 7 12 7"></path><path d="M16.5 7A2.5 2.5 0 1 0 14 4.5C14 6.2 12 7 12 7"></path>',
            'badge' => '<circle cx="12" cy="12" r="9"></circle><path d="m9.5 12 1.6 1.6 3.4-3.6"></path>',
            'arrow-right' => '<path d="M5 12h14"></path><path d="m13 6 6 6-6 6"></path>',
            'arrow-left' => '<path d="M19 12H5"></path><path d="m11 18-6-6 6-6"></path>',
            'briefcase' => '<rect x="3" y="7" width="18" height="13" rx="2"></rect><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><path d="M3 12h18"></path>',
            default => '<circle cx="12" cy="12" r="9"></circle>',
        };

        return sprintf(
            '<svg class="%s" data-app-icon="%s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">%s</svg>',
            $classAttr,
            $iconAttr,
            $paths
        );
    },
];
