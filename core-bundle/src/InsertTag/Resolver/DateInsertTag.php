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

class DateInsertTag
{
    #[AsInsertTag('date', asFragment: true)]
    public function __invoke(ResolvedInsertTag $insertTag): InsertTagResult
    {
        return new InsertTagResult(
            Date::parse($insertTag->getParameters()->get(0) ?? $GLOBALS['objPage']->dateFormat ?? Config::get('dateFormat')),
            OutputType::text,
        );
    }
}
