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

use Contao\CoreBundle\Routing\ResponseContext\HtmlBodyBag;
use Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag\HtmlHeadBag;
use Contao\CoreBundle\Routing\ResponseContext\HtmlTag;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContext;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContextAccessor;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Extension\ContaoExtension;
use Contao\CoreBundle\Twig\Global\ContaoVariable;
use Contao\CoreBundle\Twig\Inspector\InspectorNodeVisitor;
use Contao\CoreBundle\Twig\Inspector\Storage;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Contao\CoreBundle\Twig\Renderer\DeferredRenderer;
use Contao\CoreBundle\Twig\Runtime\HtmlDocumentRuntime;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Loader\ChainLoader;
use Twig\Loader\FilesystemLoader;
use Twig\RuntimeLoader\FactoryRuntimeLoader;

class HtmlHeadLayoutTest extends TestCase
{
    public function testRendersAndOverridesCollectedHeadTags(): void
    {
        $environment = $this->createEnvironment([
            'child.html.twig' => <<<'TWIG'
                {% extends 'page/layout.html.twig' %}
                {% block title %}<title data-custom>{{ head_tag.content }}</title>{% endblock %}
                {% block body %}{% endblock %}
                TWIG,
        ]);

        $head = new HtmlHeadBag()
            ->setTitle('Page title')
            ->add(HtmlTag::script('/app.js', ['defer' => true]))
        ;

        $output = new DeferredRenderer($environment)->render('child.html.twig', [
            'rtl' => false,
            'response_context' => ['head' => $head, 'end_of_head' => []],
            'app' => ['locale' => 'en', 'request' => Request::create('https://example.com/')],
        ]);

        $this->assertStringContainsString('<title data-custom>Page title</title>', $output);
        $this->assertMatchesRegularExpression('/<meta name="description" content>\s*<script src="\/app\.js" defer><\/script>/', $output);
    }

    public function testRendersCollectedBodyTagsBeforeLegacyContent(): void
    {
        $environment = $this->createEnvironment([
            'child.html.twig' => <<<'TWIG'
                {% extends 'page/layout.html.twig' %}
                {% block body_content %}{% endblock %}
                TWIG,
        ]);
        $body = new HtmlBodyBag()->add(HtmlTag::script('/app.js'));

        $output = new DeferredRenderer($environment)->render('child.html.twig', [
            'rtl' => false,
            'response_context' => [
                'head' => new HtmlHeadBag(),
                'body' => $body,
                'end_of_head' => [],
                'end_of_body' => ['<script data-legacy></script>'],
            ],
            'app' => ['locale' => 'en', 'request' => Request::create('https://example.com/')],
        ]);

        $this->assertMatchesRegularExpression('/<script src="\/app\.js"><\/script>\s*<script data-legacy><\/script>/', $output);
    }

    public function testAddsTwigContentToTheRenderedDocument(): void
    {
        $request = Request::create('https://example.com/');
        $accessor = new ResponseContextAccessor(new RequestStack([$request]));
        $responseContext = new ResponseContext()
            ->add($body = new HtmlBodyBag())
            ->add($head = new HtmlHeadBag())
        ;
        $accessor->setResponseContext($responseContext);
        $environment = $this->createEnvironment(
            [
                'child.html.twig' => <<<'TWIG'
                    {% extends 'page/layout.html.twig' %}
                    {% block body_content %}
                        {% add 'theme' to stylesheets %}<link rel="stylesheet" href="/theme.css">{% endadd %}
                        {% add 'module' to head %}<script src="/module.js"></script>{% endadd %}
                        {% add 'footer' to body %}<script src="/footer.js"></script>{% endadd %}
                    {% endblock %}
                    TWIG,
            ],
            $accessor,
        );

        $output = new DeferredRenderer($environment)->render('child.html.twig', [
            'rtl' => false,
            'response_context' => ['head' => $head, 'body' => $body],
            'app' => ['locale' => 'en', 'request' => $request],
        ]);

        $this->assertStringContainsString('<link rel="stylesheet" href="/theme.css">', $output);
        $this->assertStringContainsString('<script src="/module.js"></script>', $output);
        $this->assertStringContainsString('<script src="/footer.js"></script>', $output);
        $this->assertArrayNotHasKey('TL_HEAD', $GLOBALS);
        $this->assertArrayNotHasKey('TL_STYLE_SHEETS', $GLOBALS);
        $this->assertArrayNotHasKey('TL_BODY', $GLOBALS);
    }

    /**
     * @param array<string, string> $templates
     */
    private function createEnvironment(array $templates, ResponseContextAccessor|null $accessor = null): Environment
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
            HtmlDocumentRuntime::class => static fn () => new HtmlDocumentRuntime($accessor),
        ]));
        $environment->addExtension(new ContaoExtension(
            $environment,
            $filesystemLoader,
            $this->createStub(ContaoVariable::class),
            new InspectorNodeVisitor($this->createStub(Storage::class), $environment),
        ));

        return $environment;
    }
}
