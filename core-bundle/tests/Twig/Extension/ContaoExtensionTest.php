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
use Contao\CoreBundle\Twig\Defer\DeferTokenParser;
use Contao\CoreBundle\Twig\Extension\ContaoExtension;
use Contao\CoreBundle\Twig\Extension\DeprecationsNodeVisitor;
use Contao\CoreBundle\Twig\Global\ContaoVariable;
use Contao\CoreBundle\Twig\Inheritance\DynamicExtendsTokenParser;
use Contao\CoreBundle\Twig\Inheritance\DynamicIncludeTokenParser;
use Contao\CoreBundle\Twig\Inheritance\DynamicUseTokenParser;
use Contao\CoreBundle\Twig\Inspector\InspectorNodeVisitor;
use Contao\CoreBundle\Twig\Inspector\Storage;
use Contao\CoreBundle\Twig\Interop\ContaoEscaperNodeVisitor;
use Contao\CoreBundle\Twig\Interop\PhpTemplateProxyNodeVisitor;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Contao\CoreBundle\Twig\ResponseContext\AddTokenParser;
use Contao\CoreBundle\Twig\Slots\SlotTokenParser;
use Contao\System;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpKernel\Fragment\FragmentHandler;
use Twig\Environment;
use Twig\Error\SyntaxError;
use Twig\Extension\AbstractExtension;
use Twig\Extension\CoreExtension;
use Twig\Extension\EscaperExtension;
use Twig\Loader\ArrayLoader;
use Twig\Node\BodyNode;
use Twig\Node\EmptyNode;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\FilterExpression;
use Twig\Node\ModuleNode;
use Twig\Node\Node;
use Twig\Node\Nodes;
use Twig\NodeTraverser;
use Twig\Runtime\EscaperRuntime;
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

        $this->assertCount(2, $nodeVisitors);

        $this->assertInstanceOf(InspectorNodeVisitor::class, $nodeVisitors[0]);
        $this->assertInstanceOf(DeprecationsNodeVisitor::class, $nodeVisitors[1]);
    }

    public function testAddsTheTokenParsers(): void
    {
        $tokenParsers = $this->getContaoExtension()->getTokenParsers();

        $this->assertCount(6, $tokenParsers);

        $this->assertInstanceOf(DynamicExtendsTokenParser::class, $tokenParsers[0]);
        $this->assertInstanceOf(DynamicIncludeTokenParser::class, $tokenParsers[1]);
        $this->assertInstanceOf(DynamicUseTokenParser::class, $tokenParsers[2]);
        $this->assertInstanceOf(AddTokenParser::class, $tokenParsers[3]);
        $this->assertInstanceOf(SlotTokenParser::class, $tokenParsers[4]);
        $this->assertInstanceOf(DeferTokenParser::class, $tokenParsers[5]);
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
            'csp_nonce' => [],
            'csp_source' => [],
            'csp_hash' => [],
            'content_url' => [],
            'slot' => [],
            'backend_icon' => ['html'],
            'file_icon' => ['html'],
        ];

        $functions = $this->getContaoExtension()->getFunctions();

        $this->assertCount(\count($expectedFunctions), $functions);

        $node = $this->createStub(Node::class);

        foreach ($functions as $function) {
            $this->assertInstanceOf(TwigFunction::class, $function);

            $name = $function->getName();
            $this->assertArrayHasKey($name, $expectedFunctions);
            $this->assertSame($expectedFunctions[$name], $function->getSafe($node), $name);
        }
    }

    public function testPreventsUseOfSlotFunction(): void
    {
        $environment = new Environment(
            new ArrayLoader(['template.html.twig' => 'foo {{ slot() }} bar']),
        );

        $environment->addExtension($this->getContaoExtension());

        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('You cannot use the slot() function outside of a slot');

        $environment->render('template.html.twig');
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
            'format_number',
            'csp_unsafe_inline_style',
            'csp_inline_styles',
            'encode_email',
            'input_encoded_to_plain_text',
            'html_to_plain_text',
            'deserialize',
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

        $filesystemLoader = $this->createStub(ContaoFilesystemLoader::class);
        $filesystemLoader
            ->method('getAllFirstByThemeSlug')
            ->with('foo')
            ->willReturn(['' => '@Contao_Bar/foo.html.twig'])
        ;

        $includeFunction = $this->getContaoExtension($environment, $filesystemLoader)->getFunctions()[0];
        $args = [$environment, [], '@Contao/foo'];

        $this->expectExceptionObject($methodCalledException);

        ($includeFunction->getCallable())(...$args);
    }

    public function testIncludeFunctionDelegatesToTwigIncludeWithThemeContext(): void
    {
        $methodCalledException = new \Exception();

        $environment = $this->createMock(Environment::class);
        $environment
            ->expects($this->once())
            ->method('resolveTemplate')
            ->with('@Contao_Theme_theme/foo.html.twig')
            ->willThrowException($methodCalledException)
        ;

        $filesystemLoader = $this->createStub(ContaoFilesystemLoader::class);
        $filesystemLoader
            ->method('getAllFirstByThemeSlug')
            ->with('foo')
            ->willReturn(['theme' => '@Contao_Theme_theme/foo.html.twig', '' => '@Contao_Bar/foo.html.twig'])
        ;

        $filesystemLoader
            ->method('getCurrentThemeSlug')
            ->willReturn('theme')
        ;

        $includeFunction = $this->getContaoExtension($environment, $filesystemLoader)->getFunctions()[0];
        $args = [$environment, [], '@Contao/foo'];

        $this->expectExceptionObject($methodCalledException);

        ($includeFunction->getCallable())(...$args);
    }

    public function testThrowsIfCoreIncludeFunctionIsNotFound(): void
    {
        $environment = $this->createStub(Environment::class);
        $environment
            ->method('getRuntime')
            ->willReturn(new EscaperRuntime())
        ;

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
            $this->createStub(ContaoFilesystemLoader::class),
            $this->createStub(ContaoCsrfTokenManager::class),
            $this->createStub(ContaoVariable::class),
            new InspectorNodeVisitor($this->createStub(Storage::class), $environment),
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The Twig\Extension\CoreExtension class was expected to register the "include" Twig function but did not.');

        $extension->getFunctions();
    }


    /**
     * @param Environment&MockObject $environment
     */
    private function getContaoExtension(Environment|null $environment = null, ContaoFilesystemLoader|null $filesystemLoader = null): ContaoExtension
    {
        $environment ??= $this->createStub(Environment::class);
        $filesystemLoader ??= $this->createStub(ContaoFilesystemLoader::class);

        $environment
            ->method('getRuntime')
            ->willReturn(new EscaperRuntime())
        ;

        $environment
            ->method('getExtension')
            ->willReturnMap([
                [EscaperExtension::class, new EscaperExtension()],
                [CoreExtension::class, new CoreExtension()],
            ])
        ;

        return new ContaoExtension(
            $environment,
            $filesystemLoader,
            $this->createStub(ContaoCsrfTokenManager::class),
            $this->createStub(ContaoVariable::class),
            new InspectorNodeVisitor($this->createStub(Storage::class), $environment),
        );
    }
}
