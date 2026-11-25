<?php

declare(strict_types=1);

/*
 * This file is part of TYPO3 CMS-based extension "view_tracker" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace B13\ViewTracker\Tests\Functional\ViewHelpers;

use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\CMS\Frontend\Page\PageInformation;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

final class PixelViewHelperTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['b13/view-tracker'];

    #[Test]
    public function scriptCarriesThePixelUrlAndNoscriptFallback(): void
    {
        $output = $this->render(['uid' => 12], '0');

        self::assertMatchesRegularExpression(
            '#^<script src="[^"]*b13vt\.js[^"]*" data-src="/_b13vt\?page=12" async></script>'
            . '<noscript><img src="/_b13vt\?page=12" [^>]+></noscript>$#',
            $output
        );
    }

    #[Test]
    public function localizedPageIdAndPageTypeAreUsed(): void
    {
        $output = $this->render(['uid' => 12, '_LOCALIZED_UID' => 34], '7');

        self::assertStringContainsString('data-src="/_b13vt?page=34&amp;type=7"', $output);
        self::assertStringContainsString('<img src="/_b13vt?page=34&amp;type=7"', $output);
    }

    #[Test]
    public function endpointFollowsSiteSetting(): void
    {
        $output = $this->render(['uid' => 12], '0', ['view_tracker' => ['endpoint' => '/_count']]);

        self::assertStringContainsString('data-src="/_count?page=12"', $output);
    }

    #[Test]
    public function nothingIsRenderedWithoutPage(): void
    {
        self::assertSame('', $this->render(['uid' => 0], '0'));
    }

    private function render(array $pageRecord, string $pageType, array $settings = []): string
    {
        $pageInformation = new PageInformation();
        $pageInformation->setPageRecord($pageRecord);
        $request = (new ServerRequest('https://example.com/'))
            ->withAttribute('frontend.page.information', $pageInformation)
            ->withAttribute('routing', new PageArguments((int)$pageRecord['uid'], $pageType, []))
            ->withAttribute('site', new Site('main', 1, ['base' => 'https://example.com/', 'settings' => $settings]))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));

        $context = $this->get(RenderingContextFactory::class)->create([], $request);
        $context->setAttribute(ServerRequestInterface::class, $request);
        $context->getTemplatePaths()->setTemplateSource(
            '<html xmlns:vt="http://typo3.org/ns/B13/ViewTracker/ViewHelpers" data-namespace-typo3-fluid="true"><vt:pixel /></html>'
        );
        return trim((new TemplateView($context))->render());
    }
}
