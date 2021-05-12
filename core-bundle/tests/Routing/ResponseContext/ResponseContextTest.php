<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Routing\ResponseContext;

use Contao\CoreBundle\Routing\ResponseContext\ResponseContext;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class ResponseContextTest extends TestCase
{
    public function testHeaderBagIsInitializedCompletelyEmpty(): void
    {
        $context = new ResponseContext();
        $this->assertCount(0, $context->getHeaderBag()->all());
    }
}
