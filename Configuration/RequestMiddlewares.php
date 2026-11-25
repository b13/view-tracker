<?php

return [
    'frontend' => [
        'b13/view-tracker' => [
            'target' => \B13\ViewTracker\Middleware\TrackViewMiddleware::class,
            // Needs the "site" attribute. On a site whose languages all have a
            // path prefix, the base redirect resolver answers the pixel path
            // with a 404, so the pixel has to be answered before it.
            'after' => [
                'typo3/cms-frontend/site',
            ],
            'before' => [
                'typo3/cms-frontend/base-redirect-resolver',
            ],
        ],
    ],
];
