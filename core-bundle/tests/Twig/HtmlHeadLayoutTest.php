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

use Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag\HtmlHeadBag;
use Contao\CoreBundle\Routing\ResponseContext\HtmlTag;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Extension\ContaoExtension;
use Contao\CoreBundle\Twig\Global\ContaoVariable;
use Contao\CoreBundle\Twig\Inspector\InspectorNodeVisitor;
use Contao\CoreBundle\Twig\Inspector\Storage;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Contao\CoreBundle\Twig\Renderer\DeferredRenderer;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Loader\ChainLoader;
use Twig\Loader\FilesystemLoader;

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

    /**
     * @param array<string, string> $templates
     */
    private function createEnvironment(array $templates): Environment
    {
        $filesystemLoader = $this->createStub(ContaoFilesystemLoader::class);
        $filesystemLoader
            ->method('getAllFirstByThemeSlug')
            ->willReturnCallback(static fn (string $name): array => ['' => $name])
        ;
        $environment = new Environment(new ChainLoader([
            new ArrayLoader($templates),
            new FilesystemLoader(__DIR__.'/../../contao/templates'),
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
