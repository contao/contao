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
use Contao\CoreBundle\Fragment\AbstractFragmentRenderer;
use Contao\CoreBundle\Fragment\FragmentRegistryInterface;

class DefaultContentElementRenderer extends AbstractFragmentRenderer implements ContentElementRendererInterface
{
    /**
     * {@inheritdoc}
     */
    public function supports(ContentModel $contentModel, string $inColumn = 'main', string $scope = ContaoCoreBundle::SCOPE_FRONTEND): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function render(ContentModel $contentModel, string $inColumn = 'main', string $scope = ContaoCoreBundle::SCOPE_FRONTEND): ?string
    {
        $query = [];

        $attributes = [
            'contentModel' => $contentModel->id,
            'inColumn' => $inColumn,
            'scope' => $scope,
        ];

        $fragmentIdentifier = FragmentRegistryInterface::CONTENT_ELEMENT_FRAGMENT.'.'.$contentModel->type;

        return $this->renderFragment($fragmentIdentifier, $attributes, $query);
    }
}
