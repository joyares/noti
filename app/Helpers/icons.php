<?php

declare(strict_types=1);

/**
 * Inline stroke SVG icons. All use currentColor so they inherit text color.
 * Notebook icon keys match notebooks.icon in the DB.
 */
function icon(string $key, int $size = 16, string $class = ''): string
{
    static $paths = [
        // Fixed-notebook icons
        'bookmark' => '<path d="M6 3.5h12v17l-6-4.5-6 4.5z"/>',
        'key'      => '<circle cx="8" cy="8" r="4"/><path d="M11 11l8 8M16 16l2-2M18.5 18.5l2-2"/>',
        'link'     => '<path d="M9.5 14.5l5-5M8 12l-2.5 2.5a3.5 3.5 0 0 0 5 5L13 17M16 12l2.5-2.5a3.5 3.5 0 0 0-5-5L11 7"/>',
        'at'       => '<circle cx="12" cy="12" r="4"/><path d="M16 12v1.5a2.5 2.5 0 0 0 5 0V12a9 9 0 1 0-3.5 7.1"/>',
        'terminal' => '<path d="M5 7l5 5-5 5M12 17h7"/>',
        'pencil'   => '<path d="M4 20l1-4L16.5 4.5a2.1 2.1 0 0 1 3 3L8 19l-4 1zM14.5 6.5l3 3"/>',
        'checkbox' => '<rect x="4" y="4" width="16" height="16" rx="3"/><path d="M8.5 12.5l2.5 2.5 4.5-5"/>',
        'bulb'     => '<path d="M9 18h6M10 21h4M12 3a6 6 0 0 1 4 10.5c-.8.7-1 1.5-1 2.5h-6c0-1-.2-1.8-1-2.5A6 6 0 0 1 12 3z"/>',
        'info'     => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 7.5v.5"/>',
        'star'     => '<path d="M12 3.5l2.6 5.3 5.9.9-4.2 4.1 1 5.8-5.3-2.8-5.3 2.8 1-5.8L3.5 9.7l5.9-.9z"/>',
        'person'   => '<circle cx="12" cy="8" r="4"/><path d="M4.5 20.5a7.5 7.5 0 0 1 15 0"/>',
        'plane'    => '<path d="M21 3L3 10.5l6.5 2.5L12 20l3-7.5zM21 3l-11.5 10"/>',
        'box'      => '<path d="M3.5 7.5L12 3l8.5 4.5v9L12 21l-8.5-4.5zM3.5 7.5L12 12l8.5-4.5M12 12v9"/>',
        'sun'      => '<circle cx="12" cy="12" r="4.5"/><path d="M12 2.5v2.5M12 19v2.5M2.5 12H5M19 12h2.5M5 5l1.8 1.8M17.2 17.2L19 19M19 5l-1.8 1.8M6.8 17.2L5 19"/>',
        'bag'      => '<path d="M5 8h14l-1 12.5H6zM8.5 10.5V7a3.5 3.5 0 0 1 7 0v3.5"/>',
        'shirt'    => '<path d="M8.5 3.5L4 7l2 3 2-1.2V20.5h8V8.8L18 10l2-3-4.5-3.5a3.5 3.5 0 0 1-7 0z"/>',
        'document' => '<path d="M6 3h8l4 4v14H6zM14 3v4h4M9 12h6M9 15.5h6M9 8.5h2"/>',
        'pen'      => '<path d="M4 20c4-.5 5.5-2 6-4l8.5-8.5a2 2 0 0 0-3-3L7 13c-2 .5-3.5 2-3 7zM12.5 6.5l3 3"/>',
        'book'     => '<path d="M5 4.5A2.5 2.5 0 0 1 7.5 2H19v17.5H7.5A2.5 2.5 0 0 0 5 22zM5 19.5v-15M19 19.5H7.5A2.5 2.5 0 0 0 5 22"/>',
        // UI icons
        'home'     => '<path d="M4 11l8-7 8 7M6 9.5V20h12V9.5"/>',
        'notes'    => '<path d="M7 3h10a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2zM9 8h6M9 12h6M9 16h4"/>',
        'tag'      => '<path d="M3.5 11.5v-8h8l9 9-8 8zM8 7.5v.5"/>',
        'trash'    => '<path d="M4.5 6.5h15M9.5 6V4h5v2M6.5 6.5l1 14h9l1-14M10 10.5v6M14 10.5v6"/>',
        'plus'     => '<path d="M12 5v14M5 12h14"/>',
        'search'   => '<circle cx="11" cy="11" r="6.5"/><path d="M16 16l4.5 4.5"/>',
        'dots'     => '<circle cx="5" cy="12" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/>',
        'share'    => '<circle cx="6" cy="12" r="2.5"/><circle cx="17.5" cy="5.5" r="2.5"/><circle cx="17.5" cy="18.5" r="2.5"/><path d="M8.3 10.8l7-4M8.3 13.2l7 4"/>',
        'copy'     => '<rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2"/>',
        'back'     => '<path d="M14.5 5l-7 7 7 7"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 13.5a7.6 7.6 0 0 0 0-3l2-1.6-2-3.4-2.4 1a7.6 7.6 0 0 0-2.6-1.5L14 2.5h-4L9.6 5a7.6 7.6 0 0 0-2.6 1.5l-2.4-1-2 3.4 2 1.6a7.6 7.6 0 0 0 0 3l-2 1.6 2 3.4 2.4-1a7.6 7.6 0 0 0 2.6 1.5l.4 2.5h4l.4-2.5a7.6 7.6 0 0 0 2.6-1.5l2.4 1 2-3.4z"/>',
        'chevron-down' => '<path d="M6 9.5l6 6 6-6"/>',
        'pin'      => '<path d="M9 4h6l-1 6 3 3v1.5H7V13l3-3zM12 14.5V21"/>',
        'lock'     => '<rect x="5.5" y="10.5" width="13" height="9.5" rx="2"/><path d="M8.5 10.5V7.5a3.5 3.5 0 0 1 7 0v3"/>',
        'unlock'   => '<rect x="5.5" y="10.5" width="13" height="9.5" rx="2"/><path d="M8.5 10.5V7.5a3.5 3.5 0 0 1 6.8-1.2"/>',
        'restore'  => '<path d="M4 10a8 8 0 1 1 2.3 6.3M4 10V4.5M4 10h5.5"/>',
        'close'    => '<path d="M6 6l12 12M18 6L6 18"/>',
        'paperclip' => '<path d="M8 12.5l7.5-7.5a3.2 3.2 0 0 1 4.5 4.5l-9 9a5.3 5.3 0 0 1-7.5-7.5L11 3.5"/>',
        'mic'      => '<rect x="9.5" y="3" width="5" height="11" rx="2.5"/><path d="M6 11.5a6 6 0 0 0 12 0M12 17.5V21"/>',
        'image'    => '<rect x="3.5" y="4.5" width="17" height="15" rx="2"/><circle cx="9" cy="9.5" r="1.5"/><path d="M3.5 16.5l5-4.5 4 3.5 3-2.5 5 3.5"/>',
        'check'    => '<path d="M5 12.5l4.5 4.5L19 7.5"/>',
        'kbd'      => '<rect x="3" y="6" width="18" height="12" rx="2"/><path d="M7 12h.01M12 12h.01M17 12h.01M8 15h8"/>',
        'sync'     => '<path d="M20 5v5h-5M4 19v-5h5M19.5 10a8 8 0 0 0-14-3.5M4.5 14a8 8 0 0 0 14 3.5"/>',
    ];

    $body = $paths[$key] ?? $paths['book'];
    $cls  = $class !== '' ? ' class="' . e($class) . '"' : '';

    return '<svg' . $cls . ' width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none"'
        . ' stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"'
        . ' aria-hidden="true">' . $body . '</svg>';
}
