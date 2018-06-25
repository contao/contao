<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Tests\Api;

use Contao\ManagerBundle\Api\Application;
use PHPUnit\Framework\TestCase;

class ApplicationTest extends TestCase
{
    public function testInstantiation(): void
    {
        $this->assertInstanceOf('Contao\ManagerBundle\Api\Application', new Application(sys_get_temp_dir()));
    }
}
