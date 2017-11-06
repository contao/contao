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

use Contao\CoreBundle\Fragment\AbstractFragmentRenderer;
use Contao\CoreBundle\Fragment\FragmentRegistryInterface;
use Contao\PageModel;

class DefaultPageTypeRenderer extends AbstractFragmentRenderer implements PageTypeRendererInterface
{
    /**
     * {@inheritdoc}
     */
    public function supports(PageModel $pageModel): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function render(PageModel $pageModel): ?string
    {
        $fragmentIdentifier = FragmentRegistryInterface::PAGE_TYPE_FRAGMENT.'.'.$pageModel->type;

        return $this->renderFragment($fragmentIdentifier, [], [], 'inline');
    }
}
