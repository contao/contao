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

use Contao\CoreBundle\InsertTag\InsertTagResult;
use Contao\CoreBundle\InsertTag\ParsedInsertTag;

interface InsertTagResolverNestedParsedInterface
{
    public function __invoke(ParsedInsertTag $insertTag): InsertTagResult;
}
