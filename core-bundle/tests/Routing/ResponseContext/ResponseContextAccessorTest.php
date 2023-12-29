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
use Contao\CoreBundle\Routing\ResponseContext\ResponseContextAccessor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class ResponseContextAccessorTest extends TestCase
{
    public function testGettingAndSettingTheResponseContext(): void
    {
        $requestStack = new RequestStack();
        $context = new ResponseContext();
        $accessor = new ResponseContextAccessor($requestStack);

        $this->assertNull($accessor->getResponseContext());
        $this->assertSame($accessor, $accessor->setResponseContext($context));
        $this->assertNull($accessor->getResponseContext());

        $requestStack->push(new Request());

        $this->assertSame($accessor, $accessor->setResponseContext($context));
        $this->assertSame($context, $accessor->getResponseContext());
    }

    public function testFinalizing(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $accessor = new ResponseContextAccessor($requestStack);

        $responseContext = new ResponseContext();
        $responseContext->getHeaderBag()->set('Foo', 'Bar');

        $accessor->setResponseContext($responseContext);

        $this->assertNotNull($accessor->getResponseContext());

        $response = new Response();
        $accessor->finalizeCurrentContext($response);

        $this->assertSame('Bar', $response->headers->get('Foo'));
        $this->assertNull($accessor->getResponseContext());
    }
}
