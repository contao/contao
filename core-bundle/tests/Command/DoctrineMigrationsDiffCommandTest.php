<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
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
    public function testCanBeInstantiated()
    {
        $command = new DoctrineMigrationsDiffCommand();

        $this->assertInstanceOf('Contao\CoreBundle\Command\DoctrineMigrationsDiffCommand', $command);
        $this->assertSame('doctrine:migrations:diff', $command->getName());
    }
}
