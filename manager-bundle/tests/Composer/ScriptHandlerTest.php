<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Tests\Composer;

use Contao\ManagerBundle\Composer\ScriptHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class ScriptHandlerTest extends TestCase
{
    public function testInstantiation(): void
    {
        $this->assertInstanceOf('Contao\ManagerBundle\Composer\ScriptHandler', new ScriptHandler());
    }

    public function testInitializeApplicationMethodExists(): void
    {
        $this->assertTrue(method_exists(ScriptHandler::class, 'initializeApplication'));
    }

    public function testAddAppDirectory(): void
    {
        ScriptHandler::addAppDirectory();

        $this->assertDirectoryExists(getcwd().'/app');

        (new Filesystem())->remove(getcwd().'/app');
    }
}
