<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Security\User;

use Contao\CoreBundle\Security\User\UserChecker;
use Contao\CoreBundle\Tests\TestCase;

class UserCheckerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $userChecker = new UserChecker($this->mockContaoFramework());

        $this->assertInstanceOf('Contao\CoreBundle\Security\User\UserChecker', $userChecker);
    }
}
