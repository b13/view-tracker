<?php

declare(strict_types=1);

/*
 * This file is part of TYPO3 CMS-based extension "view_tracker" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace B13\ViewTracker\Middleware;

use B13\ViewTracker\DTO\TrackingData;
use B13\ViewTracker\Event\EnrichTrackingDataEvent;
use B13\ViewTracker\Service\StatisticsService;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Http\StreamFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final readonly class TrackViewMiddleware implements MiddlewareInterface
{
    public const TARGET = '/_pixel';
    public const PIXEL_PATH = 'EXT:view_tracker/Resources/Private/Image/pixel.png';

    public function __construct(
        protected StatisticsService $statisticsService,
        protected LoggerInterface $logger,
        protected StreamFactory $streamFactory,
        protected ResponseFactoryInterface $responseFactory,
        protected EventDispatcherInterface $eventDispatcher,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getUri()->getPath() === self::TARGET && ($request->getQueryParams()['page'] ?? false)) {
            if (!isset($request->getCookieParams()['be_typo_user'])) {
                $pageId = (int)$request->getQueryParams()['page'];
                $pageType = (int)($request->getQueryParams()['type'] ?? 0);

                $trackingData = new TrackingData($pageId, $pageType);
                $trackingData->language = $this->parseLanguage($request->getHeaderLine('Accept-Language'));

                if ($this->isBot($request->getHeaderLine('User-Agent'))) {
                    return $this->pixelResponse();
                }

                $this->eventDispatcher->dispatch(new EnrichTrackingDataEvent($trackingData, $request));

                if ($trackingData->skipTracking) {
                    return $this->pixelResponse();
                }

                $this->statisticsService->addView($trackingData);

                $this->logger->info('Page view tracked', [
                    'url' => $request->getHeaderLine('Referer'),
                    'pageId' => $pageId,
                    'pageType' => $pageType,
                    'userAgent' => $request->getHeaderLine('User-Agent'),
                ]);
            }

            return $this->pixelResponse();
        }
        return $handler->handle($request);
    }

    /**
     * Simple bot detection — covers 95%+ of bot traffic.
     * For advanced UA parsing, install view-tracker-pro.
     */
    private function isBot(string $userAgent): bool
    {
        if ($userAgent === '') {
            return true;
        }
        $botPatterns = ['bot', 'crawl', 'spider', 'slurp', 'mediapartners', 'fetcher', 'lighthouse'];
        $uaLower = strtolower($userAgent);
        foreach ($botPatterns as $pattern) {
            if (str_contains($uaLower, $pattern)) {
                return true;
            }
        }
        return false;
    }

    private function pixelResponse(): ResponseInterface
    {
        $file = GeneralUtility::getFileAbsFileName(self::PIXEL_PATH);
        return $this->responseFactory->createResponse()
            ->withBody($this->streamFactory->createStreamFromFile($file))
            ->withHeader('Content-Type', 'image/png')
            ->withHeader('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->withHeader('Pragma', 'no-cache')
            ->withHeader('Expires', '0');
    }

    private function parseLanguage(string $acceptLanguage): string
    {
        if ($acceptLanguage === '') {
            return '';
        }
        $primary = strtok($acceptLanguage, ',');
        if ($primary === false) {
            return '';
        }
        $language = strtok(trim($primary), ';');
        if ($language === false) {
            return '';
        }
        return strtolower(trim($language));
    }
}
