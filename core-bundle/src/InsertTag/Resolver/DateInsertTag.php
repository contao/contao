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
use Contao\CoreBundle\InsertTag\InsertTagResult;
use Contao\CoreBundle\InsertTag\OutputType;
use Contao\CoreBundle\InsertTag\ResolvedInsertTag;
use Contao\Date;

#[AsInsertTag('date', asFragment: true)]
class DateInsertTag implements InsertTagResolverNestedResolvedInterface
{
    public function __invoke(ResolvedInsertTag $insertTag): InsertTagResult
    {
        $format = $insertTag->getParameters()->get(0) ?? $GLOBALS['objPage']->dateFormat ?? Config::get('dateFormat');
        $result = new InsertTagResult(Date::parse($format), OutputType::text);

        // Special handling for the very common {{date::Y}} (e.g. in the website footer) case
        if ('Y' === $format) {
            $result = $result->withExpiresAt(new \DateTimeImmutable($result->getValue().'-12-31 23:59:59'));

            if ($rootId = $GLOBALS['objPage']->rootId ?? null) {
                $result = $result->withCacheTags(["contao.db.tl_page.$rootId"]);
            }
        }

        return $result;
    }
}
