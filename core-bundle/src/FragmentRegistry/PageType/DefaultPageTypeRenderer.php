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

use Contao\CoreBundle\DependencyInjection\Compiler\FragmentRegistryPass;
use Contao\CoreBundle\FragmentRegistry\AbstractFragmentRenderer;
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
        $fragmentIdentifier = FragmentRegistryPass::TAG_FRAGMENT_PAGE_TYPE.'.'.$pageModel->type;

        return $this->renderFragment($fragmentIdentifier, [], [], 'inline');
    }
}
