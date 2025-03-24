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
use Contao\CoreBundle\InsertTag\ParsedInsertTag;

#[AsInsertTag('empty')]
class EmptyInsertTag implements InsertTagResolverNestedParsedInterface
{
    public function __invoke(ParsedInsertTag $insertTag): InsertTagResult
    {
        return new InsertTagResult('', OutputType::text);
    }
}
