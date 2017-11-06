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

interface ContentElementRendererInterface
{
    /**
     * Checks if the renderer supports the given model.
     *
     * @param ContentModel $contentModel
     * @param string       $inColumn
     * @param string       $scope
     *
     * @return bool
     */
    public function supports(ContentModel $contentModel, string $inColumn = 'main', string $scope = ContaoCoreBundle::SCOPE_FRONTEND): bool;

    /**
     * Renders the fragment.
     *
     * @param ContentModel $contentModel
     * @param string       $inColumn
     * @param string       $scope
     *
     * @return null|string
     */
    public function render(ContentModel $contentModel, string $inColumn = 'main', string $scope = ContaoCoreBundle::SCOPE_FRONTEND): ?string;
}
