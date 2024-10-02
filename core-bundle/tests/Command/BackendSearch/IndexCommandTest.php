<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Command\BackendSearch;

use Contao\CoreBundle\Command\BackendSearch\IndexCommand;
use Contao\CoreBundle\Search\Backend\BackendSearch;
use Contao\CoreBundle\Search\Backend\IndexUpdateConfig\IndexUpdateConfigInterface;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class IndexCommandTest extends TestCase
{
    public function testTriggersUpdateWithoutParameters(): void
    {
        $backendSearch = $this->createMock(BackendSearch::class);
        $backendSearch
            ->expects($this->once())
            ->method('triggerUpdate')
            ->with($this->callback(static fn (IndexUpdateConfigInterface $config): bool => !$config->getUpdateSince()))
        ;

        $command = new IndexCommand($backendSearch);
        $tester = new CommandTester($command);

        $this->assertSame(Command::SUCCESS, $tester->execute([]));
    }

    public function testTriggersUpdateWithUpdateSince(): void
    {
        $backendSearch = $this->createMock(BackendSearch::class);
        $backendSearch
            ->expects($this->once())
            ->method('triggerUpdate')
            ->with($this->callback(static fn (IndexUpdateConfigInterface $config): bool => '01.01.2020' === $config->getUpdateSince()->format('d.m.Y')))
        ;

        $command = new IndexCommand($backendSearch);

        $tester = new CommandTester($command);
        $tester->execute(['--update-since' => '01-01-2020']);
    }
}
