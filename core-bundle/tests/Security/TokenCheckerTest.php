<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Security;

use Contao\CoreBundle\Security\TokenChecker;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolverInterface;

class TokenCheckerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $tokenChecker = new TokenChecker(
            $this->createMock(SessionInterface::class),
            $this->createMock(AuthenticationTrustResolverInterface::class)
        );

        $this->assertInstanceOf('Contao\CoreBundle\Security\TokenChecker', $tokenChecker);
    }
}
