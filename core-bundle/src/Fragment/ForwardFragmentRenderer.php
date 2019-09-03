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
use Symfony\Component\HttpKernel\Fragment\InlineFragmentRenderer;
use Symfony\Component\HttpKernel\HttpCache\ResponseCacheStrategy;

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
     * @var ResponseCacheStrategy[]
     */
    private $stack = [];

    /**
     * @var ResponseCacheStrategy|null
     */
    private $current;

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'forward';
    }

    public function render($uri, Request $request, array $options = [])
    {
        $response = parent::render($uri, $request, $options);

        if ($this->current && $response->headers->has('Cache-Control')) {
            $this->current->add($response);
        }

        return $response;
    }


    public function pushStrategy(ResponseCacheStrategy $strategy)
    {
        if ($this->current) {
            $this->stack[] = $this->current;
        }

        $this->current = $strategy;
    }

    public function popStrategy()
    {
        if (0 === \count($this->stack)) {
            $this->current = null;
            return;
        }

        $this->current = array_pop($this->stack);
    }

    /**
     * {@inheritdoc}
     */
    protected function createSubRequest($uri, Request $request): Request
    {
        return $request->duplicate();
    }
}
