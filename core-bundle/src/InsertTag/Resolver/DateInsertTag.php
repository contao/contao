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

        preg_match_all('/['.implode('', Date::SUPPORTED_FORMAT_CHARACTERS).']/', $format, $matches);
        $usedFormatChars = array_unique($matches[0] ?? []);
        sort($usedFormatChars);

        // Add caching headers for supported formats
        $result = $result->withExpiresAt(match ($usedFormatChars) {
            ['Y', 'd', 'm'] => new \DateTimeImmutable('today 23:59:59'),
            ['Y', 'm'] => new \DateTimeImmutable('last day of this month 23:59:59'),
            ['Y'] => new \DateTimeImmutable('last day of December this year 23:59:59'),
            default => null,
        });

        if (null !== $result->getExpiresAt() && ($rootId = $GLOBALS['objPage']->rootId ?? null)) {
            $result = $result->withCacheTags(["contao.db.tl_page.$rootId"]);
        }

        return $result;
    }
}
