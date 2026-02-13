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
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Core\Cache\Exception;

class RedisStorage implements StorageInterface
{
    protected readonly \Redis $redis;
    private bool $dimensionWarningLogged = false;

    public function __construct(
        #[Autowire(expression: 'service("extension-configuration").get("view_tracker", "redisStorage")')]
        protected $configuration,
        private readonly LoggerInterface $logger,
    ) {
        if (!extension_loaded('redis')) {
            throw new Exception('The PHP extension "redis" must be installed and loaded in order to use the redis backend.', 1279462933);
        }
        $this->redis = new \Redis();
        $this->redis->connect($configuration['host'], $configuration['port'] ?? 6379);
        $this->redis->select($configuration['database']);
    }

    public function addView(TrackingData $trackingData): void
    {
        if (!$this->dimensionWarningLogged) {
            $this->logger->notice('RedisStorage does not store dimension data (browser, os, device_type, language, country). Use DatabaseStorage for full analytics.');
            $this->dimensionWarningLogged = true;
        }
        $time = time();
        $this->redis->incr($trackingData->pageId . '_' . $trackingData->pageType . '_' . $time);
    }

    public function getMostViewed(int $limit = 10, ?int $pageType = null, ?\DateTimeInterface $start = null, ?\DateTimeInterface $end = null): array
    {
        // TODO: Implement pageType and date filtering
        return [];
    }

    public function getViewsForPage(int $pageId, ?\DateTimeInterface $start = null, ?\DateTimeInterface $end = null, ?int $pageType = null): int
    {
        // TODO: Implement pageType filtering
        return 100;
    }

    public function getDistinctPageTypes(): array
    {
        // TODO: Implement
        return [];
    }
}
