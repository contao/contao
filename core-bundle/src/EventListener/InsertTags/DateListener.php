<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener\InsertTags;

use Contao\Config;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Date;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a {{format_date::<timestamp>::<format>}} and a
 * {{convert_date::<date>::<source_format>::<target_format>}} insert tag.
 *
 * {{format_date::<timestamp>::<format>}}:
 *
 *   The first parameter is the date string to be parsed or a UNIX timestamp while
 *   the second parameter is the date format. The third parameter can also be either
 *   "date", "datim" or "time", which will use either the current page's or the
 *   system's respective date format setting. If the third parameter is omitted,
 *   "datim" will be used by default.
 *
 *   Usage:
 *
 *     {{format_date::2020-05-26 12:30:35::H:i, d.m.Y}}
 *
 *   Result:
 *
 *     12:30, 26.05.2020
 *
 * {{convert_date::<date>::<source_format>::<target_format>}}:
 *
 *   The first parameter is the date to be parsed and the second parameter the
 *   format of that date. The third parameter defines the output date format.
 *   The second and third parameter can also be "date", "datim" or "time", which
 *   will use the current page's or the system's respective date format setting.
 *
 *   Usage:
 *
 *     {{convert_date::May 26th 2020, 12:30:35::F j\t\h Y, H:i:s::j. F Y, H:i:s}}
 *
 *   Result:
 *
 *     Tuesday, 26. May 2020, 12:30:35
 *
 * @internal
 */
#[AsHook('replaceInsertTags')]
class DateListener
{
    public function __construct(private ContaoFramework $framework, private RequestStack $requestStack)
    {
    }

    public function __invoke(string $insertTag): bool|string
    {
        $tag = explode('::', $insertTag);

        if ('format_date' === $tag[0]) {
            return $this->replaceFormatDate($tag);
        }

        if ('convert_date' === $tag[0]) {
            return $this->replaceConvertDate($tag);
        }

        return false;
    }

    private function replaceFormatDate(array $tag): bool|string
    {
        if (empty($tag[1])) {
            return false;
        }

        $timestamp = is_numeric($tag[1]) ? (int) $tag[1] : strtotime($tag[1]);

        if (false === $timestamp) {
            return $tag[1];
        }

        $date = $this->framework->getAdapter(Date::class);

        return $date->parse($this->getDateFormat($tag[2] ?? 'datim'), $timestamp);
    }

    private function replaceConvertDate(array $tag): bool|string
    {
        if (4 !== \count($tag)) {
            return false;
        }

        $parsedDate = \DateTime::createFromFormat('!'.$this->getDateFormat($tag[2]), $tag[1]);

        if (false === $parsedDate) {
            return $tag[1];
        }

        $date = $this->framework->getAdapter(Date::class);

        return $date->parse($this->getDateFormat($tag[3]), $parsedDate->getTimestamp());
    }

    /**
     * Returns the configured date format for either "date", "datim" or "time"
     * from either the current page's or the system's settings.
     */
    private function getDateFormat(string $dateFormat): string
    {
        if (!\in_array($dateFormat, ['date', 'datim', 'time'], true)) {
            return $dateFormat;
        }

        $key = $dateFormat.'Format';

        if (null !== ($request = $this->requestStack->getCurrentRequest())) {
            $attributes = $request->attributes;

            if ($attributes->has('pageModel') && ($page = $attributes->get('pageModel')) instanceof PageModel) {
                return $page->{$key};
            }
        }

        return $this->framework->getAdapter(Config::class)->get($key);
    }
}
