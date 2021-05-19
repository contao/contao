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
    public function testFinalizingTheContext(): void
    {
        $context = new ResponseContext();
        $context->getHeaderBag()->set('Content-Type', 'application/json');

        $response = new Response();
        $context->finalize($response);

        $this->assertSame('application/json', $response->headers->get('Content-Type'));
    }

    public function testHeaderBagIsInitializedCompletelyEmpty(): void
    {
        $context = new ResponseContext();

        $this->assertCount(0, $context->getHeaderBag()->all());
    }
}
