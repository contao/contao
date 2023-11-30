<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig\Extension;

use Contao\Config;
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Extension\ContaoExtension;
use Contao\CoreBundle\Twig\Extension\DeprecationsNodeVisitor;
use Contao\CoreBundle\Twig\Inheritance\DynamicExtendsTokenParser;
use Contao\CoreBundle\Twig\Inheritance\DynamicIncludeTokenParser;
use Contao\CoreBundle\Twig\Inheritance\DynamicUseTokenParser;
use Contao\CoreBundle\Twig\Inheritance\TemplateHierarchyInterface;
use Contao\CoreBundle\Twig\Interop\ContaoEscaperNodeVisitor;
use Contao\CoreBundle\Twig\Interop\PhpTemplateProxyNodeVisitor;
use Contao\CoreBundle\Twig\ResponseContext\AddTokenParser;
use Contao\System;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Fragment\FragmentHandler;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Extension\CoreExtension;
use Twig\Extension\EscaperExtension;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\FilterExpression;
use Twig\Node\ModuleNode;
use Twig\Node\Node;
use Twig\Node\TextNode;
use Twig\NodeTraverser;
use Twig\Source;
use Twig\TwigFilter;
use Twig\TwigFunction;

class ContaoExtensionTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_MIME']);

        $this->resetStaticProperties([ContaoFramework::class, System::class, Config::class]);

        parent::tearDown();
    }

    public function testAddsTheNodeVisitors(): void
    {
        $nodeVisitors = $this->getContaoExtension()->getNodeVisitors();

        $this->assertCount(3, $nodeVisitors);

        $this->assertInstanceOf(ContaoEscaperNodeVisitor::class, $nodeVisitors[0]);
        $this->assertInstanceOf(PhpTemplateProxyNodeVisitor::class, $nodeVisitors[1]);
        $this->assertInstanceOf(DeprecationsNodeVisitor::class, $nodeVisitors[2]);
    }

    public function testAddsTheTokenParsers(): void
    {
        $tokenParsers = $this->getContaoExtension()->getTokenParsers();

        $this->assertCount(4, $tokenParsers);

        $this->assertInstanceOf(DynamicExtendsTokenParser::class, $tokenParsers[0]);
        $this->assertInstanceOf(DynamicIncludeTokenParser::class, $tokenParsers[1]);
        $this->assertInstanceOf(DynamicUseTokenParser::class, $tokenParsers[2]);
        $this->assertInstanceOf(AddTokenParser::class, $tokenParsers[3]);
    }

    public function testAddsTheFunctions(): void
    {
        $expectedFunctions = [
            'include' => ['all'],
            'attrs' => [],
            'figure' => [],
            'contao_figure' => ['html'],
            'picture_config' => [],
            'insert_tag' => [],
            'add_schema_org' => [],
            'contao_sections' => ['html'],
            'contao_section' => ['html'],
            'prefix_url' => [],
            'frontend_module' => ['html'],
            'content_element' => ['html'],
        ];

        $functions = $this->getContaoExtension()->getFunctions();

        $this->assertCount(\count($expectedFunctions), $functions);

        $node = $this->createMock(Node::class);

        foreach ($functions as $function) {
            $this->assertInstanceOf(TwigFunction::class, $function);

            $name = $function->getName();
            $this->assertArrayHasKey($name, $expectedFunctions);
            $this->assertSame($expectedFunctions[$name], $function->getSafe($node), $name);
        }
    }

    public function testAddsTheFilters(): void
    {
        $filters = $this->getContaoExtension()->getFilters();

        $expectedFilters = [
            'escape',
            'e',
            'insert_tag',
            'insert_tag_raw',
            'highlight',
            'highlight_auto',
            'format_bytes',
            'sanitize_html',
        ];

        $this->assertCount(\count($expectedFilters), $filters);

        foreach ($filters as $filter) {
            $this->assertInstanceOf(TwigFilter::class, $filter);
            $this->assertContains($filter->getName(), $expectedFilters);
        }
    }

    public function testIncludeFunctionDelegatesToTwigInclude(): void
    {
        $methodCalledException = new \Exception();

        $environment = $this->createMock(Environment::class);
        $environment
            ->expects($this->once())
            ->method('resolveTemplate')
            ->with('@Contao_Bar/foo.html.twig')
            ->willThrowException($methodCalledException)
        ;

        $hierarchy = $this->createMock(TemplateHierarchyInterface::class);
        $hierarchy
            ->method('getFirst')
            ->with('foo')
            ->willReturn('@Contao_Bar/foo.html.twig')
        ;

        $includeFunction = $this->getContaoExtension($environment, $hierarchy)->getFunctions()[0];
        $args = [$environment, [], '@Contao/foo'];

        $this->expectExceptionObject($methodCalledException);

        ($includeFunction->getCallable())(...$args);
    }

    public function testThrowsIfCoreIncludeFunctionIsNotFound(): void
    {
        $environment = $this->createMock(Environment::class);
        $environment
            ->method('getExtension')
            ->willReturnMap([
                [EscaperExtension::class, new EscaperExtension()],
                [CoreExtension::class, new class() extends AbstractExtension {
                }],
            ])
        ;

        $extension = new ContaoExtension(
            $environment,
            $this->createMock(TemplateHierarchyInterface::class),
            $this->createMock(ContaoCsrfTokenManager::class)
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The Twig\Extension\CoreExtension class was expected to register the "include" Twig function but did not.');

        $extension->getFunctions();
    }

    public function testAllowsOnTheFlyRegisteringTemplatesForInputEncoding(): void
    {
        $contaoExtension = $this->getContaoExtension();
        $escaperNodeVisitor = $contaoExtension->getNodeVisitors()[0];

        $traverser = new NodeTraverser(
            $this->createMock(Environment::class),
            [$escaperNodeVisitor]
        );

        $node = new ModuleNode(
            new FilterExpression(
                new TextNode('text', 1),
                new ConstantExpression('escape', 1),
                new Node([
                    new ConstantExpression('html', 1),
                    new ConstantExpression(null, 1),
                    new ConstantExpression(true, 1),
                ]),
                1
            ),
            null,
            new Node(),
            new Node(),
            new Node(),
            null,
            new Source('<code>', 'foo.html.twig')
        );

        $original = (string) $node;

        // Traverse tree first time (no changes expected)
        $traverser->traverse($node);
        $iteration1 = (string) $node;

        // Add rule that allows the template and traverse tree a second time (change expected)
        $contaoExtension->addContaoEscaperRule('/foo\.html\.twig/');

        // Adding the same rule should be ignored
        $contaoExtension->addContaoEscaperRule('/foo\.html\.twig/');

        $traverser->traverse($node);
        $iteration2 = (string) $node;

        $this->assertSame($original, $iteration1);
        $this->assertStringNotContainsString("'contao_html'", $iteration1);
        $this->assertStringContainsString("'contao_html'", $iteration2);
    }

    public function testRenderLegacyTemplate(): void
    {
        $extension = $this->getContaoExtension();

        $container = $this->getContainerWithContaoConfiguration(
            Path::canonicalize(__DIR__.'/../../Fixtures/Twig/legacy')
        );

        $container->set('contao.insert_tag.parser', new InsertTagParser($this->mockContaoFramework(), $this->createMock(LoggerInterface::class), $this->createMock(FragmentHandler::class), $this->createMock(RequestStack::class)));

        System::setContainer($container);

        $output = $extension->renderLegacyTemplate(
            'foo.html5',
            ['B' => ['overwritten B block']],
            ['foo' => 'bar']
        );

        $this->assertSame("foo: bar\noriginal A block\noverwritten B block", $output);
    }

    public function testRenderLegacyTemplateNested(): void
    {
        $extension = $this->getContaoExtension();

        $container = $this->getContainerWithContaoConfiguration(
            Path::canonicalize(__DIR__.'/../../Fixtures/Twig/legacy')
        );

        $container->set('contao.insert_tag.parser', new InsertTagParser($this->mockContaoFramework(), $this->createMock(LoggerInterface::class), $this->createMock(FragmentHandler::class), $this->createMock(RequestStack::class)));

        System::setContainer($container);

        $framework = new \ReflectionClass(ContaoFramework::class);
        $framework->setStaticPropertyValue('nonce', '<nonce>');

        $output = $extension->renderLegacyTemplate(
            'baz.html5',
            ['B' => "root before B\n[[TL_PARENT_<nonce>]]root after B"],
            ['foo' => 'bar']
        );

        $this->assertSame(
            implode("\n", [
                'foo: bar',
                'baz before A',
                'bar before A',
                'original A block',
                'bar after A',
                'baz after A',
                'root before B',
                'baz before B',
                'original B block',
                'baz after B',
                'root after B',
            ]),
            $output
        );
    }

    public function testRenderLegacyTemplateWithTemplateFunctions(): void
    {
        $tokenChecker = $this->createMock(TokenChecker::class);
        $tokenChecker
            ->method('hasBackendUser')
            ->willReturn(true)
        ;

        $container = $this->getContainerWithContaoConfiguration(Path::canonicalize(__DIR__.'/../../Fixtures/Twig/legacy'));
        $container->set('contao.security.token_checker', $tokenChecker);
        $container->set('contao.insert_tag.parser', new InsertTagParser($this->mockContaoFramework(), $this->createMock(LoggerInterface::class), $this->createMock(FragmentHandler::class), $this->createMock(RequestStack::class)));

        System::setContainer($container);

        $GLOBALS['TL_LANG'] = [
            'MONTHS' => ['a', 'b'],
            'DAYS' => ['c', 'd'],
            'MONTHS_SHORT' => ['e', 'f'],
            'DAYS_SHORT' => ['g', 'h'],
            'DP' => ['select_a_time' => 'i', 'use_mouse_wheel' => 'j', 'time_confirm_button' => 'k', 'apply_range' => 'l', 'cancel' => 'm', 'week' => 'n'],
        ];

        $output = $this->getContaoExtension()->renderLegacyTemplate('with_template_functions.html5', [], []);

        $expected =
            "1\n".
            'Locale.define("en-US","Date",{months:["a","b"],days:["c","d"],months_abbr:["e","f"],days_abbr:["g","h"]});'.
            'Locale.define("en-US","DatePicker",{select_a_time:"i",use_mouse_wheel:"j",time_confirm_button:"k",apply_range:"l",cancel:"m",week:"n"});';

        $this->assertSame($expected, $output);

        unset($GLOBALS['TL_LANG']);
    }

    /**
     * @dataProvider provideTemplateNames
     */
    public function testDefaultEscaperRules(string $templateName): void
    {
        $extension = $this->getContaoExtension();

        $property = new \ReflectionProperty(ContaoExtension::class, 'contaoEscaperFilterRules');
        $rules = $property->getValue($extension);

        $this->assertCount(2, $rules);

        foreach ($rules as $rule) {
            if (1 === preg_match($rule, $templateName)) {
                return;
            }
        }

        $this->fail(sprintf('No escaper rule matched template "%s".', $templateName));
    }

    public function provideTemplateNames(): \Generator
    {
        yield '@Contao namespace' => ['@Contao/foo.html.twig'];
        yield '@Contao namespace with folder' => ['@Contao/foo/bar.html.twig'];
        yield '@Contao_* namespace' => ['@Contao_Global/foo.html.twig'];
        yield '@Contao_* namespace with folder' => ['@Contao_Global/foo/bar.html.twig'];
        yield 'core-bundle template' => ['@ContaoCore/Image/Studio/figure.html.twig'];
    }

    /**
     * We need to adjust some of Twig's core functions (e.g. the escape filter)
     * but still delegate to the original implementation for maximum compatibility.
     * This test makes sure the function's signatures remains the same and changes
     * to the original codebase do not stay unnoticed.
     *
     * @dataProvider provideTwigFunctionSignatures
     */
    public function testContaoUsesCorrectTwigFunctionSignatures(string $function, array $expectedParameters): void
    {
        // Make sure the functions outside the class scope are loaded
        new \ReflectionClass(EscaperExtension::class);

        $parameters = array_map(
            static fn (\ReflectionParameter $parameter): array => [
                ($type = $parameter->getType()) instanceof \ReflectionNamedType ? $type->getName() : null,
                $parameter->getName(),
            ],
            (new \ReflectionFunction($function))->getParameters()
        );
        $this->assertSame($parameters, $expectedParameters);
    }

    public function provideTwigFunctionSignatures(): \Generator
    {
        yield [
            'twig_escape_filter',
            [
                [Environment::class, 'env'],
                [null, 'string'],
                [null, 'strategy'],
                [null, 'charset'],
                [null, 'autoescape'],
            ],
        ];

        yield [
            'twig_escape_filter_is_safe',
            [
                [Node::class, 'filterArgs'],
            ],
        ];
    }

    /**
     * @param Environment&MockObject $environment
     */
    private function getContaoExtension(Environment|null $environment = null, TemplateHierarchyInterface|null $hierarchy = null): ContaoExtension
    {
        $environment ??= $this->createMock(Environment::class);
        $hierarchy ??= $this->createMock(TemplateHierarchyInterface::class);

        $environment
            ->method('getExtension')
            ->willReturnMap([
                [EscaperExtension::class, new EscaperExtension()],
                [CoreExtension::class, new CoreExtension()],
            ])
        ;

        return new ContaoExtension($environment, $hierarchy, $this->createMock(ContaoCsrfTokenManager::class));
    }
}
