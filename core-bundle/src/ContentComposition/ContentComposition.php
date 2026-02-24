<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\ContentComposition;

use Contao\CoreBundle\Asset\ContaoContext;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Image\PictureFactory;
use Contao\CoreBundle\Image\Preview\PreviewFactory;
use Contao\CoreBundle\Routing\Page\PageRegistry;
use Contao\CoreBundle\Twig\Renderer\RendererInterface;
use Contao\PageModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;

/**
 * @experimental
 */
class ContentComposition
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly LoggerInterface $logger,
        private readonly PictureFactory $pictureFactory,
        private readonly PreviewFactory $previewFactory,
        private readonly ContaoContext $assetsContext,
        private readonly RendererInterface $defaultRenderer,
        private readonly RendererInterface $deferredRenderer,
        private readonly RequestStack $requestStack,
        private readonly LocaleAwareInterface $translator,
        private readonly PageRegistry $pageRegistry,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function createContentCompositionBuilder(PageModel $page): ContentCompositionBuilder
    {
        $builder = new ContentCompositionBuilder(
            $this->framework,
            $this->logger,
            $this->pictureFactory,
            $this->previewFactory,
            $this->assetsContext,
            $this->defaultRenderer,
            $this->requestStack,
            $this->translator,
            $this->eventDispatcher,
            $page,
        );

        // Always use the deferred renderer for the layout template
        $builder->setRenderer($this->deferredRenderer);

        if ($template = $this->pageRegistry->getPageTemplate($page)) {
            $builder->useCustomLayoutTemplate($template);
        }

        return $builder;
    }
}
