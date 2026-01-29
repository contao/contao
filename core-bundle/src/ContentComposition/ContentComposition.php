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
use Contao\CoreBundle\Twig\Renderer\DefaultRenderer;
use Contao\PageModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @experimental
 */
final class ContentComposition
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly LoggerInterface $logger,
        private readonly PictureFactory $pictureFactory,
        private readonly PreviewFactory $previewFactory,
        private readonly ContaoContext $assetsContext,
        private readonly DefaultRenderer $defaultRenderer,
        private readonly RequestStack $requestStack,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function createContentCompositionBuilder(PageModel $page): ContentCompositionBuilder
    {
        return new ContentCompositionBuilder(
            $this->framework,
            $this->logger,
            $this->pictureFactory,
            $this->previewFactory,
            $this->assetsContext,
            $this->defaultRenderer,
            $this->requestStack,
            $this->translator,
            $page,
        );
    }
}
