<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\FragmentRegistry\PageType;

use Contao\PageModel;

class DelegatingPageTypeRenderer implements PageTypeRendererInterface
{
    /**
     * @var PageTypeRendererInterface[]
     */
    private $renderers = [];

    /**
     * @param PageTypeRendererInterface[] $renderers
     */
    public function __construct(array $renderers)
    {
        foreach ($renderers as $renderer) {
            $this->addRenderer($renderer);
        }
    }

    /**
     * Adds a renderer.
     *
     * @param PageTypeRendererInterface $renderer
     */
    public function addRenderer(PageTypeRendererInterface $renderer): void
    {
        $this->renderers[] = $renderer;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(PageModel $pageModel): bool
    {
        foreach ($this->renderers as $renderer) {
            if ($renderer->supports($pageModel)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function render(PageModel $pageModel): ?string
    {
        foreach ($this->renderers as $renderer) {
            if ($renderer->supports($pageModel)) {
                return $renderer->render($pageModel);
            }
        }

        return null;
    }
}
