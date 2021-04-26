<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Routing\ResponseContext\Factory\Provider;

use Contao\CoreBundle\Routing\ResponseContext\ResponseContext;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContextInterface;
use Contao\CoreBundle\Routing\ResponseContext\WebpageContext;

class CoreProvider implements ResponseContextProviderInterface
{
    public function supports(string $responseContextClassName): bool
    {
        return \in_array($responseContextClassName, [
            ResponseContext::class,
            WebpageContext::class,
        ], true);
    }

    public function create(string $responseContextClassName): ResponseContextInterface
    {
        return new $responseContextClassName();
    }
}
