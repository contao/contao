<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Controller;

use Contao\CoreBundle\EventListener\SubrequestCacheSubscriber;
use Contao\CoreBundle\Fixtures\Controller\FragmentController;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Interop\ContextFactory;
use Contao\Model;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;
use Twig\Loader\LoaderInterface;

class AbstractFragmentControllerTest extends TestCase
{
    public function testCreateAndRenderFragmentTemplate(): void
    {
        $fragmentController = $this->getFragmentController('foo/bar');

        // Create template
        $template = $fragmentController->doCreateTemplate($this->mockClassWithProperties(Model::class));
        $this->assertSame('foo/bar', $template->getName());
        $this->assertEmpty($template->getData());

        // Get response via fragment template
        $template->set('some', 'data');
        $response = $template->getResponse();

        $this->assertSame('rendered foo/bar', $response->getContent());
        $this->assertFalse($response->headers->has('Cache-Control'));
        $this->assertTrue($response->headers->has(SubrequestCacheSubscriber::MERGE_CACHE_HEADER));

        // Get response by calling render
        $response = $fragmentController->doRender(parameters: ['some' => 'data']);

        $this->assertSame('rendered foo/bar', $response->getContent());
        $this->assertFalse($response->headers->has('Cache-Control'));
        $this->assertTrue($response->headers->has(SubrequestCacheSubscriber::MERGE_CACHE_HEADER));
    }

    public function testCreateAndRenderModifiedFragmentTemplate(): void
    {
        $twig = $this->createMock(Environment::class);
        $twig
            ->method('render')
            ->with('@Contao/modified/template.html.twig', ['some' => 'data'])
            ->willReturn('rendered modified/template')
        ;

        $fragmentController = $this->getFragmentController('original/template', $twig);

        // Create and modify template
        $template = $fragmentController->doCreateTemplate($this->mockClassWithProperties(Model::class));
        $template->setName('modified/template');
        $template->set('some', 'data');

        // Get response of modified template
        $response = $template->getResponse();

        $this->assertSame('rendered modified/template', $response->getContent());
    }

    public function testRenderUsingPreBuiltResponse(): void
    {
        $preBuiltResponse = new Response();

        $twig = $this->createMock(Environment::class);
        $twig
            ->method('render')
            ->with('@Contao/foo/bar.html.twig', [])
            ->willReturn('rendered foo/bar')
        ;

        $fragmentController = $this->getFragmentController('foo/bar', $twig);

        // GGet original response with rendered content via fragment template
        $template = $fragmentController->doCreateTemplate($this->mockClassWithProperties(Model::class));
        $response = $template->getResponse($preBuiltResponse);

        $this->assertSame($preBuiltResponse, $response);
        $this->assertSame('rendered foo/bar', $response->getContent());
        $this->assertFalse($response->headers->has(SubrequestCacheSubscriber::MERGE_CACHE_HEADER));

        // Get original response with rendered content by calling render
        $response = $fragmentController->doRender(response: $preBuiltResponse);

        $this->assertSame($preBuiltResponse, $response);
        $this->assertSame('rendered foo/bar', $response->getContent());
        $this->assertFalse($response->headers->has(SubrequestCacheSubscriber::MERGE_CACHE_HEADER));
    }

    private function getFragmentController(string $defaultTemplateName, Environment|null $twig = null): object
    {
        $loader = $this->createMock(LoaderInterface::class);
        $loader
            ->method('exists')
            ->with("@Contao/$defaultTemplateName.html.twig")
            ->willReturn(true)
        ;

        if (null === $twig) {
            $twig = $this->createMock(Environment::class);
            $twig
                ->method('render')
                ->with("@Contao/$defaultTemplateName.html.twig", ['some' => 'data'])
                ->willReturn("rendered $defaultTemplateName")
            ;
        }

        $container = new Container();
        $container->set('contao.twig.filesystem_loader', $loader);
        $container->set('contao.twig.interop.context_factory', new ContextFactory());
        $container->set('twig', $twig);

        $fragmentController = new FragmentController();
        $fragmentController->setContainer($container);
        $fragmentController->setFragmentOptions(['template' => $defaultTemplateName]);

        return $fragmentController;
    }
}
