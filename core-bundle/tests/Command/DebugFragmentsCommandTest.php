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
use Contao\CoreBundle\Fragment\FragmentOptionsAwareInterface;
use Contao\CoreBundle\Fragment\FragmentRegistry;
use Contao\CoreBundle\Tests\TestCase;
use Contao\ModuleArticle;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Terminal;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DebugFragmentsCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->resetStaticProperties([Table::class, Terminal::class]);

        parent::tearDown();
    }

    public function testNameAndArguments(): void
    {
        $command = new DebugFragmentsCommand(
            $this->createMock(FragmentRegistry::class),
            $this->createMock(ContainerInterface::class),
        );

        $this->assertSame('debug:fragments', $command->getName());
        $this->assertSame(0, $command->getDefinition()->getArgumentCount());
        $this->assertEmpty($command->getDefinition()->getOptions());
    }

    /**
     * @dataProvider commandOutputProvider
     */
    public function testCommandOutput(array $fragments, string $expectedOutput): void
    {
        $fragmentsRegistry = new FragmentRegistry();
        $container = new ContainerBuilder();

        foreach ($fragments as [$id, $config, $options]) {
            $fragmentsRegistry->add($id, $config);

            /** @var FragmentOptionsAwareInterface $instance */
            $instance = (new \ReflectionClass($config->getController()))->newInstanceWithoutConstructor();

            if ($instance instanceof FragmentOptionsAwareInterface) {
                $instance->setFragmentOptions($options);
            }

            $container->set($config->getController(), $instance);
        }

        $command = new DebugFragmentsCommand($fragmentsRegistry, $container);

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertSame($expectedOutput, preg_replace('/ +(?=\n)/', '', $commandTester->getDisplay(true)));
    }

    public static function commandOutputProvider(): iterable
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

        yield 'Fragment with options' => [
            [
                ['contao.foo.bar', new FragmentConfig(TestController::class), ['category' => 'test', 'foo' => ['bar', 'baz']]],
            ],
            <<<'OUTPUT'

                Contao Fragments
                ================

                 ---------------- --------------------------------------------------------------------- ---------- ---------------- ---------------------
                  Identifier       Controller                                                            Renderer   Render Options   Fragment Options
                 ---------------- --------------------------------------------------------------------- ---------- ---------------- ---------------------
                  contao.foo.bar   Contao\CoreBundle\Fixtures\Controller\FrontendModule\TestController   forward                     category : test
                                                                                                                                     foo      : bar, baz
                 ---------------- --------------------------------------------------------------------- ---------- ---------------- ---------------------


                OUTPUT,
        ];

        yield 'Nested fragment' => [
            [
                ['contao.foo.bar', new FragmentConfig(TestController::class), ['category' => 'test', 'nestedFragments' => ['allowedTypes' => ['alias', 'link']]]],
            ],
            <<<'OUTPUT'

                Contao Fragments
                ================

                 ---------------- --------------------------------------------------------------------- ---------- ---------------- ---------------------------------
                  Identifier       Controller                                                            Renderer   Render Options   Fragment Options
                 ---------------- --------------------------------------------------------------------- ---------- ---------------- ---------------------------------
                  contao.foo.bar   Contao\CoreBundle\Fixtures\Controller\FrontendModule\TestController   forward                     category        : test
                                                                                                                                     nestedFragments : allowedTypes:
                                                                                                                                                         - alias
                                                                                                                                                         - link

                 ---------------- --------------------------------------------------------------------- ---------- ---------------- ---------------------------------


                OUTPUT,
        ];

        yield 'Legacy modules' => [
            [
                ['contao.foo.bar', new FragmentConfig(ModuleArticle::class), []],
            ],
            <<<'OUTPUT'

                Contao Fragments
                ================

                 ------------ ------------ ---------- ---------------- ------------------
                  Identifier   Controller   Renderer   Render Options   Fragment Options
                 ------------ ------------ ---------- ---------------- ------------------


                OUTPUT,
        ];
    }
}
