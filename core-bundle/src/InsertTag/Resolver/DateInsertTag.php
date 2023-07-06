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

use Contao\CoreBundle\DependencyInjection\Attribute\AsInsertTag;
use Contao\CoreBundle\InsertTag\InsertTagResult;
use Contao\CoreBundle\InsertTag\OutputType;
use Contao\CoreBundle\InsertTag\ResolvedInsertTag;
use Contao\Date;

#[AsInsertTag('date', asFragment: true)]
class DateInsertTag implements InsertTagResolverNestedResolvedInterface
{
    private const CACHEABLE_FORMAT_CHARACTERS = ['Y', 'm', 'd'];

    public function __invoke(ResolvedInsertTag $insertTag): InsertTagResult
    {
        $format = $insertTag->getParameters()->get(0) ?? $GLOBALS['objPage']->dateFormat ?? $GLOBALS['TL_CONFIG']['dateFormat'] ?? '';
        $result = new InsertTagResult(Date::parse($format), OutputType::text);

        preg_match_all('/['.implode('', self::CACHEABLE_FORMAT_CHARACTERS).']/', $format, $matches);
        $usedFormatChars = $matches[0] ?? [];
        sort($usedFormatChars);

        // Add caching headers for supported formats
        if (0 === \count(array_diff($usedFormatChars, self::CACHEABLE_FORMAT_CHARACTERS))) {

            switch (true) {
                case $usedFormatChars === ['Y', 'd', 'm']:
                    $result = $result->withExpiresAt(new \DateTimeImmutable('today 23:59:59'));
                    break;
                case $usedFormatChars === ['Y', 'm']:
                    $result = $result->withExpiresAt(new \DateTimeImmutable('last day of this month 23:59:59'));
                    break;
                case $usedFormatChars === ['Y']:
                    $result = $result->withExpiresAt(new \DateTimeImmutable('last day of December this year 23:59:59'));
                    break;
            }

            if ($rootId = $GLOBALS['objPage']->rootId ?? null) {
                $result = $result->withCacheTags(["contao.db.tl_page.$rootId"]);
            }
        }

        return $result;
    }
}
