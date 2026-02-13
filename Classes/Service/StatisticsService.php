<?php

declare(strict_types=1);

/*
 * This file is part of TYPO3 CMS-based extension "view_tracker" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace B13\ViewTracker\Service;

use B13\ViewTracker\DTO\TrackingData;
use B13\ViewTracker\Storage\StorageInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * This class is basically a shell to call the same methods on the storage.
 * This way other classes don't know and care about the storage type, as it is only directly used here.
 */
class StatisticsService
{
    public function __construct(
        #[Autowire(service: 'b13viewTracker.storage')]
        protected readonly StorageInterface $storage,
    ) {}

    public function addView(TrackingData $trackingData): void
    {
        $this->storage->addView($trackingData);
    }

    public function getMostViewed(int $limit = 10, ?int $pageType = null): array
    {
        return $this->storage->getMostViewed($limit, $pageType);
    }

    public function getViewsForPage(int $pageId, ?\DateTimeInterface $start = null, ?\DateTimeInterface $end = null, ?int $pageType = null): int
    {
        return $this->storage->getViewsForPage($pageId, $start, $end, $pageType);
    }

    public function getDistinctPageTypes(): array
    {
        return $this->storage->getDistinctPageTypes();
    }
}
