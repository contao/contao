<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Routing\ResponseContext;

use Contao\CoreBundle\Routing\ResponseContext\HtmlHeadManager\HtmlHeadManager;
use Contao\PageModel;

class CoreResponseContextFactory
{
    /**
     * @var ResponseContextAccessor
     */
    private $responseContextAccessor;

    public function __construct(ResponseContextAccessor $responseContextAccessor)
    {
        $this->responseContextAccessor = $responseContextAccessor;
    }

    public function createResponseContext(): ResponseContext
    {
        $context = new ResponseContext();
        $this->responseContextAccessor->setResponseContext($context);

        return $context;
    }

    public function createWebpageResponseContext(): WebpageResponseContext
    {
        $inner = $this->createResponseContext();

        $context = new WebpageResponseContext($inner, new HtmlHeadManager());
        $this->responseContextAccessor->setResponseContext($context);

        return $context;
    }

    public function createContaoWebpageResponseContext(PageModel $pageModel): ContaoWebpageResponseContext
    {
        $inner = $this->createWebpageResponseContext();

        $context = new ContaoWebpageResponseContext($inner, $pageModel);
        $this->responseContextAccessor->setResponseContext($context);

        return $context;
    }
}
