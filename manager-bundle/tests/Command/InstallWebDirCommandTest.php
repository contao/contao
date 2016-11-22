<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\Test\Command;

use Contao\ManagerBundle\Command\InstallWebDirCommand;

/**
 * Tests the InstallWebDirCommand class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class InstallWebDirCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var InstallWebDirCommand
     */
    private $command;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->command = new InstallWebDirCommand('contao:install-web-dir');
    }
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $this->assertInstanceOf('Contao\ManagerBundle\Command\InstallWebDirCommand', $this->command);
    }

    /**
     * Tests the command name.
     */
    public function testName()
    {
        $this->assertEquals('contao:install-web-dir', $this->command->getName());
    }
}
