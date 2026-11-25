<?php

declare(strict_types=1);

/*
 * This file is part of TYPO3 CMS-based extension "view_tracker" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace B13\ViewTracker\Tests\Functional\Storage;

use B13\ViewTracker\DTO\TrackingData;
use B13\ViewTracker\Storage\DatabaseStorage;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class DatabaseStorageTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['b13/view-tracker'];

    private DatabaseStorage $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new DatabaseStorage($this->getConnectionPool());
    }

    #[Test]
    public function addViewStoresAllFields(): void
    {
        $data = new TrackingData(5, 1);
        $data->language = 'en-gb';
        $data->browser = 'Firefox';
        $data->os = 'Linux';
        $data->deviceType = 'desktop';
        $data->country = 'GB';
        $data->referrer = 'example.org';

        $this->subject->addView($data);

        $row = $this->getConnectionPool()->getConnectionForTable(DatabaseStorage::TABLE_NAME)
            ->select(['*'], DatabaseStorage::TABLE_NAME)->fetchAssociative();
        self::assertSame(5, (int)$row['page']);
        self::assertSame(1, (int)$row['pagetype']);
        self::assertSame('en-gb', $row['language']);
        self::assertSame('Firefox', $row['browser']);
        self::assertSame('Linux', $row['os']);
        self::assertSame('desktop', $row['device_type']);
        self::assertSame('GB', $row['country']);
        self::assertSame('example.org', $row['referrer']);
    }

    #[Test]
    public function viewsForPageAreFilteredByPeriodAndPageType(): void
    {
        $now = time();
        $this->insertViews(1, 0, $now - 3600, 3);
        $this->insertViews(1, 0, $now - 10 * 86400, 2);
        $this->insertViews(1, 7, $now - 3600, 4);
        $this->insertViews(2, 0, $now - 3600, 5);

        self::assertSame(9, $this->subject->getViewsForPage(1));
        self::assertSame(7, $this->subject->getViewsForPage(1, new \DateTimeImmutable('-1 day')));
        self::assertSame(3, $this->subject->getViewsForPage(1, new \DateTimeImmutable('-1 day'), null, 0));
        self::assertSame(4, $this->subject->getViewsForPage(1, null, null, 7));
        self::assertSame(2, $this->subject->getViewsForPage(1, new \DateTimeImmutable('-11 days'), new \DateTimeImmutable('-9 days')));
        self::assertSame(0, $this->subject->getViewsForPage(3));
    }

    #[Test]
    public function mostViewedPagesAreSortedByViews(): void
    {
        $now = time();
        $this->insertViews(1, 0, $now - 60, 2);
        $this->insertViews(2, 0, $now - 60, 5);
        $this->insertViews(3, 7, $now - 60, 3);
        $this->insertViews(4, 0, $now - 60, 1);

        $result = $this->subject->getMostViewed(2);
        self::assertSame([2, 3], array_map(static fn(array $row): int => (int)$row['page'], $result));
        self::assertSame([5, 3], array_map(static fn(array $row): int => (int)$row['hits'], $result));

        $result = $this->subject->getMostViewed(10, 0);
        self::assertSame([2, 1, 4], array_map(static fn(array $row): int => (int)$row['page'], $result));
    }

    #[Test]
    public function distinctPageTypesAreSorted(): void
    {
        $now = time();
        $this->insertViews(1, 7, $now, 1);
        $this->insertViews(1, 0, $now, 2);
        $this->insertViews(2, 7, $now, 1);

        self::assertSame([0, 7], array_map('intval', $this->subject->getDistinctPageTypes()));
    }

    private function insertViews(int $page, int $pageType, int $timestamp, int $count): void
    {
        $connection = $this->getConnectionPool()->getConnectionForTable(DatabaseStorage::TABLE_NAME);
        for ($i = 0; $i < $count; $i++) {
            $connection->insert(DatabaseStorage::TABLE_NAME, [
                'page' => $page,
                'pagetype' => $pageType,
                'timestamp' => $timestamp,
            ]);
        }
    }
}
