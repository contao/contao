<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\FragmentRegistry\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\DependencyInjection\Compiler\FragmentRegistryPass;
use Contao\CoreBundle\FragmentRegistry\AbstractFragmentRenderer;

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

        $fragmentIdentifier = FragmentRegistryPass::TAG_FRAGMENT_CONTENT_ELEMENT.'.'.$contentModel->type;

        return $this->renderFragment($fragmentIdentifier, $attributes, $query);
    }
}
