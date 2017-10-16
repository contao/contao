<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Fragment\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\ContaoCoreBundle;

class DelegatingContentElementRenderer implements ContentElementRendererInterface
{
    /**
     * @var ContentElementRendererInterface[]
     */
    private $renderers = [];

    /**
     * Adds a renderer.
     *
     * @param ContentElementRendererInterface $renderer
     */
    public function addRenderer(ContentElementRendererInterface $renderer): void
    {
        $this->renderers[] = $renderer;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(ContentModel $contentModel, string $inColumn = 'main', string $scope = ContaoCoreBundle::SCOPE_FRONTEND): bool
    {
        foreach ($this->renderers as $renderer) {
            if ($renderer->supports($contentModel, $inColumn, $scope)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function render(ContentModel $contentModel, string $inColumn = 'main', string $scope = ContaoCoreBundle::SCOPE_FRONTEND): ?string
    {
        foreach ($this->renderers as $renderer) {
            if ($renderer->supports($contentModel, $inColumn, $scope)) {
                return $renderer->render($contentModel, $inColumn, $scope);
            }
        }

        return null;
    }
}
