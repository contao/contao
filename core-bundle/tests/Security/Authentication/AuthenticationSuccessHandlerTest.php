<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Security\Authentication;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\CoreBundle\Security\Authentication\AuthenticationSuccessHandler;
use Contao\CoreBundle\Tests\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Translation\TranslatorInterface;

class AuthenticationSuccessHandlerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $handler = $this->mockSuccessHandler();

        $this->assertInstanceOf('Contao\CoreBundle\Security\Authentication\AuthenticationSuccessHandler', $handler);
    }

    /**
     * Mocks an authentication success handler.
     *
     * @param HttpUtils|null                $utils
     * @param ContaoFrameworkInterface|null $framework
     *
     * @return AuthenticationSuccessHandler
     */
    private function mockSuccessHandler(HttpUtils $utils = null, ContaoFrameworkInterface $framework = null): AuthenticationSuccessHandler
    {
        if (null === $utils) {
            $utils = $this->createMock(HttpUtils::class);
        }

        if (null === $framework) {
            $framework = $this->mockContaoFramework();
        }

        $translator = $this->createMock(TranslatorInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        return new AuthenticationSuccessHandler($utils, $framework, $translator, $logger);
    }
}
