<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\Test\Composer;

use Contao\ManagerBundle\Composer\ScriptHandler;

/**
 * Tests the ScriptHandler class.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class ScriptHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $this->assertInstanceOf('Contao\ManagerBundle\Composer\ScriptHandler', new ScriptHandler());
    }

    public function testInitializeApplicationMethodExists()
    {
        $this->assertTrue(method_exists(ScriptHandler::class, 'initializeApplication'));
    }

    public function testAddAppDirectory()
    {
        ScriptHandler::addAppDirectory();
    }
}
