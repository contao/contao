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

class FooResponseContext
{
    public function supports(string $responseContextClassName): never
    {
        throw new \RuntimeException('not implemented');
    }

    public function create(string $responseContextClassName): never
    {
        throw new \RuntimeException('not implemented');
    }
}
