<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2018 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Fragment;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Fragment\InlineFragmentRenderer;

/**
 * Implements the forward rendering strategy.
 *
 * The default "inline" renderer creates a new, almost blank request object for
 * each subrequest, which means a fragment controller will not get POST data or
 * other main request configuration. Contrary to regular Symfony inline
 * fragments, however, the Contao fragments are supposed to handle POST data.
 */
class ForwardFragmentRenderer extends InlineFragmentRenderer
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'forward';
    }

    /**
     * {@inheritdoc}
     */
    protected function createSubRequest($uri, Request $request): Request
    {
        return $request->duplicate();
    }
}
