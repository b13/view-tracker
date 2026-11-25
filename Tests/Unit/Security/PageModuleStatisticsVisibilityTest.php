<?php

declare(strict_types=1);

/*
 * This file is part of TYPO3 CMS-based extension "view_tracker" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace B13\ViewTracker\Tests\Unit\Security;

use B13\ViewTracker\Security\PageModuleStatisticsVisibility;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class PageModuleStatisticsVisibilityTest extends UnitTestCase
{
    public static function visibilityProvider(): \Generator
    {
        yield 'editor, no settings' => [false, [3], [], true];
        yield 'admin, admins only' => [true, [], ['visibleForAdminsOnly' => true], true];
        yield 'editor, admins only' => [false, [3], ['visibleForAdminsOnly' => true], false];
        yield 'editor in allowed group' => [false, [3, 5], ['visibleForGroups' => '12, 5'], true];
        yield 'editor in other group' => [false, [3], ['visibleForGroups' => '12,5'], false];
        yield 'editor without group, groups set' => [false, [], ['visibleForGroups' => '12'], false];
        yield 'admin, groups set' => [true, [], ['visibleForGroups' => '12'], true];
        yield 'admins only wins over groups' => [false, [12], ['visibleForAdminsOnly' => true, 'visibleForGroups' => '12'], false];
        yield 'invalid group list means no restriction' => [false, [3], ['visibleForGroups' => ' , abc'], true];
    }

    #[Test]
    #[DataProvider('visibilityProvider')]
    public function visibilityFollowsRoleAndSiteSettings(bool $admin, array $groupIds, array $settings, bool $expected): void
    {
        $site = new Site('main', 1, ['base' => '/', 'settings' => ['view_tracker' => ['pageModuleStatistics' => $settings]]]);
        self::assertSame($expected, (new PageModuleStatisticsVisibility())->isVisible($this->user($admin, $groupIds), $site));
    }

    #[Test]
    public function nobodyButAdminsSeesItWithoutSite(): void
    {
        $subject = new PageModuleStatisticsVisibility();
        self::assertFalse($subject->isVisible($this->user(false, [3]), null));
        self::assertTrue($subject->isVisible($this->user(true, []), null));
    }

    #[Test]
    public function loggedOutUsersNeverSeeIt(): void
    {
        self::assertFalse((new PageModuleStatisticsVisibility())->isVisible(new UserAspect(), new Site('main', 1, ['base' => '/'])));
    }

    private function user(bool $admin, array $groupIds): UserAspect
    {
        $user = new BackendUserAuthentication();
        $user->user = ['uid' => 7, 'admin' => $admin ? 1 : 0];
        $user->userGroupsUID = $groupIds;
        return new UserAspect($user);
    }
}
