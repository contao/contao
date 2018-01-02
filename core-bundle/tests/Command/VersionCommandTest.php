<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Command;

use Contao\CoreBundle\Command\VersionCommand;
use Contao\CoreBundle\Util\PackageUtil;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class VersionCommandTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $command = new VersionCommand('contao:version');

        $this->assertInstanceOf('Contao\CoreBundle\Command\VersionCommand', $command);
        $this->assertSame('contao:version', $command->getName());
    }

    public function testOutputsTheVersionNumber(): void
    {
        $command = new VersionCommand('contao:version');

        $tester = new CommandTester($command);
        $code = $tester->execute([]);

        $this->assertSame(0, $code);
        $this->assertContains(PackageUtil::getVersion('contao/core-bundle'), $tester->getDisplay());
    }
}
