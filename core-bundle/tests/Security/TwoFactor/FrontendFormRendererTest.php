<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Security\TwoFactor;

use Contao\CoreBundle\Security\TwoFactor\FrontendFormRenderer;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

class FrontendFormRendererTest extends TestCase
{
    public function testRedirectsOnRenderFormCall(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('generate')
            ->with('contao_frontend_two_factor')
            ->willReturn('/_contao/two-factor')
        ;

        $renderer = new FrontendFormRenderer($router);
        $response = $renderer->renderForm($this->createMock(Request::class), []);

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }
}
