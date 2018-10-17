<?php

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

/**
 * Tests the ScriptHandler class.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class ScriptHandlerTest extends TestCase
{
    /**
     * Tests that the initializeApplication() method exists.
     */
    public function testInitializeApplicationMethodExists()
    {
        $this->assertTrue(method_exists(ScriptHandler::class, 'initializeApplication'));
    }

    /**
     * Tests adding the app directory.
     */
    public function testAddAppDirectory()
    {
        ScriptHandler::addAppDirectory();

        (new Filesystem())->remove(getcwd().'/app');
    }
}
