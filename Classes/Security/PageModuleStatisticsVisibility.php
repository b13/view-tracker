<?php

declare(strict_types=1);

/*
 * This file is part of TYPO3 CMS-based extension "view_tracker" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace B13\ViewTracker\Security;

use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Site\Entity\Site;

/**
 * Decides whether a backend user sees the statistics in the Page module.
 * Admins always do. Without site settings every logged-in editor does.
 */
final class PageModuleStatisticsVisibility
{
    public function isVisible(UserAspect $user, mixed $site): bool
    {
        if (!$user->isLoggedIn()) {
            return false;
        }
        if ($user->isAdmin()) {
            return true;
        }
        if (!$site instanceof Site) {
            return false;
        }
        $settings = $site->getSettings();
        if ((bool)$settings->get('view_tracker.pageModuleStatistics.visibleForAdminsOnly', false)) {
            return false;
        }
        $allowedGroupIds = array_filter(array_map(
            'intval',
            explode(',', (string)$settings->get('view_tracker.pageModuleStatistics.visibleForGroups', ''))
        ));
        if ($allowedGroupIds === []) {
            return true;
        }
        return array_intersect($allowedGroupIds, $user->getGroupIds()) !== [];
    }
}
