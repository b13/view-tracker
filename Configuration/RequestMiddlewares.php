<?php

return [
    'frontend' => [
        'b13/view-tracker' => [
            'target' => \B13\ViewTracker\Middleware\TrackViewMiddleware::class,
            // Needs the "site" attribute, and must answer before a site without
            // a root language base redirects the pixel request to "/de/" etc.
            'after' => [
                'typo3/cms-frontend/site',
            ],
            'before' => [
                'typo3/cms-frontend/base-redirect-resolver',
            ],
        ],
    ],
];
