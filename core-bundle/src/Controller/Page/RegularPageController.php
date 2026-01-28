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
use Contao\CoreBundle\EventListener\SubrequestCacheSubscriber;
use Contao\CoreBundle\Routing\ResponseContext\CoreResponseContextFactory;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContextAccessor;
use Contao\CoreBundle\Twig\Renderer\RendererInterface;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\Response;

class RegularPageController extends AbstractController
{
    public function __construct(
        private readonly ContentComposition $contentComposition,
        private readonly CoreResponseContextFactory $responseContextFactory,
        private readonly ResponseContextAccessor $responseContextAccessor,
        private readonly RendererInterface $deferredRenderer,
    ) {
    }

    public function __invoke(PageModel $page): Response
    {
        $responseContext = $this->responseContextFactory->createContaoWebpageResponseContext($page);

        $layoutTemplate = $this->contentComposition
            ->createContentCompositionBuilder($page)
            ->setResponseContext($responseContext)
            ->setFragmentRenderer($this->deferredRenderer)
            ->buildLayoutTemplate()
        ;

        $response = $layoutTemplate->getResponse();
        $this->responseContextAccessor->finalizeCurrentContext($response);

        // Set cache headers
        $response->headers->set(SubrequestCacheSubscriber::MERGE_CACHE_HEADER, '1');
        $this->setCacheHeaders($response, $page);

        return $response;
    }
}
