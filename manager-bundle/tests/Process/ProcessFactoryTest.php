<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Tests\Process;

use Contao\ManagerBundle\Process\ProcessFactory;
use Contao\TestCase\ContaoTestCase;

class ProcessFactoryTest extends ContaoTestCase
{
    public function testCreatesCommand(): void
    {
        $process = (new ProcessFactory())->create(['command'], 'cwd', ['env'], 'input', 100);

        $this->assertStringContainsString('command', $process->getCommandLine());
        $this->assertSame('cwd', $process->getWorkingDirectory());
        $this->assertSame(['env'], $process->getEnv());
        $this->assertSame('input', $process->getInput());
        $this->assertSame(100.0, $process->getTimeout());
    }
}
