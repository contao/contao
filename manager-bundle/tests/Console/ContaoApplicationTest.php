<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Tests\Console;

use Contao\CoreBundle\Util\PackageUtil;
use Contao\ManagerBundle\Console\ContaoApplication;
use Contao\ManagerBundle\HttpKernel\ContaoKernel;
use Contao\TestCase\ContaoTestCase;
use Symfony\Component\HttpFoundation\Request;

class ContaoApplicationTest extends ContaoTestCase
{
    public function testApplicationNameAndVersion(): void
    {
        $app = new ContaoApplication(ContaoKernel::fromRequest(sys_get_temp_dir(), Request::create('/')));

        $this->assertSame('Contao Managed Edition', $app->getName());
        $this->assertSame(PackageUtil::getContaoVersion(), $app->getVersion());
    }

    public function testDoesNotHaveNoDebugOption(): void
    {
        $app = new ContaoApplication(ContaoKernel::fromRequest(sys_get_temp_dir(), Request::create('/')));

        $options = $app->getDefinition()->getOptions();

        $this->assertArrayNotHasKey('no-debug', $options);
    }
}
