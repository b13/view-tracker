<?php

declare(strict_types=1);

/*
 * This file is part of TYPO3 CMS-based extension "view_tracker" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace B13\ViewTracker\DTO;

class TrackingData
{
    public string $language = '';
    public string $browser = '';
    public string $os = '';
    public string $deviceType = '';
    public string $country = '';
    public bool $skipTracking = false;

    public function __construct(
        public readonly int $pageId,
        public readonly int $pageType = 0,
    ) {}
}
