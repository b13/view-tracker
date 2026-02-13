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
use Doctrine\DBAL\ParameterType;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;

class DatabaseStorage implements StorageInterface
{
    public const TABLE_NAME = 'tx_view_tracker_count';

    public function __construct(
        protected readonly ConnectionPool $connectionPool
    ) {}

    public function addView(TrackingData $trackingData): void
    {
        $connection = $this->connectionPool->getConnectionForTable(self::TABLE_NAME);
        $connection->insert(
            self::TABLE_NAME,
            [
                'page' => $trackingData->pageId,
                'pagetype' => $trackingData->pageType,
                'timestamp' => time(),
                'language' => $trackingData->language,
                'browser' => $trackingData->browser,
                'os' => $trackingData->os,
                'device_type' => $trackingData->deviceType,
                'country' => $trackingData->country,
            ],
            [
                'page' => ParameterType::INTEGER,
                'pagetype' => ParameterType::INTEGER,
                'timestamp' => ParameterType::INTEGER,
                'language' => ParameterType::STRING,
                'browser' => ParameterType::STRING,
                'os' => ParameterType::STRING,
                'device_type' => ParameterType::STRING,
                'country' => ParameterType::STRING,
            ]
        );
    }

    public function getMostViewed(int $limit = 10, ?int $pageType = null, ?\DateTimeInterface $start = null, ?\DateTimeInterface $end = null): array
    {
        $connection = $this->connectionPool->getConnectionForTable(self::TABLE_NAME);
        $startTs = $start?->getTimestamp() ?? 0;
        $endTs = $end?->getTimestamp() ?? time();

        $sql = 'SELECT page, COUNT(*) AS hits FROM ' . self::TABLE_NAME
            . ' WHERE timestamp >= ? AND timestamp <= ?';
        $params = [$startTs, $endTs];
        $paramTypes = [ParameterType::INTEGER, ParameterType::INTEGER];

        if ($pageType !== null) {
            $sql .= ' AND pagetype = ?';
            $params[] = $pageType;
            $paramTypes[] = ParameterType::INTEGER;
        }

        $sql .= ' GROUP BY page ORDER BY hits DESC LIMIT ' . $limit;

        return $connection->executeQuery($sql, $params, $paramTypes)->fetchAllAssociative();
    }

    public function getViewsForPage(int $pageId, ?\DateTimeInterface $start = null, ?\DateTimeInterface $end = null, ?int $pageType = null): int
    {
        $connection = $this->connectionPool->getConnectionForTable(self::TABLE_NAME);
        $startTs = $start?->getTimestamp() ?? 0;
        $endTs = $end?->getTimestamp() ?? time();

        $sql = 'SELECT COUNT(*) FROM ' . self::TABLE_NAME
            . ' WHERE page = ? AND timestamp >= ? AND timestamp <= ?';
        $params = [$pageId, $startTs, $endTs];

        if ($pageType !== null) {
            $sql .= ' AND pagetype = ?';
            $params[] = $pageType;
        }

        return (int)$connection->executeQuery($sql, $params)->fetchOne();
    }

    public function getDistinctPageTypes(): array
    {
        $queryBuilder = $this->getQueryBuilder();
        return $queryBuilder
            ->selectLiteral('DISTINCT pagetype')
            ->from(self::TABLE_NAME)
            ->orderBy('pagetype', 'ASC')
            ->executeQuery()
            ->fetchFirstColumn();
    }

    protected function getQueryBuilder(): QueryBuilder
    {
        return $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);
    }
}
