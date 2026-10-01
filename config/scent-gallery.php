<?php

return [
    // Editorial artwork is tied to exact catalog slugs, never a database ID.
    // Unlisted products always keep the image selected by the administrator.
    'featured' => 'miss-dior-blooming-bouquet',
    'selection' => ['dior-sauvage', 'chanel-chance-eau-tendre', 'parfums-de-marly-delina-exclusif', 'tom-ford-rose-prick-edp'],
    'artwork' => [
        'miss-dior-blooming-bouquet' => 'images/gallery/miss-dior.webp',
        'dior-sauvage' => 'images/gallery/sauvage.webp',
        'chanel-chance-eau-tendre' => 'images/gallery/chanel.webp',
        'parfums-de-marly-delina-exclusif' => 'images/gallery/delina.webp',
        'tom-ford-rose-prick-edp' => 'images/gallery/rose-prick.webp',
    ],
];
