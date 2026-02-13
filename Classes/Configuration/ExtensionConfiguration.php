<?php

declare(strict_types=1);

/*
 * This file is part of TYPO3 CMS-based extension "view_tracker" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace B13\ViewTracker\Configuration;

use TYPO3\CMS\Core\Domain\Repository\PageRepository;

final class ExtensionConfiguration
{
    /**
     * @return int[]
     */
    public function getExcludedDoktypes(): array
    {
        $doktypes = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['view_tracker']['excludedDoktypes']
            ?? [
                PageRepository::DOKTYPE_LINK,
                PageRepository::DOKTYPE_SHORTCUT,
                PageRepository::DOKTYPE_BE_USER_SECTION,
                PageRepository::DOKTYPE_MOUNTPOINT,
                PageRepository::DOKTYPE_SPACER,
                PageRepository::DOKTYPE_SYSFOLDER,
            ];

        return array_map('intval', $doktypes);
    }

    /**
     * @param array|int $pageRecordOrDoktype A page record array or a doktype integer
     */
    public function isDoktypeExcluded(array|int $pageRecordOrDoktype): bool
    {
        $doktype = is_array($pageRecordOrDoktype)
            ? (int)($pageRecordOrDoktype['doktype'] ?? 0)
            : $pageRecordOrDoktype;

        return in_array($doktype, $this->getExcludedDoktypes(), true);
    }
}
