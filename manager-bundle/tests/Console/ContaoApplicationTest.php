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

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\Tests\TestCase;
use Contao\ManagerBundle\Console\ContaoApplication;
use Contao\ManagerBundle\HttpKernel\ContaoKernel;
use Symfony\Component\Console\Input\ArgvInput;

class ContaoApplicationTest extends TestCase
{
    private $globalsBackup;

    protected function setUp(): void
    {
        parent::setUp();

        $this->globalsBackup['_SERVER'] = $_SERVER ?? null;
        $this->globalsBackup['_ENV'] = $_ENV ?? null;
    }

    protected function tearDown(): void
    {
        foreach ($this->globalsBackup as $key => $value) {
            if (null === $value) {
                unset($GLOBALS[$key]);
            } else {
                $GLOBALS[$key] = $value;
            }
        }

        parent::tearDown();
    }

    public function testApplicationNameAndVersion(): void
    {
        $app = new ContaoApplication(ContaoKernel::fromInput($this->getTempDir(), new ArgvInput()));

        $this->assertSame('Contao Managed Edition', $app->getName());
        $this->assertSame(ContaoCoreBundle::getVersion(), $app->getVersion());
    }

    public function testDoesNotHaveNoDebugOption(): void
    {
        $app = new ContaoApplication(ContaoKernel::fromInput($this->getTempDir(), new ArgvInput()));
        $options = $app->getDefinition()->getOptions();

        $this->assertArrayNotHasKey('no-debug', $options);
    }
}
