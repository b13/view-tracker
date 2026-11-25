<?php

declare(strict_types=1);

/*
 * This file is part of TYPO3 CMS-based extension "view_tracker" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace B13\ViewTracker\Tests\Functional\Middleware;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Sends the pixel request through the whole frontend middleware stack,
 * unlike TrackViewMiddlewareTest, so that the position of the middleware
 * is tested too.
 */
final class MiddlewareOrderTest extends FunctionalTestCase
{
    private const BROWSER = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36';

    protected array $testExtensionsToLoad = ['b13/view-tracker'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->getConnectionPool()->getConnectionForTable('pages')->insert('pages', [
            'uid' => 1, 'pid' => 0, 'title' => 'Home', 'slug' => '/', 'is_siteroot' => 1, 'doktype' => 1,
        ]);
    }

    #[Test]
    public function pixelIsCountedOnASiteWhoseLanguagesAllHaveAPrefix(): void
    {
        $this->writeSiteConfiguration('/en/', '/de/');

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest('https://example.com/_b13vt?page=1'))->withHeader('User-Agent', self::BROWSER)
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('image/png', $response->getHeaderLine('Content-Type'));
        self::assertSame(1, $this->countViews());
    }

    #[Test]
    public function pixelIsCountedOnASiteWithoutPrefixForTheDefaultLanguage(): void
    {
        $this->writeSiteConfiguration('/', '/de/');

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest('https://example.com/_b13vt?page=1'))->withHeader('User-Agent', self::BROWSER)
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(1, $this->countViews());
    }

    private function countViews(): int
    {
        return (int)$this->getConnectionPool()->getConnectionForTable('tx_view_tracker_count')
            ->count('*', 'tx_view_tracker_count', []);
    }

    private function writeSiteConfiguration(string $defaultBase, string $germanBase): void
    {
        $path = Environment::getConfigPath() . '/sites/main';
        GeneralUtility::mkdir_deep($path);
        file_put_contents($path . '/config.yaml', implode("\n", [
            'rootPageId: 1',
            "base: 'https://example.com/'",
            'languages:',
            '  - { title: English, enabled: true, languageId: 0, base: ' . $defaultBase . ', locale: en_US.UTF-8, flag: us }',
            '  - { title: Deutsch, enabled: true, languageId: 1, base: ' . $germanBase . ', locale: de_DE.UTF-8, flag: de }',
        ]) . "\n");
    }
}
