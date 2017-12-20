<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Security\Logout;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\CoreBundle\Security\Logout\LogoutHandler;
use Contao\CoreBundle\Tests\TestCase;
use Psr\Log\LoggerInterface;

class LogoutHandlerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $handler = new LogoutHandler(
            $this->createMock(ContaoFrameworkInterface::class),
            $this->createMock(LoggerInterface::class)
        );

        $this->assertInstanceOf('Contao\CoreBundle\Security\Logout\LogoutHandler', $handler);
    }
}
