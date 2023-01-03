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

use Contao\CoreBundle\Command\FilesyncCommand;
use Contao\CoreBundle\Filesystem\Dbafs\ChangeSet\ChangeSet;
use Contao\CoreBundle\Filesystem\Dbafs\DbafsManager;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Bridge\PhpUnit\ClockMock;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Terminal;
use Symfony\Component\Console\Tester\CommandTester;

class FilesyncCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->resetStaticProperties([Table::class, Terminal::class]);

        parent::tearDown();
    }

    public function testNameAndArguments(): void
    {
        $command = $this->getCommand();

        $this->assertSame('contao:filesync', $command->getName());
        $this->assertNotEmpty($command->getDescription());

        $this->assertTrue($command->getDefinition()->hasArgument('paths'));
        $this->assertFalse($command->getDefinition()->getArgument('paths')->isRequired());
    }

    /**
     * @dataProvider provideInputs
     */
    public function testDelegatesArgumentsToDbafsManager(array $input): void
    {
        $manager = $this->createMock(DbafsManager::class);
        $manager
            ->expects($this->once())
            ->method('sync')
            ->with(...$input)
            ->willReturn(ChangeSet::createEmpty())
        ;

        $command = $this->getCommand($manager);

        $tester = new CommandTester($command);
        $tester->execute(['paths' => $input]);

        $this->assertSame(0, $tester->getStatusCode());
    }

    public function testRenderStats(): void
    {
        ClockMock::withClockMock(true);

        $changeSet = new ChangeSet(
            [
                [
                    ChangeSet::ATTR_HASH => '5493611ba7d91b0ee8e0f893f6bf837e',
                    ChangeSet::ATTR_PATH => 'foo/new1',
                    ChangeSet::ATTR_TYPE => ChangeSet::TYPE_FILE,
                ],
                [
                    ChangeSet::ATTR_HASH => '802ffec476939b66450caf0140bee49e',
                    ChangeSet::ATTR_PATH => 'foo/new2',
                    ChangeSet::ATTR_TYPE => ChangeSet::TYPE_DIRECTORY,
                ],
            ],
            [
                'bar/old_path' => [
                    ChangeSet::ATTR_PATH => 'bar/updated_path',
                ],
                'bar/file_that_changes' => [
                    ChangeSet::ATTR_HASH => '8a1631a4eacf47253f3ebb5aea2ccce7',
                ],
            ],
            [
                'baz' => ChangeSet::TYPE_DIRECTORY,
                'baz/deleted1' => ChangeSet::TYPE_FILE,
                'baz/deleted2' => ChangeSet::TYPE_FILE,
            ]
        );

        $manager = $this->createMock(DbafsManager::class);
        $manager
            ->expects($this->once())
            ->method('sync')
            ->willReturnCallback(
                static function () use ($changeSet) {
                    ClockMock::sleep(3);

                    return $changeSet;
                }
            )
        ;

        $command = $this->getCommand($manager);

        $expectedOutput =
            <<<'OUTPUT'
                Synchronizing…
                +--------+------------------------------------------------------------------------+
                | Action | Resource / Change                                                      |
                +--------+------------------------------------------------------------------------+
                | add    | foo/new1 (new hash: 5493611ba7d91b0ee8e0f893f6bf837e)                  |
                | add    | foo/new2 (new hash: 802ffec476939b66450caf0140bee49e)                  |
                | move   | bar/old_path → bar/updated_path                                        |
                | update | bar/file_that_changes (updated hash: 8a1631a4eacf47253f3ebb5aea2ccce7) |
                | delete | baz                                                                    |
                | delete | baz/deleted1                                                           |
                | delete | baz/deleted2                                                           |
                +--------+------------------------------------------------------------------------+
                 Total items added: 2 | updated/moved: 2 | deleted: 3
                 [OK] Synchronization complete in 3s.

                OUTPUT;

        $tester = new CommandTester($command);
        $tester->execute([]);

        $normalizedOutput = preg_replace("/\\s+\n/", "\n", $tester->getDisplay(true));

        $this->assertSame($expectedOutput, $normalizedOutput);
        $this->assertSame(0, $tester->getStatusCode());
    }

    public function provideInputs(): \Generator
    {
        yield 'no input' => [
            [],
        ];

        yield 'single argument' => [
            ['foo/**'],
        ];

        yield 'multiple arguments' => [
            ['foo/**', 'bar', 'baz/*'],
        ];
    }

    private function getCommand(DbafsManager $manager = null): FilesyncCommand
    {
        return new FilesyncCommand($manager ?? $this->createMock(DbafsManager::class));
    }
}
