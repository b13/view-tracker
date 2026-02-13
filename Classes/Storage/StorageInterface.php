<?php

declare(strict_types=1);

/*
 * This file is part of TYPO3 CMS-based extension "view_tracker" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace B13\ViewTracker\Storage;

use B13\ViewTracker\DTO\TrackingData;

interface StorageInterface
{
    public function addView(TrackingData $trackingData): void;
    public function getMostViewed(int $limit = 10, ?int $pageType = null, ?\DateTimeInterface $start = null, ?\DateTimeInterface $end = null): array;
    public function getViewsForPage(int $pageId, ?\DateTimeInterface $start = null, ?\DateTimeInterface $end = null, ?int $pageType = null): int;
    public function getDistinctPageTypes(): array;
}
