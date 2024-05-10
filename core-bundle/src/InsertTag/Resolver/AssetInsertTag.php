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
use Symfony\Component\Asset\Packages;

#[AsInsertTag('asset')]
class AssetInsertTag implements InsertTagResolverNestedResolvedInterface
{
    public function __construct(private readonly Packages $packages)
    {
    }

    public function __invoke(ResolvedInsertTag $insertTag): InsertTagResult
    {
        return new InsertTagResult(
            $this->packages->getUrl($insertTag->getParameters()->get(0), $insertTag->getParameters()->get(1)),
            OutputType::url,
        );
    }
}
