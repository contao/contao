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

use Contao\CoreBundle\Command\DebugContaoTwigCommand;
use Contao\CoreBundle\Twig\Inheritance\TemplateHierarchyInterface;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoaderWarmer;
use Contao\TestCase\ContaoTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class DebugContaoTwigCommandTest extends ContaoTestCase
{
    public function testNameAndArguments(): void
    {
        $command = new DebugContaoTwigCommand(
            $this->createMock(TemplateHierarchyInterface::class),
            $this->createMock(ContaoFilesystemLoaderWarmer::class),
        );

        $this->assertSame('debug:contao-twig', $command->getName());
        $this->assertNotEmpty($command->getDescription());

        $this->assertTrue($command->getDefinition()->hasOption('theme'));
        $this->assertTrue($command->getDefinition()->hasArgument('filter'));
    }

    public function testRefreshesLoader(): void
    {
        $cacheWarmer = $this->createMock(ContaoFilesystemLoaderWarmer::class);
        $cacheWarmer
            ->expects($this->once())
            ->method('refresh')
        ;

        $command = new DebugContaoTwigCommand(
            $this->createMock(TemplateHierarchyInterface::class),
            $cacheWarmer,
        );

        $tester = new CommandTester($command);
        $tester->execute([]);

        $this->assertSame(0, $tester->getStatusCode());
    }

    /**
     * @dataProvider provideInput
     */
    public function testOutputsHierarchy(array $input, string $expectedOutput): void
    {
        $hierarchy = $this->createMock(TemplateHierarchyInterface::class);
        $hierarchy
            ->expects($this->once())
            ->method('getInheritanceChains')
            ->willReturn([
                'foo' => [
                    '/path1/foo.html.twig' => '@A/foo.html.twig',
                    '/path2/foo.html5' => '@B/foo.html5',
                ],
                'bar' => [
                    '/path/bar.html.twig' => '@C/bar.html.twig',
                ],
                'baz' => [
                    '/path/baz.html5' => '@D/baz.html5',
                ],
            ])
        ;

        $command = new DebugContaoTwigCommand(
            $hierarchy,
            $this->createMock(ContaoFilesystemLoaderWarmer::class)
        );

        $tester = new CommandTester($command);
        $tester->execute($input);

        $normalizedOutput = preg_replace("/\\s+\n/", "\n", $tester->getDisplay(true));

        $this->assertSame($expectedOutput, $normalizedOutput);
        $this->assertSame(0, $tester->getStatusCode());
    }

    public function provideInput(): \Generator
    {
        yield 'no filter' => [
            [],
            <<<'OUTPUT'

                Template hierarchy
                ==================
                 ------------ ------------------------ ----------------------
                  Identifier   Effective logical name   Path
                 ------------ ------------------------ ----------------------
                  foo          @A/foo.html.twig         /path1/foo.html.twig
                               @B/foo.html5             /path2/foo.html5
                 ------------ ------------------------ ----------------------
                  bar          @C/bar.html.twig         /path/bar.html.twig
                 ------------ ------------------------ ----------------------
                  baz          @D/baz.html5             /path/baz.html5
                 ------------ ------------------------ ----------------------

                OUTPUT
        ];

        yield 'filter by full word' => [
            ['filter' => 'foo'],
            <<<'OUTPUT'

                Template hierarchy
                ==================
                 ------------ ------------------------ ----------------------
                  Identifier   Effective logical name   Path
                 ------------ ------------------------ ----------------------
                  foo          @A/foo.html.twig         /path1/foo.html.twig
                               @B/foo.html5             /path2/foo.html5
                 ------------ ------------------------ ----------------------

                OUTPUT
        ];

        yield 'filter by prefix' => [
            ['filter' => 'ba'],
            <<<'OUTPUT'

                Template hierarchy
                ==================
                 ------------ ------------------------ ---------------------
                  Identifier   Effective logical name   Path
                 ------------ ------------------------ ---------------------
                  bar          @C/bar.html.twig         /path/bar.html.twig
                 ------------ ------------------------ ---------------------
                  baz          @D/baz.html5             /path/baz.html5
                 ------------ ------------------------ ---------------------

                OUTPUT
        ];
    }

    /**
     * @dataProvider provideThemeOptions
     */
    public function testIncludesThemeTemplates(array $input, ?string $expectedThemeAlias): void
    {
        $hierarchy = $this->createMock(TemplateHierarchyInterface::class);
        $hierarchy
            ->expects($this->once())
            ->method('getInheritanceChains')
            ->with($expectedThemeAlias)
            ->willReturn([])
        ;

        $command = new DebugContaoTwigCommand(
            $hierarchy,
            $this->createMock(ContaoFilesystemLoaderWarmer::class)
        );

        $tester = new CommandTester($command);
        $tester->execute($input);

        $this->assertSame(0, $tester->getStatusCode());
    }

    public function provideThemeOptions(): \Generator
    {
        yield 'no theme' => [
            [],
            null,
        ];

        yield 'theme alias' => [
            ['--theme' => 'foo_bar'],
            'foo_bar',
        ];

        yield 'theme path' => [
            ['--theme' => 'path/to/theme'],
            'path_to_theme',
        ];

        yield 'theme path (non normalized)' => [
            ['--theme' => 'path\to\theme'],
            'path_to_theme',
        ];
    }
}
