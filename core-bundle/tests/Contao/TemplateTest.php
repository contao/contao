<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Contao;

use Contao\BackendTemplate;
use Contao\Config;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FrontendTemplate;
use Contao\System;
use Contao\Template;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\VarDumper\VarDumper;
use Twig\Environment;

class TemplateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        System::setContainer($this->getContainerWithContaoConfiguration());
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_MIME'], $GLOBALS['objPage']);

        $this->resetStaticProperties([System::class, Config::class]);

        parent::tearDown();
    }

    public function testDelegatesRenderingToTwig(): void
    {
        $twig = $this->createMock(Environment::class);
        $twig
            ->expects($this->once())
            ->method('render')
            ->with(
                '@Contao/test_template.html.twig',
                $this->callback(
                    function (array $context) {
                        $this->assertArrayHasKey('foo', $context);
                        $this->assertSame('bar', $context['foo']);

                        return true;
                    },
                ),
            )
            ->willReturn('<output>')
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('twig', $twig);

        System::setContainer($container);

        $template = new BackendTemplate('test_template');
        $template->setData(['foo' => 'bar']);

        $this->assertSame('<output>', $template->parse());
    }

    public function testCanDumpTemplateVars(): void
    {
        $template = new FrontendTemplate();
        $template->setData(['test' => 1]);

        $dump = null;

        VarDumper::setHandler(
            static function ($var) use (&$dump): void {
                $dump = $var;
            },
        );

        $template->dumpTemplateVars();

        $this->assertSame(['test' => 1], $dump);
    }

    #[DataProvider('provideBuffer')]
    public function testCompileReplacesLiteralInsertTags(string $buffer, string $expectedOutput): void
    {
        $page = new \stdClass();
        $page->minifyMarkup = false;

        $GLOBALS['objPage'] = $page;

        $template = new class($buffer) extends FrontendTemplate {
            public function __construct(private readonly string|null $testBuffer)
            {
                parent::__construct();
            }

            public function parse(): string
            {
                return System::getContainer()->get('contao.insert_tag.parser')->replace($this->testBuffer);
            }

            public function testCompile(): string
            {
                $this->getResponse();

                return $this->strBuffer;
            }

            public static function replaceDynamicScriptTags($strBuffer)
            {
                return $strBuffer; // ignore dynamic script tags
            }
        };

        $this->assertSame($expectedOutput, $template->testCompile());

        unset($GLOBALS['objPage']);
    }

    public static function provideBuffer(): iterable
    {
        yield 'plain string' => [
            'foo bar',
            'foo bar',
        ];

        yield 'literal insert tags are replaced' => [
            'foo[{]bar[{]baz[}]',
            'foo&#123;&#123;bar&#123;&#123;baz&#125;&#125;',
        ];

        yield 'literal insert tags inside script tag are not replaced' => [
            '<script type="application/javascript">if (/[\[{]$/.test(foo)) {}</script>',
            '<script type="application/javascript">if (/[\[{]$/.test(foo)) {}</script>',
        ];

        yield 'multiple occurrences' => [
            '[{][}]<script>[{][}]</script>[{][}]<script>[{][}]</script>[{][}]',
            '&#123;&#123;&#125;&#125;<script>[{][}]</script>&#123;&#123;&#125;&#125;<script>[{][}]</script>&#123;&#123;&#125;&#125;',
        ];
    }

    public function testOnceHelperExecutesCodeOnce(): void
    {
        $invocationCount = 0;

        $expensiveFunction = static function () use (&$invocationCount) {
            ++$invocationCount;

            return false;
        };

        $template = new FrontendTemplate();
        $template->hasFoo = Template::once($expensiveFunction);

        $this->assertFalse($template->hasFoo, 'first call');
        $this->assertFalse($template->hasFoo, 'second call');

        $this->assertSame(1, $invocationCount);
    }
}
