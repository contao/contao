<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Fragment\PageType;

use Contao\PageModel;

interface PageTypeRendererInterface
{
    /**
     * Checks if the renderer supports the given model.
     *
     * @param PageModel $pageModel
     *
     * @return bool
     */
    public function supports(PageModel $pageModel): bool;

    /**
     * Renders the fragment.
     *
     * @param PageModel $pageModel
     *
     * @return string|null
     */
    public function render(PageModel $pageModel): ?string;
}
