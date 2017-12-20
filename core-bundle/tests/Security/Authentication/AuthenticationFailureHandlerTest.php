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

use Contao\CoreBundle\Security\Authentication\AuthenticationFailureHandler;
use Contao\CoreBundle\Tests\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\Security\Http\HttpUtils;

class AuthenticationFailureHandlerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $handler = $this->mockFailureHandler();

        $this->assertInstanceOf('Contao\CoreBundle\Security\Authentication\AuthenticationFailureHandler', $handler);
    }

    /**
     * Mocks an authentication failure handler.
     *
     * @param HttpUtils|null $utils
     *
     * @return AuthenticationFailureHandler
     */
    private function mockFailureHandler(HttpUtils $utils = null): AuthenticationFailureHandler
    {
        $kernel = $this->createMock(HttpKernel::class);

        if (null === $utils) {
            $utils = $this->createMock(HttpUtils::class);
        }

        $logger = $this->createMock(LoggerInterface::class);

        return new AuthenticationFailureHandler($kernel, $utils, [], $logger);
    }
}
