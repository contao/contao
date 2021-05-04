<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Fixtures\Routing;

use Contao\CoreBundle\Routing\ResponseContext\Factory\Provider\ResponseContextProviderInterface;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContextInterface;

class FooResponseContext implements ResponseContextProviderInterface
{
    public function supports(string $responseContextClassName): bool
    {
        throw new \RuntimeException('not implemented');
    }

    public function create(string $responseContextClassName): ResponseContextInterface
    {
        throw new \RuntimeException('not implemented');
    }
}
