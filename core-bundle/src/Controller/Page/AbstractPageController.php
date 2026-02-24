<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Controller\Page;

use Contao\CoreBundle\ContentComposition\ContentComposition;
use Contao\CoreBundle\Controller\AbstractController;
use Contao\CoreBundle\Event\RenderPageEvent;
use Contao\CoreBundle\EventListener\SubrequestCacheSubscriber;
use Contao\CoreBundle\Routing\Page\PageRegistry;
use Contao\CoreBundle\Routing\ResponseContext\CoreResponseContextFactory;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContext;
use Contao\FrontendIndex;
use Contao\LayoutModel;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractPageController extends AbstractController
{
    public static function getSubscribedServices(): array
    {
        return [
            ...parent::getSubscribedServices(),
            'contao.routing.response_context_factory' => CoreResponseContextFactory::class,
            'contao.content_composition' => ContentComposition::class,
            'contao.routing.page_registry' => PageRegistry::class,
        ];
    }

    protected function renderPage(PageModel $pageModel, ResponseContext|null $responseContext = null): Response
    {
        if ($this->container->get('contao.routing.page_registry')->getPageTemplate($pageModel)) {
            return $this->handleModernLayout($pageModel, $responseContext);
        }

        $layoutModel = $this->getLayout($pageModel);

        $event = new RenderPageEvent($pageModel, $responseContext, $layoutModel);
        $this->container->get('event_dispatcher')->dispatch($event);

        if ($response = $event->getResponse()) {
            return $response;
        }

        $layoutType = $event->getLayout()?->type;

        return match ($layoutType) {
            'modern' => $this->handleModernLayout($pageModel, $responseContext),
            'default' => $this->handleDefaultLayout($pageModel, $responseContext),
            default => throw new \LogicException(\sprintf('Unknown layout type "%s"', $layoutType)),
        };
    }

    protected function getLayout(PageModel $page): LayoutModel|null
    {
        $framework = $this->container->get('contao.framework');
        $framework->initialize();

        return $framework->getAdapter(LayoutModel::class)->findById($page->layout);
    }

    protected function handleDefaultLayout(PageModel $pageModel, ResponseContext|null $responseContext): Response
    {
        if ($responseContext) {
            $this->container->get('contao.routing.response_context_accessor')->setResponseContext($responseContext);
        }

        return $this->container->get('contao.framework')->createInstance(FrontendIndex::class)->renderLegacy($pageModel);
    }

    protected function handleModernLayout(PageModel $pageModel, ResponseContext|null $responseContext): Response
    {
        $responseContext ??= $this->container
            ->get('contao.routing.response_context_factory')
            ->createContaoWebpageResponseContext($pageModel)
        ;

        $layoutTemplate = $this->container
            ->get('contao.content_composition')
            ->createContentCompositionBuilder($pageModel)
            ->setResponseContext($responseContext)
            ->buildLayoutTemplate()
        ;

        $response = $layoutTemplate->getResponse();

        $this->container->get('contao.routing.response_context_accessor')->finalizeCurrentContext($response);

        // Set cache headers
        $response->headers->set(SubrequestCacheSubscriber::MERGE_CACHE_HEADER, '1');
        $this->setCacheHeaders($response, $pageModel);

        return $response;
    }
}
