<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Event;

use Contao\CoreBundle\Routing\ResponseContext\ResponseContext;
use Contao\CoreBundle\Twig\LayoutTemplate;
use Contao\LayoutModel;
use Contao\PageModel;
use Symfony\Contracts\EventDispatcher\Event;

class LayoutEvent extends Event
{
    /**
     * @internal
     */
    public function __construct(
        private readonly LayoutTemplate $template,
        private readonly PageModel $page,
        private readonly LayoutModel|null $layout,
        private readonly ResponseContext|null $responseContext,
    ) {
    }

    public function getTemplate(): LayoutTemplate
    {
        return $this->template;
    }

    public function getPage(): PageModel
    {
        return $this->page;
    }

    public function getLayout(): LayoutModel|null
    {
        return $this->layout;
    }

    public function getResponseContext(): ResponseContext|null
    {
        return $this->responseContext;
    }
}
