<?php

declare(strict_types=1);

/*
 * This file is part of TYPO3 CMS-based extension "view_tracker" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace B13\ViewTracker\Tests\Unit\Configuration;

use B13\ViewTracker\Configuration\ExtensionConfiguration;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ExtensionConfigurationTest extends UnitTestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['view_tracker']);
        parent::tearDown();
    }

    #[Test]
    public function nonContentDoktypesAreExcludedByDefault(): void
    {
        $subject = new ExtensionConfiguration();
        self::assertTrue($subject->isDoktypeExcluded(PageRepository::DOKTYPE_SYSFOLDER));
        self::assertTrue($subject->isDoktypeExcluded(['doktype' => PageRepository::DOKTYPE_SHORTCUT]));
        self::assertFalse($subject->isDoktypeExcluded(PageRepository::DOKTYPE_DEFAULT));
        self::assertFalse($subject->isDoktypeExcluded(['uid' => 1]));
    }

    #[Test]
    public function configuredDoktypesReplaceTheDefaults(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['view_tracker']['excludedDoktypes'] = ['116'];
        $subject = new ExtensionConfiguration();
        self::assertSame([116], $subject->getExcludedDoktypes());
        self::assertTrue($subject->isDoktypeExcluded(['doktype' => '116']));
        self::assertFalse($subject->isDoktypeExcluded(PageRepository::DOKTYPE_SYSFOLDER));
    }
}
