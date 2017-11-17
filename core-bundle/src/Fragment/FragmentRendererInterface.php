<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Fragment;

use Contao\CoreBundle\Fragment\Reference\FragmentReference;

interface FragmentRendererInterface
{
    /**
     * Renders a fragment.
     *
     * @param FragmentReference $reference
     *
     * @return string|null
     */
    public function render(FragmentReference $reference): ?string;
}
