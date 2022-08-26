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

use Contao\CoreBundle\Command\DebugFragmentsCommand;
use Contao\CoreBundle\Controller\FrontendModule\TwoFactorController;
use Contao\CoreBundle\Fixtures\Controller\FrontendModule\TestController;
use Contao\CoreBundle\Fragment\FragmentConfig;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Terminal;
use Symfony\Component\Console\Tester\CommandTester;

class DebugFragmentsCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->resetStaticProperties([Table::class, Terminal::class]);

        parent::tearDown();
    }

    public function testNameAndArguments(): void
    {
        $command = new DebugFragmentsCommand();

        $this->assertSame('debug:fragments', $command->getName());
        $this->assertSame(0, $command->getDefinition()->getArgumentCount());
        $this->assertEmpty($command->getDefinition()->getOptions());
    }

    /**
     * @dataProvider commandOutputProvider
     */
    public function testCommandOutput(array $fragments, string $expectedOutput): void
    {
        $command = new DebugFragmentsCommand();

        foreach ($fragments as $fragment) {
            $command->add($fragment[0], $fragment[1], $fragment[2]);
        }

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertSame($expectedOutput, preg_replace('/ +(?=\n)/', '', $commandTester->getDisplay(true)));
    }

    public function commandOutputProvider(): \Generator
    {
        yield 'Basic fragment list' => [
            [
                ['contao.foo.bar', new FragmentConfig(TwoFactorController::class), []],
            ],
            <<<'OUTPUT'

                Contao Fragments
                ================

                 ---------------- ----------------------------------------------------------------- ---------- ---------------- ------------------
                  Identifier       Controller                                                        Renderer   Render Options   Fragment Options
                 ---------------- ----------------------------------------------------------------- ---------- ---------------- ------------------
                  contao.foo.bar   Contao\CoreBundle\Controller\FrontendModule\TwoFactorController   forward
                 ---------------- ----------------------------------------------------------------- ---------- ---------------- ------------------


                OUTPUT,
        ];

        yield 'Multiple fragments' => [
            [
                ['contao.foo.bar', new FragmentConfig(TwoFactorController::class), ['category' => 'application']],
                ['contao.foo.baz', new FragmentConfig(TestController::class), ['category' => 'foobar']],
            ],
            <<<'OUTPUT'

                Contao Fragments
                ================

                 ---------------- --------------------------------------------------------------------- ---------- ---------------- ------------------------
                  Identifier       Controller                                                            Renderer   Render Options   Fragment Options
                 ---------------- --------------------------------------------------------------------- ---------- ---------------- ------------------------
                  contao.foo.bar   Contao\CoreBundle\Controller\FrontendModule\TwoFactorController       forward                     category : application
                  contao.foo.baz   Contao\CoreBundle\Fixtures\Controller\FrontendModule\TestController   forward                     category : foobar
                 ---------------- --------------------------------------------------------------------- ---------- ---------------- ------------------------


                OUTPUT,
        ];

        yield 'ESI fragment' => [
            [
                ['contao.foo.bar', new FragmentConfig(TestController::class, 'esi', ['ignore_errors' => false]), ['category' => 'esi', 'foo' => 'bar']],
            ],
            <<<'OUTPUT'

                Contao Fragments
                ================

                 ---------------- --------------------------------------------------------------------- ---------- ----------------------- ------------------
                  Identifier       Controller                                                            Renderer   Render Options          Fragment Options
                 ---------------- --------------------------------------------------------------------- ---------- ----------------------- ------------------
                  contao.foo.bar   Contao\CoreBundle\Fixtures\Controller\FrontendModule\TestController   esi        ignore_errors : false   category : esi
                                                                                                                                            foo      : bar
                 ---------------- --------------------------------------------------------------------- ---------- ----------------------- ------------------


                OUTPUT,
        ];
    }
}
