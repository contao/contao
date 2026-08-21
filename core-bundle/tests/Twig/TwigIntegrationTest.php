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
use Contao\CoreBundle\Csp\WysiwygStyleProcessor;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\Routing\ResponseContext\Csp\CspHandler;
use Contao\CoreBundle\Routing\ResponseContext\HtmlBodyBag;
use Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag\HtmlHeadBag;
use Contao\CoreBundle\Routing\ResponseContext\HtmlTag;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContext;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContextAccessor;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Defer\DeferTokenParser;
use Contao\CoreBundle\Twig\Extension\ContaoExtension;
use Contao\CoreBundle\Twig\Global\ContaoVariable;
use Contao\CoreBundle\Twig\Inspector\InspectorNodeVisitor;
use Contao\CoreBundle\Twig\Inspector\Storage;
use Contao\CoreBundle\Twig\Interop\ContextFactory;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Contao\CoreBundle\Twig\Renderer\DeferredRenderer;
use Contao\CoreBundle\Twig\Runtime\CspRuntime;
use Contao\CoreBundle\Twig\Runtime\HighlighterRuntime;
use Contao\CoreBundle\Twig\Runtime\HtmlDocumentRuntime;
use Contao\CoreBundle\Twig\Runtime\InsertTagRuntime;
use Contao\FormText;
use Contao\System;
use Highlight\Highlighter;
use Nelmio\SecurityBundle\ContentSecurityPolicy\DirectiveSet;
use Nelmio\SecurityBundle\ContentSecurityPolicy\PolicyManager;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Loader\ChainLoader;
use Twig\Loader\FilesystemLoader;
use Twig\RuntimeLoader\FactoryRuntimeLoader;

class TwigIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        new Filesystem()->mkdir(Path::join($this->getTempDir(), 'templates'));

        $GLOBALS['TL_FFL'] = [
            'text' => FormText::class,
        ];

        $GLOBALS['TL_LANG']['MSC'] = [
            'mandatory' => 'mandatory',
            'user' => 'user',
        ];
    }

    protected function tearDown(): void
    {
        new Filesystem()->remove(Path::join($this->getTempDir(), 'templates'));

        unset($GLOBALS['TL_LANG'], $GLOBALS['TL_FFL'], $GLOBALS['TL_MIME']);

        $this->resetStaticProperties([ContaoFramework::class, DeferTokenParser::class, System::class, Config::class]);

        parent::tearDown();
    }

    public function testRendersWidgets(): void
    {
        $content = "{{ strClass }}\n{{ strLabel }} {{ this.label }}\n {{ getErrorAsString }}";

        $environment = new Environment(new ArrayLoader(['@Contao/form_text.html.twig' => $content]));

        $environment->addExtension(
            new ContaoExtension(
                $environment,
                $this->createStub(ContaoFilesystemLoader::class),
                $this->createStub(ContaoVariable::class),
                new InspectorNodeVisitor($this->createStub(Storage::class), $environment),
            ),
        );

        $requestStack = new RequestStack([$request = new Request()]);

        $filesystemLoader = $this->createStub(ContaoFilesystemLoader::class);
        $filesystemLoader
            ->method('exists')
            ->willReturnMap([['@Contao/form_text.html.twig', true]])
        ;

        $filesystemLoader
            ->method('getFirst')
            ->willReturnMap([['form_text', '/path/to/form_text.html.twig']])
        ;

        $container = $this->getContainerWithContaoConfiguration($this->getTempDir());
        $container->set('twig', $environment);
        $container->set(ContextFactory::class, new ContextFactory($this->createStub(ScopeMatcher::class)));
        $container->set('request_stack', $requestStack);
        $container->set('contao.twig.filesystem_loader', $filesystemLoader);

        System::setContainer($container);

        // Render widget
        $textField = new FormText(['class' => 'my_class', 'label' => 'foo']);
        $textField->addError('bar');

        $this->assertSame("my_class error\nfoo foo\n bar", $textField->parse(), 'HTML is built correctly');
        $this->assertTrue($request->attributes->get('_contao_widget_error'), 'error attribute is set');
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
                $this->createStub(ContaoFilesystemLoader::class),
                $this->createStub(ContaoVariable::class),
                new InspectorNodeVisitor($this->createStub(Storage::class), $environment),
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

    public function testRendersHtmlTags(): void
    {
        $environment = $this->createHeadEnvironment([
            'test.html.twig' => <<<'TWIG'
                {{ include('@Contao/component/_html_tag.html.twig', {tag, identifier: null, debug: false}, false) }}
                {{- include('@Contao/component/_html_tag.html.twig', {tag: raw_tag, identifier: null, debug: false}, false) }}
                TWIG,
        ]);

        $this->assertSame(
            '<title>&lt;Title&gt;</title><meta data-legacy>',
            $environment->render('test.html.twig', [
                'tag' => HtmlTag::title('<Title>'),
                'raw_tag' => '<meta data-legacy>',
            ]),
        );
    }

    public function testCanCustomizeTheHtmlTagComponent(): void
    {
        $environment = $this->createHeadEnvironment([
            'test.html.twig' => "{{ include('@Contao/component/_html_tag.html.twig', {tag, identifier: null, debug: false}, false) }}",
            'component/_html_tag.html.twig' => '<custom-tag data-name="{{ tag.name }}">{{ tag.content }}</custom-tag>',
        ]);

        $this->assertSame(
            '<custom-tag data-name="title">Page title</custom-tag>',
            $environment->render('test.html.twig', ['tag' => HtmlTag::title('Page title')]),
        );
    }

    public function testRendersDebugIdentifiersAndCspNoncesInTheHtmlTagComponent(): void
    {
        $directives = new DirectiveSet(new PolicyManager());
        $directives->setDirective('script-src', "'self'");
        $directives->setDirective('style-src', "'self'");

        $responseContext = new ResponseContext()->add(new CspHandler($directives));
        $request = Request::create('https://example.com/');
        $accessor = new ResponseContextAccessor(new RequestStack([$request]));
        $accessor->setResponseContext($responseContext);

        $environment = $this->createHeadEnvironment(
            [
                'test.html.twig' => <<<'TWIG'
                    {{ include('@Contao/component/_html_tag.html.twig', {tag: external_script, identifier: external_identifier, debug: true}, false) }}
                    {{- include('@Contao/component/_html_tag.html.twig', {tag: raw_tag, identifier: raw_identifier, debug: true}, false) }}
                    {{- include('@Contao/component/_html_tag.html.twig', {tag: inline_script, identifier: null, debug: false}, false) }}
                    {{- include('@Contao/component/_html_tag.html.twig', {tag: inline_style, identifier: null, debug: false}, false) }}
                    {{- include('@Contao/component/_html_tag.html.twig', {tag: manual_script, identifier: null, debug: false}, false) }}
                    TWIG,
            ],
            $accessor,
        );
        $inlineScript = HtmlTag::inlineScript('alert(1)');

        $output = $environment->render('test.html.twig', [
            'external_script' => HtmlTag::script('/app.js'),
            'external_identifier' => 'script[src="/app.js"]',
            'raw_tag' => '<meta data-raw>',
            'raw_identifier' => "contao.twig.head.price--break\n",
            'inline_script' => $inlineScript,
            'inline_style' => HtmlTag::inlineStyle('body {}'),
            'manual_script' => $inlineScript->withAttribute('nonce', 'manual'),
        ]);

        $this->assertMatchesRegularExpression(
            '/^<script src="\/app\.js" data-contao-tag="script\[src=&quot;\/app\.js&quot;\]"><\/script>/',
            $output,
        );
        $this->assertStringContainsString('<!-- contao-tag (URL-encoded): price%2D%2Dbreak%0A --><meta data-raw>', $output);
        $this->assertMatchesRegularExpression('/<script nonce="[^"]+">alert\(1\)<\/script>/', $output);
        $this->assertMatchesRegularExpression('/<style nonce="[^"]+">body \{\}<\/style>/', $output);
        $this->assertStringContainsString('<script nonce="manual">alert(1)</script>', $output);
        $this->assertFalse(isset($inlineScript->getAttributes()['nonce']));
    }

    public function testRendersAndOverridesHeadTagBlocksWhenDeferred(): void
    {
        $environment = $this->createHeadEnvironment([
            'child.html.twig' => <<<'TWIG'
                {% extends 'page/layout.html.twig' %}
                {% block viewport %}<meta name="viewport" content="custom">{% endblock %}
                {% block title %}<title data-custom>{{ head_tag.content }}</title>{% endblock %}
                {% block end_of_head %}{{ parent() }}<meta data-after-legacy>{% endblock %}
                {% block body %}{% endblock %}
                TWIG,
        ]);

        $head = new HtmlHeadBag()
            ->setTitle('Page title')
            ->addAfter(HtmlTag::script('/app.js', ['defer' => true]), HtmlHeadBag::TAG_DESCRIPTION, 'app.script')
        ;

        $output = new DeferredRenderer($environment)->render('child.html.twig', [
            'locale' => 'en',
            'rtl' => false,
            'response_context' => ['head' => $head, 'end_of_head' => ['<meta data-legacy>']],
            'app' => ['request' => Request::create('https://example.com/')],
        ]);

        $this->assertStringContainsString('<meta name="viewport" content="custom">', $output);
        $this->assertStringNotContainsString('width=device-width', $output);
        $this->assertStringContainsString('<title data-custom>Page title</title>', $output);
        $this->assertMatchesRegularExpression('/<meta name="description" content>\s*<script src="\/app\.js" defer><\/script>/', $output);
        $this->assertMatchesRegularExpression('/<meta data-legacy>\s*<meta data-after-legacy>/', $output);
    }

    public function testAddTwigTagAddsAndReordersContentInTheDeferredDocument(): void
    {
        $request = Request::create('https://example.com/');
        $accessor = new ResponseContextAccessor(new RequestStack([$request]));
        $responseContext = new ResponseContext()
            ->add($body = new HtmlBodyBag())
            ->add($head = new HtmlHeadBag())
        ;
        $body->add(HtmlTag::script('/structured.js'));
        $accessor->setResponseContext($responseContext);
        $environment = $this->createHeadEnvironment(
            [
                'child.html.twig' => <<<'TWIG'
                    {% extends 'page/layout.html.twig' %}
                    {% block body_content %}
                        {% add 'theme' to stylesheets %}<link rel="stylesheet" href="/theme.css">{% endadd %}
                        {% add 'module' to head after 'vendor' %}<script src="/module.js"></script>{% endadd %}
                        {% add 'vendor' to head %}<script src="/vendor.js"></script>{% endadd %}
                        {% add 'footer' to body after 'script[src="/structured.js"]' %}<script src="/footer.js"></script>{% endadd %}
                    {% endblock %}
                    {% block head_tags %}
                        {% do response_context.head.orderBefore('module', 'vendor') %}
                        {{ parent() }}
                    {% endblock %}
                    {% block end_of_body %}
                        {% do response_context.body.orderBefore('footer', 'script[src="/structured.js"]') %}
                        {{ parent() }}
                    {% endblock %}
                    TWIG,
            ],
            $accessor,
        );

        $output = new DeferredRenderer($environment)->render('child.html.twig', [
            'locale' => 'en',
            'rtl' => false,
            'response_context' => [
                'head' => $head,
                'body' => $body,
                'end_of_body' => ['<script data-legacy-body></script>'],
            ],
            'app' => ['request' => $request, 'debug' => true],
        ]);

        $this->assertStringContainsString('<meta charset="UTF-8">', $output);
        $this->assertStringContainsString('<meta name="generator" content="Contao Open Source CMS">', $output);
        $this->assertStringContainsString('width=device-width,initial-scale=1.0,shrink-to-fit=no', $output);
        $this->assertMatchesRegularExpression(
            '/<!-- contao-tag \(URL-encoded\): module --><script src="\/module\.js"><\/script>\s*<!-- contao-tag \(URL-encoded\): vendor --><script src="\/vendor\.js"><\/script>/',
            $output,
        );
        $this->assertMatchesRegularExpression(
            '/<!-- contao-tag \(URL-encoded\): footer --><script src="\/footer\.js"><\/script>\s*<script src="\/structured\.js" data-contao-tag="script\[src=&quot;\/structured\.js&quot;\]"><\/script>/',
            $output,
        );
        $this->assertMatchesRegularExpression(
            '/<script src="\/structured\.js"[^>]*><\/script>\s*<script data-legacy-body><\/script>/',
            $output,
        );
        $this->assertStringContainsString('<!-- contao-tag (URL-encoded): theme --><link rel="stylesheet" href="/theme.css">', $output);
        $this->assertArrayNotHasKey('TL_HEAD', $GLOBALS);
        $this->assertArrayNotHasKey('TL_STYLE_SHEETS', $GLOBALS);
        $this->assertArrayNotHasKey('TL_BODY', $GLOBALS);
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
                $this->createStub(ContaoFilesystemLoader::class),
                $this->createStub(ContaoVariable::class),
                new InspectorNodeVisitor($this->createStub(Storage::class), $environment),
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
            {{ '<i>foo</i>{{br}}'|insert_tag_html }}
            {{ unsafe|insert_tag|raw }}
            {{ unsafe|insert_tag_html }}
            TEMPLATE;

        // With 'preserve_safety' set, we expect the unescaped versions in the first two
        // lines, while the unsafe parameter is still escaped (last line):
        $expectedOutput = <<<'TEMPLATE'
            <i>foo</i><br>
            <i>foo</i><br>
            &lt;i&gt;foo&lt;/i&gt;<br>
            TEMPLATE;

        $parser = $this->createStub(InsertTagParser::class);
        $parser
            ->method('replace')
            ->willReturnCallback(
                static fn (string $input): string => match ($input) {
                    '<i>foo</i>{{br}}' => '<i>foo</i><br>',
                    '&lt;i&gt;foo&lt;/i&gt;{{br}}' => '&lt;i&gt;foo&lt;/i&gt;<br>',
                    default => $input,
                },
            )
        ;

        $parser
            ->method('replaceInline')
            ->willReturnCallback(
                static fn (string $input): string => match ($input) {
                    '<i>foo</i>{{br}}' => '<i>foo</i><br>',
                    '&lt;i&gt;foo&lt;/i&gt;{{br}}' => '&lt;i&gt;foo&lt;/i&gt;<br>',
                    default => $input,
                },
            )
        ;

        $environment = new Environment(new ArrayLoader(['test.html.twig' => $templateContent]));
        $environment->addRuntimeLoader(new FactoryRuntimeLoader([InsertTagRuntime::class => static fn () => new InsertTagRuntime($parser)]));

        $environment->addExtension(
            new ContaoExtension(
                $environment,
                $this->createStub(ContaoFilesystemLoader::class),
                $this->createStub(ContaoVariable::class),
                new InspectorNodeVisitor($this->createStub(Storage::class), $environment),
            ),
        );

        $output = $environment->render('test.html.twig', ['unsafe' => '<i>foo</i>{{br}}']);

        $this->assertSame($expectedOutput, $output);
    }

    #[DataProvider('provideDeserializeFilterValues')]
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
                $this->createStub(ContaoFilesystemLoader::class),
                $this->createStub(ContaoVariable::class),
                new InspectorNodeVisitor($this->createStub(Storage::class), $environment),
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

    /**
     * @param array<string, string> $templates
     */
    private function createHeadEnvironment(array $templates, ResponseContextAccessor|null $accessor = null): Environment
    {
        $accessor ??= $this->createStub(ResponseContextAccessor::class);
        $filesystemLoader = $this->createStub(ContaoFilesystemLoader::class);
        $filesystemLoader
            ->method('getAllFirstByThemeSlug')
            ->willReturnCallback(static fn (string $name): array => ['' => $name])
        ;
        $environment = new Environment(new ChainLoader([
            new ArrayLoader($templates),
            new FilesystemLoader(__DIR__.'/../../contao/templates'),
        ]));
        $environment->addRuntimeLoader(new FactoryRuntimeLoader([
            CspRuntime::class => static fn () => new CspRuntime($accessor, new WysiwygStyleProcessor([])),
            HtmlDocumentRuntime::class => static fn () => new HtmlDocumentRuntime($accessor),
        ]));
        $extension = new ContaoExtension(
            $environment,
            $filesystemLoader,
            $this->createStub(ContaoVariable::class),
            new InspectorNodeVisitor($this->createStub(Storage::class), $environment),
        );

        $environment->addExtension($extension);

        return $environment;
    }
}
