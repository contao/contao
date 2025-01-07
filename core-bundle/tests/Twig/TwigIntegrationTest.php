<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig;

use Contao\Config;
use Contao\CoreBundle\Config\ResourceFinder;
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\InsertTag\ChunkedText;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\InsertTag\InsertTagSubscription;
use Contao\CoreBundle\InsertTag\Resolver\LegacyInsertTag;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Extension\ContaoExtension;
use Contao\CoreBundle\Twig\Global\ContaoVariable;
use Contao\CoreBundle\Twig\Interop\ContextFactory;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Contao\CoreBundle\Twig\Loader\TemplateLocator;
use Contao\CoreBundle\Twig\Loader\ThemeNamespace;
use Contao\CoreBundle\Twig\Runtime\HighlighterRuntime;
use Contao\CoreBundle\Twig\Runtime\InsertTagRuntime;
use Contao\FormText;
use Contao\FrontendTemplate;
use Contao\System;
use Contao\TemplateLoader;
use Doctrine\DBAL\Connection;
use Highlight\Highlighter;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Fragment\FragmentHandler;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\RuntimeLoader\FactoryRuntimeLoader;

class TwigIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        (new Filesystem())->mkdir(Path::join($this->getTempDir(), 'templates'));

        $GLOBALS['TL_FFL'] = [
            'text' => FormText::class,
        ];

        $GLOBALS['TL_LANG']['MSC'] = [
            'mandatory' => 'mandatory',
            'global' => 'global',
        ];
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove(Path::join($this->getTempDir(), 'templates'));

        TemplateLoader::reset();

        unset($GLOBALS['TL_LANG'], $GLOBALS['TL_FFL'], $GLOBALS['TL_MIME']);

        $this->resetStaticProperties([ContaoFramework::class, System::class, Config::class]);

        parent::tearDown();
    }

    public function testRendersWidgets(): void
    {
        $content = "{{ strClass }}\n{{ strLabel }} {{ this.label }}\n {{ getErrorAsString }}";

        // Setup legacy framework and environment
        (new Filesystem())->touch(Path::join($this->getTempDir(), 'templates/form_text.html5'));
        TemplateLoader::addFile('form_text', 'templates');

        $environment = new Environment(new ArrayLoader(['@Contao/form_text.html.twig' => $content]));

        $environment->addExtension(
            new ContaoExtension(
                $environment,
                $this->createMock(ContaoFilesystemLoader::class),
                $this->createMock(ContaoCsrfTokenManager::class),
                $this->createMock(ContaoVariable::class),
            ),
        );

        $container = $this->getContainerWithContaoConfiguration($this->getTempDir());
        $container->set('twig', $environment);
        $container->set(ContextFactory::class, new ContextFactory());

        System::setContainer($container);

        // Render widget
        $textField = new FormText(['class' => 'my_class', 'label' => 'foo']);
        $textField->addError('bar');

        $this->assertSame("my_class error\nfoo foo\n bar", $textField->parse());
    }

    public function testRendersTwigTemplateWithLegacyParent(): void
    {
        (new Filesystem())->dumpFile(
            Path::join($this->getTempDir(), 'templates/legacy_template.html5'),
            <<<'EOF'
                <?php
                    echo $this->value;
                    echo ',test1';
                    $this->block('a');
                    echo ',test2';
                    $this->endblock('a');
                    echo ',test3';
                    $this->block('b');
                    echo ',test4';
                    $this->block('b1');
                    echo ',test5';
                    $this->endblock('b1');
                    echo ',test6';
                    $this->endblock('b');
                    echo ',test7';
                EOF,
        );

        TemplateLoader::addFile('legacy_template', 'templates');

        (new Filesystem())->dumpFile(
            Path::join($this->getTempDir(), 'templates/twig_template.html.twig'),
            <<<'EOF'
                {% extends "@Contao/legacy_template.html5" %}
                {% block a %}<<{{ parent() }}>>{% endblock %}
                {% block b1 %}{{ parent() }},({{ value }}){% endblock %}
                EOF,
        );

        $templateLocator = new TemplateLocator(
            $this->getTempDir(),
            $this->createMock(ResourceFinder::class),
            $themeNamespace = new ThemeNamespace(),
            $this->createMock(Connection::class),
        );

        $filesystemLoader = new ContaoFilesystemLoader(new NullAdapter(), $templateLocator, $themeNamespace, $this->createMock(ContaoFramework::class), $this->getTempDir());
        $environment = new Environment($filesystemLoader);

        $environment->addExtension(
            new ContaoExtension(
                $environment,
                $filesystemLoader,
                $this->createMock(ContaoCsrfTokenManager::class),
                $this->createMock(ContaoVariable::class),
            ),
        );

        $container = $this->getContainerWithContaoConfiguration($this->getTempDir());
        $container->set('twig', $environment);
        $container->set(ContextFactory::class, new ContextFactory());

        $insertTagParser = new InsertTagParser($this->createMock(ContaoFramework::class), $this->createMock(LoggerInterface::class), $this->createMock(FragmentHandler::class), $this->createMock(RequestStack::class));
        $insertTagParser->addSubscription(new InsertTagSubscription(new LegacyInsertTag($container), '__invoke', 'br', null, true, false));

        $container->set('contao.insert_tag.parser', $insertTagParser);

        System::setContainer($container);

        $template = new FrontendTemplate('twig_template');
        $template->setData(['value' => 'value{{br}}']);

        $obLevel = ob_get_level();
        $this->assertSame('value<br>,test1<<,test2>>,test3,test4,test5,(value{{br}}),test6,test7', $template->parse());
        $this->assertSame($obLevel, ob_get_level());
    }

    public function testRendersAttributes(): void
    {
        $templateContent = <<<'TEMPLATE'
            <div{{ attrs(attributes).addClass('foo').mergeWith(cssId) }}>
              <h1{{ attrs() }}>
                <span{{ attrs({'data-x': 'y'}).setIfExists('style', style).set('data-bar', 'bar') }}>{{ headline }}</span>
              </h1>
              <p{{ attrs(paragraph_attributes) }}>{{ text }}</p>
            </div>
            TEMPLATE;

        $expectedOutput = <<<'TEMPLATE'
            <div class="block foo" data-thing="42" id="my-id">
              <h1>
                <span data-x="y" data-bar="bar">Test headline</span>
              </h1>
              <p class="rte">Some text</p>
            </div>
            TEMPLATE;

        $environment = new Environment(new ArrayLoader(['test.html.twig' => $templateContent]));

        $environment->addExtension(
            new ContaoExtension(
                $environment,
                $this->createMock(ContaoFilesystemLoader::class),
                $this->createMock(ContaoCsrfTokenManager::class),
                $this->createMock(ContaoVariable::class),
            ),
        );

        $output = $environment->render('test.html.twig', [
            'attributes' => ['class' => 'block', 'data-thing' => 42],
            'cssId' => ' id="my-id"',
            'paragraph_attributes' => ' class="rte"',
            'style' => '',
            'headline' => 'Test headline',
            'text' => 'Some text',
        ]);

        $this->assertSame($expectedOutput, $output);
    }

    public function testHighlightsCode(): void
    {
        $templateContent = <<<'TEMPLATE'
            <h2>js</h2>
            <pre>
                {{ code|highlight('js') }}
            </pre>

            {% set highlighted = code|highlight_auto(['php', 'c++']) %}
            <h2>{{ highlighted.language }}</h2>
            <pre>
                {{ highlighted }}
            </pre>
            TEMPLATE;

        $expectedOutput = <<<'TEMPLATE'
            <h2>js</h2>
            <pre>
                <span class="hljs-function"><span class="hljs-keyword">function</span> <span class="hljs-title">foo</span>(<span class="hljs-params"></span>) </span>{ <span class="hljs-keyword">return</span> <span class="hljs-string">"&lt;b&gt;ar"</span>; };
            </pre>

            <h2>php</h2>
            <pre>
                <span class="hljs-function"><span class="hljs-keyword">function</span> <span class="hljs-title">foo</span><span class="hljs-params">()</span> </span>{ <span class="hljs-keyword">return</span> <span class="hljs-string">"&lt;b&gt;ar"</span>; };
            </pre>
            TEMPLATE;

        $environment = new Environment(new ArrayLoader(['test.html.twig' => $templateContent]));
        $environment->addRuntimeLoader(new FactoryRuntimeLoader([HighlighterRuntime::class => static fn () => new HighlighterRuntime()]));

        $environment->addExtension(
            new ContaoExtension(
                $environment,
                $this->createMock(ContaoFilesystemLoader::class),
                $this->createMock(ContaoCsrfTokenManager::class),
                $this->createMock(ContaoVariable::class),
            ),
        );

        $output = $environment->render('test.html.twig', [
            'code' => 'function foo() { return "<b>ar"; };',
        ]);

        $this->assertSame($expectedOutput, $output);

        $this->resetStaticProperties([Highlighter::class]);
    }

    public function testPreservesSafetyInInsertTagFilters(): void
    {
        $templateContent = <<<'TEMPLATE'
            {{ '<i>foo</i>{{br}}'|insert_tag_raw }}
            {{ unsafe|raw|insert_tag }}
            {{ unsafe|insert_tag_raw }}
            TEMPLATE;

        // With 'preserve_safety' set, we expect the unescaped versions in the first two
        // lines, while the unsafe parameter is still escaped (last line):
        $expectedOutput = <<<'TEMPLATE'
            <i>foo</i><br>
            <i>foo</i><br>
            &lt;i&gt;foo&lt;/i&gt;<br>
            TEMPLATE;

        $parser = $this->createMock(InsertTagParser::class);
        $parser
            ->method('replaceChunked')
            ->willReturnCallback(
                static fn (string $input): ChunkedText => match ($input) {
                    '<i>foo</i>{{br}}' => new ChunkedText(['<i>foo</i>', '<br>']),
                    default => new ChunkedText([$input]),
                },
            )
        ;

        $parser
            ->method('replaceInline')
            ->with('<i>foo</i>{{br}}')
            ->willReturn('<i>foo</i><br>')
        ;

        $environment = new Environment(new ArrayLoader(['test.html.twig' => $templateContent]));
        $environment->addRuntimeLoader(new FactoryRuntimeLoader([InsertTagRuntime::class => static fn () => new InsertTagRuntime($parser)]));

        $environment->addExtension(
            new ContaoExtension(
                $environment,
                $this->createMock(ContaoFilesystemLoader::class),
                $this->createMock(ContaoCsrfTokenManager::class),
                $this->createMock(ContaoVariable::class),
            ),
        );

        $output = $environment->render('test.html.twig', ['unsafe' => '<i>foo</i>{{br}}']);

        $this->assertSame($expectedOutput, $output);
    }

    /**
     * @dataProvider provideDeserializeFilterValues
     */
    public function testDeserializeFilter(mixed $values, string $expectedOutput): void
    {
        $templateContent = <<<'TEMPLATE'
            <ul>
                {%- for key, value in values|deserialize ~%}
                <li>{{ key }}: {{ value }}</li>
                {%- endfor ~%}
            </ul>
            TEMPLATE;

        $environment = new Environment(new ArrayLoader(['test.html.twig' => $templateContent]));

        $environment->addExtension(
            new ContaoExtension(
                $environment,
                $this->createMock(ContaoFilesystemLoader::class),
                $this->createMock(ContaoCsrfTokenManager::class),
                $this->createMock(ContaoVariable::class),
            ),
        );

        $output = $environment->render('test.html.twig', [
            'values' => $values,
        ]);

        $this->assertSame($expectedOutput, $output);
    }

    public static function provideDeserializeFilterValues(): iterable
    {
        yield [
            serialize(['key1' => 'value1', 'key2' => 2]),
            <<<'HTML'
                <ul>
                    <li>key1: value1</li>
                    <li>key2: 2</li>
                </ul>
                HTML,
        ];

        yield [
            serialize(['value1', 2]),
            <<<'HTML'
                <ul>
                    <li>0: value1</li>
                    <li>1: 2</li>
                </ul>
                HTML,
        ];

        yield [
            ['key1' => 'value1', 'key2' => 2],
            <<<'HTML'
                <ul>
                    <li>key1: value1</li>
                    <li>key2: 2</li>
                </ul>
                HTML,
        ];

        yield [
            ['value1', 2],
            <<<'HTML'
                <ul>
                    <li>0: value1</li>
                    <li>1: 2</li>
                </ul>
                HTML,
        ];

        yield [
            'string',
            <<<'HTML'
                <ul>
                    <li>0: string</li>
                </ul>
                HTML,
        ];

        yield [
            123,
            <<<'HTML'
                <ul>
                    <li>0: 123</li>
                </ul>
                HTML,
        ];

        yield [
            '',
            <<<'HTML'
                <ul>
                </ul>
                HTML,
        ];

        yield [
            null,
            <<<'HTML'
                <ul>
                </ul>
                HTML,
        ];
    }
}
