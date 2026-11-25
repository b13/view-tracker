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

use B13\ViewTracker\Event\EnrichTrackingDataEvent;
use B13\ViewTracker\Middleware\TrackViewMiddleware;
use B13\ViewTracker\Service\StatisticsService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\NullLogger;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\ResponseFactory;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\StreamFactory;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class TrackViewMiddlewareTest extends FunctionalTestCase
{
    private const BROWSER = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36';

    protected array $testExtensionsToLoad = ['b13/view-tracker'];

    /** @var list<callable(EnrichTrackingDataEvent): void> */
    private array $listeners = [];

    private bool $handlerCalled = false;

    #[Test]
    public function viewIsCountedAndPixelIsReturned(): void
    {
        $response = $this->process($this->pixelRequest('/_b13vt?page=12&type=7')
            ->withHeader('Accept-Language', 'de-DE,de;q=0.9,en;q=0.8'));

        self::assertFalse($this->handlerCalled);
        self::assertSame(200, $response->getStatusCode());
        self::assertSame('image/png', $response->getHeaderLine('Content-Type'));
        self::assertStringContainsString('no-store', $response->getHeaderLine('Cache-Control'));
        self::assertStringStartsWith("\x89PNG", (string)$response->getBody());

        $rows = $this->countedViews();
        self::assertCount(1, $rows);
        self::assertSame(12, (int)$rows[0]['page']);
        self::assertSame(7, (int)$rows[0]['pagetype']);
        self::assertSame('de-de', $rows[0]['language']);
        self::assertSame('', $rows[0]['referrer']);
        self::assertEqualsWithDelta(time(), (int)$rows[0]['timestamp'], 5);
    }

    public static function botUserAgentProvider(): \Generator
    {
        yield 'empty user agent' => [''];
        yield 'Googlebot' => ['Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'];
        yield 'Bingbot' => ['Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)'];
        yield 'Yahoo Slurp' => ['Mozilla/5.0 (compatible; Yahoo! Slurp; http://help.yahoo.com/help/us/ysearch/slurp)'];
        yield 'generic crawler' => ['SomeCrawler/1.0'];
        yield 'spider' => ['Baiduspider/2.0'];
        yield 'AdSense' => ['Mediapartners-Google'];
        yield 'feed fetcher' => ['FeedFetcher-Google; (+http://www.google.com/feedfetcher.html)'];
        yield 'Lighthouse' => ['Mozilla/5.0 (Linux; Android 11) Chrome-Lighthouse'];
    }

    #[Test]
    #[DataProvider('botUserAgentProvider')]
    public function botsGetThePixelButAreNotCounted(string $userAgent): void
    {
        $response = $this->process($this->pixelRequest('/_b13vt?page=12')->withHeader('User-Agent', $userAgent));

        self::assertSame('image/png', $response->getHeaderLine('Content-Type'));
        self::assertSame([], $this->countedViews());
    }

    #[Test]
    public function viewsOfLoggedInEditorsAreNotCounted(): void
    {
        $response = $this->process($this->pixelRequest('/_b13vt?page=12')->withCookieParams(['be_typo_user' => 'abc']));

        self::assertSame('image/png', $response->getHeaderLine('Content-Type'));
        self::assertSame([], $this->countedViews());
    }

    public static function passThroughProvider(): \Generator
    {
        yield 'other path' => ['/some/page?page=12'];
        yield 'endpoint without page' => ['/_b13vt'];
        yield 'endpoint with empty page' => ['/_b13vt?page='];
        yield 'old endpoint' => ['/_pixel?page=12'];
    }

    #[Test]
    #[DataProvider('passThroughProvider')]
    public function otherRequestsArePassedOn(string $uri): void
    {
        $this->process($this->pixelRequest($uri));

        self::assertTrue($this->handlerCalled);
        self::assertSame([], $this->countedViews());
    }

    #[Test]
    public function requestsWithoutSiteArePassedOn(): void
    {
        $this->process($this->pixelRequest('/_b13vt?page=12')->withoutAttribute('site'));

        self::assertTrue($this->handlerCalled);
        self::assertSame([], $this->countedViews());
    }

    #[Test]
    public function endpointFollowsSiteSetting(): void
    {
        $site = $this->site(['view_tracker' => ['endpoint' => '/_count']]);

        $this->process($this->pixelRequest('/_b13vt?page=12', $site));
        self::assertTrue($this->handlerCalled);
        self::assertSame([], $this->countedViews());

        $this->handlerCalled = false;
        $this->process($this->pixelRequest('/_count?page=12', $site));
        self::assertFalse($this->handlerCalled);
        self::assertCount(1, $this->countedViews());
    }

    #[Test]
    public function listenersCanAddData(): void
    {
        $this->listeners[] = static function (EnrichTrackingDataEvent $event): void {
            $event->trackingData->referrer = $event->request->getQueryParams()['ref'] ?? '';
            $event->trackingData->country = 'DE';
        };

        $this->process($this->pixelRequest('/_b13vt?page=12&ref=example.org'));

        $rows = $this->countedViews();
        self::assertCount(1, $rows);
        self::assertSame('example.org', $rows[0]['referrer']);
        self::assertSame('DE', $rows[0]['country']);
    }

    #[Test]
    public function listenersCanSkipTracking(): void
    {
        $this->listeners[] = static function (EnrichTrackingDataEvent $event): void {
            $event->trackingData->skipTracking = true;
        };

        $response = $this->process($this->pixelRequest('/_b13vt?page=12'));

        self::assertSame('image/png', $response->getHeaderLine('Content-Type'));
        self::assertSame([], $this->countedViews());
    }

    public static function languageProvider(): \Generator
    {
        yield 'no header' => [null, ''];
        yield 'single language' => ['en', 'en'];
        yield 'region is lower-cased' => ['en-US,en;q=0.5', 'en-us'];
        yield 'quality of first entry is dropped' => ['fr-CH;q=0.9, fr;q=0.8', 'fr-ch'];
        yield 'wildcard' => ['*', ''];
        yield 'longest stored tag' => ['zh-Hant-TW', 'zh-hant-tw'];
        yield 'too long tag is dropped' => ['de-DE-x-very-long-private-use', ''];
        yield 'invalid characters are dropped' => ['<script>', ''];
    }

    #[Test]
    #[DataProvider('languageProvider')]
    public function primaryBrowserLanguageIsStored(?string $header, string $expected): void
    {
        $request = $this->pixelRequest('/_b13vt?page=12');
        if ($header !== null) {
            $request = $request->withHeader('Accept-Language', $header);
        }

        $this->process($request);

        $rows = $this->countedViews();
        self::assertCount(1, $rows);
        self::assertSame($expected, $rows[0]['language']);
    }

    private function process(ServerRequestInterface $request): ResponseInterface
    {
        $listeners = $this->listeners;
        $eventDispatcher = new class ($listeners) implements EventDispatcherInterface {
            public function __construct(private readonly array $listeners) {}

            public function dispatch(object $event): object
            {
                foreach ($this->listeners as $listener) {
                    $listener($event);
                }
                return $event;
            }
        };
        $handler = new class ($this->handlerCalled) implements RequestHandlerInterface {
            public function __construct(private bool &$called) {}

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->called = true;
                return new HtmlResponse('page');
            }
        };
        $subject = new TrackViewMiddleware(
            $this->get(StatisticsService::class),
            new NullLogger(),
            new StreamFactory(),
            new ResponseFactory(),
            $eventDispatcher,
        );
        return $subject->process($request, $handler);
    }

    private function pixelRequest(string $uri, ?Site $site = null): ServerRequestInterface
    {
        $request = new ServerRequest('https://example.com' . $uri, 'GET', 'php://input', ['User-Agent' => self::BROWSER]);
        parse_str((string)parse_url($uri, PHP_URL_QUERY), $queryParams);
        return $request
            ->withQueryParams($queryParams)
            ->withAttribute('site', $site ?? $this->site());
    }

    private function site(array $settings = []): Site
    {
        return new Site('main', 1, ['base' => 'https://example.com/', 'settings' => $settings]);
    }

    private function countedViews(): array
    {
        return $this->getConnectionPool()
            ->getConnectionForTable('tx_view_tracker_count')
            ->select(['*'], 'tx_view_tracker_count')
            ->fetchAllAssociative();
    }
}
