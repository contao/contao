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

use Contao\CoreBundle\Command\VersionCommand;
use Contao\CoreBundle\ContaoCoreBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class VersionCommandTest extends TestCase
{
    public function testOutputsTheVersionNumber(): void
    {
        $command = new VersionCommand('contao:version');

        $tester = new CommandTester($command);
        $code = $tester->execute([]);

        $this->assertSame(0, $code);
        $this->assertStringContainsString(ContaoCoreBundle::getVersion(), $tester->getDisplay());
    }
}
