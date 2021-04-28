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

class ResponseContextAccessorTest extends TestCase
{
    public function testGettingAndSettingTheResponseContext(): void
    {
        $request = new Request();
        $requestStack = new RequestStack();
        $context = new ResponseContext();
        $accessor = new ResponseContextAccessor($requestStack);

        $this->assertNull($accessor->getResponseContext());
        $this->assertSame($accessor, $accessor->setResponseContext($context));
        $this->assertNull($accessor->getResponseContext());

        $requestStack->push($request);

        $this->assertSame($accessor, $accessor->setResponseContext($context));
        $this->assertSame($context, $accessor->getResponseContext());
    }
}
