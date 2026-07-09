<?php

declare(strict_types=1);

/*
 * This file is part of TYPO3 CMS-based extension "view_tracker" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace B13\ViewTracker\ViewHelpers;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Frontend\Page\PageInformation;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Adds the ViewHelper for adding a pixel to the current page.
 *   <html
 *     xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
 *     xmlns:vtr="http://typo3.org/ns/B13/ViewTracker/ViewHelpers"
 *     data-namespace-typo3-fluid="true"
 *   >
 *
 *   <f:if condition="!{settings.t3dnt}">
 *     <vt:pixel />
 *   </f:if>
 *
 * </html>
 */
final class PixelViewHelper extends AbstractViewHelper
{
    protected $escapeOutput = false;

    public function render()
    {
        /** @var ServerRequestInterface $request */
        $request = $this->renderingContext->getAttribute(ServerRequestInterface::class);
        /** @var PageInformation $pageInformation */
        $pageInformation = $request->getAttribute('frontend.page.information');
        $pageId = $pageInformation->getPageRecord()['_LOCALIZED_UID'] ?? 0;
        if (!$pageId) {
            $pageId = $pageInformation->getPageRecord()['uid'];
        }
        if (!$pageId) {
            return '';
        }
        $site = $request->getAttribute('site');
        $endpoint = $site instanceof Site
            ? (string)$site->getSettings()->get('view_tracker.endpoint', '/_b13vt')
            : '/_b13vt';
        $src = $endpoint . '?page=' . (int)$pageId;
        $type = $request->getAttribute('routing')?->getPageType();
        if ($type) {
            $src .= '&amp;type=' . $type;
        }
        return '<img src="' . $src . '" style="position: fixed; z-index: -1000;" alt="" role="presentation" width="1" height="1" loading="eager">';
    }
}
