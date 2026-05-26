<?php

declare(strict_types=1);

/*
 * This file is part of TYPO3 CMS-based extension "view_tracker" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace B13\ViewTracker\EventListener;

use B13\ViewTracker\Configuration\ExtensionConfiguration;
use B13\ViewTracker\DTO\LastSevenDaysPeriod;
use B13\ViewTracker\DTO\LastThirtyDaysPeriod;
use B13\ViewTracker\DTO\LastTwelveMonthsPeriod;
use B13\ViewTracker\DTO\PeriodInterface;
use B13\ViewTracker\Service\StatisticsService;
use Doctrine\DBAL\ParameterType;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Controller\Event\ModifyPageLayoutContentEvent;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Localization\Locale;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Fluid\View\FluidViewAdapter;
use TYPO3\CMS\Fluid\View\TemplatePaths;

#[AsEventListener(
    identifier: 'my-extension/backend/modify-page-module-content',
)]
final class PageModuleStatisticsListener
{
    private readonly Locale $locale;

    public function __construct(
        private readonly PageRenderer $pageRenderer,
        private readonly ConnectionPool $connectionPool,
        private readonly StatisticsService $statisticsService,
        private readonly ViewFactoryInterface $viewFactory,
        private readonly ExtensionConfiguration $extensionConfiguration,
    ) {
        $user = $GLOBALS['BE_USER'];
        if ($user->user['lang'] ?? false) {
            $this->locale = GeneralUtility::makeInstance(Locales::class)->createLocale($user->user['lang']);
        } else {
            $this->locale = new Locale();
        }
    }

    public function __invoke(ModifyPageLayoutContentEvent $event): void
    {
        if (!$this->isVisibleToCurrentUser($event->getRequest())) {
            return;
        }

        $id = (int)($event->getRequest()->getQueryParams()['id'] ?? 0);
        if ($id > 0) {
            $pageRecord = BackendUtility::getRecord('pages', $id);
            if (!$pageRecord || $this->extensionConfiguration->isDoktypeExcluded($pageRecord)) {
                return;
            }

            $this->pageRenderer->loadJavaScriptModule('@b13/view-tracker/main.js');
            /** @var Site $site */
            $site = $event->getRequest()->getAttribute('site');
            $languages = $this->getPageIdsForAllLanguages($id, $site->getLanguages());

            $view = $this->viewFactory->create(new ViewFactoryData());
            if (!$view instanceof FluidViewAdapter) {
                return;
            }
            $templatePaths = new TemplatePaths();
            $templatePaths->setTemplateRootPaths(['EXT:view_tracker/Resources/Private/Templates/PageModule']);
            $view->getRenderingContext()->setTemplatePaths($templatePaths);

            $dataSets = [
                [
                    'label' => LocalizationUtility::translate('LLL:EXT:view_tracker/Resources/Private/Language/locallang_be.xlf:statistics.total'),
                    'type' => 'percentage',
                    'data' => $this->getTotalViews($languages),
                ],
            ];
            $dataSets[] = [
                'label' => LocalizationUtility::translate('LLL:EXT:view_tracker/Resources/Private/Language/locallang_be.xlf:statistics.last7days'),
                'type' => 'axis-mixed',
                'data' => $this->getViewsForPeriod($languages, new LastSevenDaysPeriod($this->locale)),
            ];
            $dataSets[] = [
                'label' => LocalizationUtility::translate('LLL:EXT:view_tracker/Resources/Private/Language/locallang_be.xlf:statistics.last30days'),
                'type' => 'axis-mixed',
                'data' => $this->getViewsForPeriod($languages, new LastThirtyDaysPeriod($this->locale)),
            ];
            $dataSets[] = [
                'label' => LocalizationUtility::translate('LLL:EXT:view_tracker/Resources/Private/Language/locallang_be.xlf:statistics.last12months'),
                'type' => 'axis-mixed',
                'data' => $this->getViewsForPeriod($languages, new LastTwelveMonthsPeriod($this->locale)),
            ];
            $dataSets[] = [
                'label' => LocalizationUtility::translate('LLL:EXT:view_tracker/Resources/Private/Language/locallang_be.xlf:statistics.byPageType'),
                'type' => 'bar',
                'data' => $this->getViewsByPageType($languages),
            ];
            $view->assignMultiple([
                'dataSets' => $dataSets,
            ]);

            $event->addHeaderContent($view->render('Header'));
        }
    }

    /**
     * Gate the widget by BE user role + site setting. Admins always see it.
     * Defaults preserve backwards compatibility: every non-admin sees it.
     */
    private function isVisibleToCurrentUser(ServerRequestInterface $request): bool
    {
        $beUser = $GLOBALS['BE_USER'] ?? null;
        if (!$beUser instanceof BackendUserAuthentication) {
            return false;
        }
        if ($beUser->isAdmin()) {
            return true;
        }

        $site = $request->getAttribute('site');
        if (!$site instanceof Site) {
            // No site context (rare in the Page module) — fall back to visible.
            return true;
        }
        $settings = $site->getSettings();

        if ((bool)$settings->get('view_tracker.pageModuleStatistics.visibleForAdminsOnly', false)) {
            return false;
        }

        $visibleForGroups = (string)$settings->get('view_tracker.pageModuleStatistics.visibleForGroups', '');
        if ($visibleForGroups === '') {
            return true;
        }

        $allowedGroupIds = array_filter(array_map(
            'intval',
            array_map('trim', explode(',', $visibleForGroups))
        ));
        if ($allowedGroupIds === []) {
            return true;
        }

        $userGroupIds = array_map(
            static fn(array $group): int => (int)($group['uid'] ?? 0),
            $beUser->userGroups ?? []
        );

        return array_intersect($allowedGroupIds, $userGroupIds) !== [];
    }

    protected function getPageIdsForAllLanguages(int $pageId, array $languages): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');

        $pagesByLanguage = $queryBuilder->select('sys_language_uid', 'uid')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($pageId, ParameterType::INTEGER)),
                    $queryBuilder->expr()->eq('l10n_parent', $queryBuilder->createNamedParameter($pageId, ParameterType::INTEGER)),
                )
            )->executeQuery()->fetchAllKeyValue();

        $return = [];
        /** @var SiteLanguage $language */
        foreach ($languages as $language) {
            $pageInLanguage = $pagesByLanguage[$language->getLanguageId()] ?? false;
            if ($pageInLanguage) {
                $return[$language->getLanguageId()] = ['title' => $language->getTitle(), 'pageId' => $pageInLanguage];
            }
        }
        return $return;
    }

    protected function getTotalViews(array $languages): array
    {
        $labels = [];
        $dataset = [
            'name' => 'views',
            'values' => [],
        ];
        foreach ($languages as $language) {
            $views = $this->statisticsService->getViewsForPage($language['pageId']);
            $labels[] = $language['title'] . ' (' . $views . ')';
            $dataset['values'][] = $views;
        }

        return [
            'labels' => $labels,
            'datasets' => [$dataset],
        ];
    }

    protected function getViewsForPeriod(array $languages, PeriodInterface $period): array
    {
        $dataSets = array_map(
            fn($language) => [
                'name' => $language['title'],
                'chartType' => 'bar',
                'values' => [],
            ],
            $languages
        );

        $dataSets['total'] = [
            'name' => 'Total',
            'chartType' => 'line',
            'values' => [],
        ];

        /** @var \DateTimeImmutable $date */
        foreach ($period->getPeriod() as $date) {
            $total = 0;
            foreach ($languages as $id => $language) {
                $views = $this->statisticsService->getViewsForPage($language['pageId'], $date, $date->add($period->getInterval()));
                $dataSets[$id]['values'][] = $views;
                $total += $views;
            }
            $dataSets['total']['values'][] = $total;
        }

        foreach ($languages as $id => $language) {
            $sum = array_sum($dataSets[$id]['values']);
            $dataSets[$id]['name'] = $language['title'] . ' (' . $sum . ')';
        }
        $dataSets['total']['name'] = 'Total (' . array_sum($dataSets['total']['values']) . ')';

        return [
            'labels' => $period->getLabels(),
            'datasets' => array_values($dataSets), // remove keys to force a JS array instead of an object
        ];
    }

    protected function getViewsByPageType(array $languages): array
    {
        $pageTypes = $this->statisticsService->getDistinctPageTypes();
        if (empty($pageTypes)) {
            $pageTypes = [0];
        }

        $labels = array_map(
            fn(int $type) => LocalizationUtility::translate('LLL:EXT:view_tracker/Resources/Private/Language/locallang_be.xlf:statistics.pageType') . ' ' . $type,
            $pageTypes
        );

        $dataSets = [];
        foreach ($languages as $id => $language) {
            $values = [];
            foreach ($pageTypes as $pageType) {
                $values[] = $this->statisticsService->getViewsForPage($language['pageId'], null, null, $pageType);
            }
            $sum = array_sum($values);
            $dataSets[] = [
                'name' => $language['title'] . ' (' . $sum . ')',
                'values' => $values,
            ];
        }

        return [
            'labels' => $labels,
            'datasets' => $dataSets,
        ];
    }
}
