<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Command;

use Contao\CoreBundle\Command\DoctrineMigrationsDiffCommand;
use PHPUnit\Framework\TestCase;

/**
 * Tests the DoctrineMigrationsDiffCommand class.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class DoctrineMigrationsDiffCommandTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $command = new DoctrineMigrationsDiffCommand();

        $this->assertInstanceOf('Contao\CoreBundle\Command\DoctrineMigrationsDiffCommand', $command);
        $this->assertSame('doctrine:migrations:diff', $command->getName());
    }
}
