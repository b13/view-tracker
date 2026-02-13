<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

defined('TYPO3') or die();

// Define which page doktypes should be excluded from showing view statistics.
// These doktypes typically don't have trackable content (folders, shortcuts, etc.).
// Integrators can add custom doktypes in their ext_localconf.php:
//   $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['view_tracker']['excludedDoktypes'][] = 116;
$GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['view_tracker']['excludedDoktypes'] ??= [
    PageRepository::DOKTYPE_LINK, // 3 - External URL
    PageRepository::DOKTYPE_SHORTCUT, // 4 - Shortcut
    PageRepository::DOKTYPE_BE_USER_SECTION, // 6 - Backend User Section
    PageRepository::DOKTYPE_MOUNTPOINT, // 7 - Mount Point
    PageRepository::DOKTYPE_SPACER, // 199 - Menu Separator
    PageRepository::DOKTYPE_SYSFOLDER, // 254 - Folder
];
