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
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Inheritance\TemplateHierarchyInterface;
use Contao\CoreBundle\Twig\Inspector\Inspector;
use Contao\CoreBundle\Twig\Inspector\TemplateInformation;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoaderWarmer;
use Contao\CoreBundle\Twig\Loader\ThemeNamespace;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Terminal;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Path;
use Twig\Source;

class DebugContaoTwigCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->resetStaticProperties([Table::class, Terminal::class]);

        parent::tearDown();
    }

    public function testNameAndArguments(): void
    {
        $command = $this->getCommand();

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

        $command = $this->getCommand(null, $cacheWarmer);

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

        $command = $this->getCommand($hierarchy);

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

                foo
                ===
                 --------------- --------------------------------------------------
                  Attribute       Value
                 --------------- --------------------------------------------------
                  Original name   @A/foo.html.twig
                  @Contao name    @Contao/foo.html.twig
                  Path            /path1/foo.html.twig
                  Blocks          @A/foo.html.twig_block1, @A/foo.html.twig_block2
                  Preview         … code of @A/foo.html.twig …
                 --------------- --------------------------------------------------
                  Original name   @B/foo.html5
                  @Contao name    @Contao/foo.html.twig
                  Path            /path2/foo.html5
                  Blocks          @B/foo.html5_block1, @B/foo.html5_block2
                 --------------- --------------------------------------------------
                bar
                ===
                 --------------- --------------------------------------------------
                  Attribute       Value
                 --------------- --------------------------------------------------
                  Original name   @C/bar.html.twig
                  @Contao name    @Contao/bar.html.twig
                  Path            /path/bar.html.twig
                  Blocks          @C/bar.html.twig_block1, @C/bar.html.twig_block2
                  Preview         … code of @C/bar.html.twig …
                 --------------- --------------------------------------------------
                baz
                ===
                 --------------- ------------------------------------------
                  Attribute       Value
                 --------------- ------------------------------------------
                  Original name   @D/baz.html5
                  @Contao name    @Contao/baz.html.twig
                  Path            /path/baz.html5
                  Blocks          @D/baz.html5_block1, @D/baz.html5_block2
                 --------------- ------------------------------------------

                OUTPUT,
        ];

        yield 'filter by full word' => [
            ['filter' => 'foo'],
            <<<'OUTPUT'

                foo
                ===
                 --------------- --------------------------------------------------
                  Attribute       Value
                 --------------- --------------------------------------------------
                  Original name   @A/foo.html.twig
                  @Contao name    @Contao/foo.html.twig
                  Path            /path1/foo.html.twig
                  Blocks          @A/foo.html.twig_block1, @A/foo.html.twig_block2
                  Preview         … code of @A/foo.html.twig …
                 --------------- --------------------------------------------------
                  Original name   @B/foo.html5
                  @Contao name    @Contao/foo.html.twig
                  Path            /path2/foo.html5
                  Blocks          @B/foo.html5_block1, @B/foo.html5_block2
                 --------------- --------------------------------------------------

                OUTPUT,
        ];

        yield 'filter by prefix' => [
            ['filter' => 'ba'],
            <<<'OUTPUT'

                bar
                ===
                 --------------- --------------------------------------------------
                  Attribute       Value
                 --------------- --------------------------------------------------
                  Original name   @C/bar.html.twig
                  @Contao name    @Contao/bar.html.twig
                  Path            /path/bar.html.twig
                  Blocks          @C/bar.html.twig_block1, @C/bar.html.twig_block2
                  Preview         … code of @C/bar.html.twig …
                 --------------- --------------------------------------------------
                baz
                ===
                 --------------- ------------------------------------------
                  Attribute       Value
                 --------------- ------------------------------------------
                  Original name   @D/baz.html5
                  @Contao name    @Contao/baz.html.twig
                  Path            /path/baz.html5
                  Blocks          @D/baz.html5_block1, @D/baz.html5_block2
                 --------------- ------------------------------------------

                OUTPUT,
        ];
    }

    public function testOutputsHierarchyAsATree(): void
    {
        $hierarchy = $this->createMock(TemplateHierarchyInterface::class);
        $hierarchy
            ->expects($this->once())
            ->method('getInheritanceChains')
            ->willReturn([
                'content_element/text/info' => [
                    '/path1/content_element/text/info.html.twig' => '@A/content_element/text/info.html.twig',
                ],
                'content_element/text/highlight' => [
                    '/path1/content_element/text/highlight.html.twig' => '@A/content_element/text/highlight.html.twig',
                    '/path2/content_element/text/highlight.html.twig' => '@B/content_element/text/highlight.html.twig',
                ],
                'content_element/text' => [
                    '/path1/content_element/text.html.twig' => '@A/content_element/text.html.twig',
                ],
            ])
        ;

        $command = $this->getCommand($hierarchy);

        $tester = new CommandTester($command);
        $tester->execute(['--tree' => true]);

        $normalizedOutput = preg_replace("/\\s+\n/", "\n", $tester->getDisplay(true));

        $expectedOutput = <<<'OUTPUT'
            └──content_element
               └──text (@Contao/content_element/text.html.twig)
                  ├──/path1/content_element/text.html.twig
                  │  Original name: @A/content_element/text.html.twig
                  ├──highlight (@Contao/content_element/text/highlight.html.twig)
                  │  ├──/path1/content_element/text/highlight.html.twig
                  │  │  Original name: @A/content_element/text/highlight.html.twig
                  │  └──/path2/content_element/text/highlight.html.twig
                  │     Original name: @B/content_element/text/highlight.html.twig
                  └──info (@Contao/content_element/text/info.html.twig)
                     └──/path1/content_element/text/info.html.twig
                        Original name: @A/content_element/text/info.html.twig

            OUTPUT;

        $this->assertSame($expectedOutput, $normalizedOutput);
        $this->assertSame(0, $tester->getStatusCode());
    }

    /**
     * @dataProvider provideThemeOptions
     */
    public function testIncludesThemeTemplates(array $input, string|null $expectedThemeSlug): void
    {
        $hierarchy = $this->createMock(TemplateHierarchyInterface::class);
        $hierarchy
            ->expects($this->once())
            ->method('getInheritanceChains')
            ->with($expectedThemeSlug)
            ->willReturn([])
        ;

        $command = $this->getCommand($hierarchy);

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

        yield 'theme slug' => [
            ['--theme' => 'my_theme'],
            'my_theme',
        ];

        yield 'theme path' => [
            ['--theme' => 'my/theme'],
            'my_theme',
        ];

        yield 'theme path (relative up)' => [
            ['--theme' => '../themes/foo'],
            '_themes_foo',
        ];
    }

    private function getCommand(TemplateHierarchyInterface|null $hierarchy = null, ContaoFilesystemLoaderWarmer|null $cacheWarmer = null): DebugContaoTwigCommand
    {
        $inspector = $this->createMock(Inspector::class);
        $inspector
            ->method('inspectTemplate')
            ->willReturnCallback(
                static fn (string $name): TemplateInformation => new TemplateInformation(
                    new Source("… code of $name …", $name),
                    ["{$name}_block1", "{$name}_block2"],
                ),
            )
        ;

        return new DebugContaoTwigCommand(
            $hierarchy ?? $this->createMock(TemplateHierarchyInterface::class),
            $cacheWarmer ?? $this->createMock(ContaoFilesystemLoaderWarmer::class),
            new ThemeNamespace(),
            Path::canonicalize(__DIR__.'/../Fixtures/Twig/inheritance'),
            $inspector,
        );
    }
}
