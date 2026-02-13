<?php

return [
    'frontend' => [
        'b13/view-tracker' => [
            'target' => \B13\ViewTracker\Middleware\TrackViewMiddleware::class,
            'before' => [
                'typo3/cms-frontend/eid',
            ],
        ],
    ],
];
