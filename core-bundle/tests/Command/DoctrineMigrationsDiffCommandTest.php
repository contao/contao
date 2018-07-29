<?php

declare(strict_types=1);

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

class DoctrineMigrationsDiffCommandTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $command = new DoctrineMigrationsDiffCommand();

        $this->assertInstanceOf('Contao\CoreBundle\Command\DoctrineMigrationsDiffCommand', $command);
        $this->assertSame('doctrine:migrations:diff', $command->getName());
    }
}
