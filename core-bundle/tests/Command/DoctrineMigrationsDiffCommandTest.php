<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Command;

use Contao\CoreBundle\Command\DoctrineMigrationsDiffCommand;

/**
 * Tests the DoctrineMigrationsDiffCommand class.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class DoctrineMigrationsDiffCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $command = new DoctrineMigrationsDiffCommand();

        $this->assertInstanceOf('Contao\CoreBundle\Command\DoctrineMigrationsDiffCommand', $command);
        $this->assertEquals('doctrine:migrations:diff', $command->getName());
    }
}
