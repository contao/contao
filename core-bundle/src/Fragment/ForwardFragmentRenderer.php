<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Fragment;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ControllerReference;
use Symfony\Component\HttpKernel\Fragment\InlineFragmentRenderer;

/**
 * Implements the forward rendering strategy.
 *
 * The default "inline" renderer creates a new, almost blank request object for
 * each sub-request, which means a fragment controller will not get POST data or
 * other main request configuration. Contrary to regular Symfony inline
 * fragments, however, the Contao fragments are supposed to handle POST data.
 */
class ForwardFragmentRenderer extends InlineFragmentRenderer
{
    #[\Override]
    public function getName(): string
    {
        return 'forward';
    }

    /**
     * @param ControllerReference|string $uri
     */
    #[\Override]
    protected function createSubRequest($uri, Request $request): Request
    {
        return $request->duplicate();
    }
}
