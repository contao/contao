<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\InsertTag\Resolver;

use Contao\Config;
use Contao\CoreBundle\DependencyInjection\Attribute\AsInsertTag;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\InsertTag\InsertTagResult;
use Contao\CoreBundle\InsertTag\ResolvedInsertTag;
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
 */
class FormatDateInsertTag
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly RequestStack $requestStack,
    ) {
    }

    #[AsInsertTag('format_date')]
    public function replaceFormatDate(ResolvedInsertTag $insertTag): InsertTagResult
    {
        $timeParam = $insertTag->getParameters()->get(0);
        $timestamp = is_numeric($timeParam) ? (int) $timeParam : strtotime($timeParam ?? '');

        if (false === $timestamp) {
            return new InsertTagResult($timeParam ?? '');
        }

        $date = $this->framework->getAdapter(Date::class);

        return new InsertTagResult(
            $date->parse($this->getDateFormat($insertTag->getParameters()->get(1) ?? 'datim'), $timestamp),
        );
    }

    #[AsInsertTag('convert_date')]
    public function replaceConvertDate(ResolvedInsertTag $insertTag): InsertTagResult
    {
        $params = $insertTag->getParameters();

        if (3 !== \count($params->all())) {
            return new InsertTagResult('');
        }

        $parsedDate = \DateTime::createFromFormat('!'.$this->getDateFormat($params->get(1)), $params->get(0));

        if (false === $parsedDate) {
            return new InsertTagResult($params->get(0));
        }

        $date = $this->framework->getAdapter(Date::class);

        return new InsertTagResult(
            $date->parse($this->getDateFormat($params->get(2)), $parsedDate->getTimestamp()),
        );
    }

    /**
     * Returns the configured date format for either "date", "datim" or "time" from
     * either the current page's or the system's settings.
     */
    private function getDateFormat(string $dateFormat): string
    {
        if (!\in_array($dateFormat, ['date', 'datim', 'time'], true)) {
            return $dateFormat;
        }

        $key = $dateFormat.'Format';

        if ($request = $this->requestStack->getCurrentRequest()) {
            $attributes = $request->attributes;

            if ($attributes->has('pageModel')) {
                $page = $attributes->get('pageModel');

                if ($page instanceof PageModel) {
                    return $page->{$key};
                }
            }
        }

        return $this->framework->getAdapter(Config::class)->get($key);
    }
}
