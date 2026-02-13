<?php

declare(strict_types=1);

/*
 * This file is part of TYPO3 CMS-based extension "view_tracker" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace B13\ViewTracker\DTO;

use TYPO3\CMS\Core\Localization\DateFormatter;
use TYPO3\CMS\Core\Localization\Locale;

final readonly class LastSevenDaysPeriod implements PeriodInterface
{
    protected \DatePeriod $period;

    public function __construct(
        protected Locale $locale
    ) {
        $date = new \DateTimeImmutable('today 00:00');
        $this->period = new \DatePeriod(
            $date->modify('-7 days'),
            new \DateInterval('P1D'),
            $date,
            \DatePeriod::EXCLUDE_START_DATE | \DatePeriod::INCLUDE_END_DATE
        );
    }

    public function getLabels(): array
    {
        $labels = [];
        foreach ($this->period as $date) {
            $labels[] = (new DateFormatter())->format($date, 'E', $this->locale);
        }
        return $labels;
    }

    public function getPeriod(): \DatePeriod
    {
        return $this->period;
    }

    public function getInterval(): \DateInterval
    {
        return $this->period->getDateInterval();
    }
}
