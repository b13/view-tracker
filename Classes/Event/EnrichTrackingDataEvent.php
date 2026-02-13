<?php

declare(strict_types=1);

/*
 * This file is part of TYPO3 CMS-based extension "view_tracker" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace B13\ViewTracker\Event;

use B13\ViewTracker\DTO\TrackingData;
use Psr\Http\Message\ServerRequestInterface;

final class EnrichTrackingDataEvent
{
    public function __construct(
        public readonly TrackingData $trackingData,
        public readonly ServerRequestInterface $request,
    ) {}
}
