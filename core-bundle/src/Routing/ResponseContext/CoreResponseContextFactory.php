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

use Contao\CoreBundle\Routing\ResponseContext\JsonLd\JsonLdManager;
use Contao\PageModel;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class CoreResponseContextFactory
{
    /**
     * @var ResponseContextAccessor
     */
    private $responseContextAccessor;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(ResponseContextAccessor $responseContextAccessor, EventDispatcherInterface $eventDispatcher)
    {
        $this->responseContextAccessor = $responseContextAccessor;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function createWebpageResponseContext(): WebpageResponseContext
    {
        $context = new WebpageResponseContext(new JsonLdManager($this->eventDispatcher));
        $this->responseContextAccessor->setResponseContext($context);

        return $context;
    }

    public function createResponseContext(): ResponseContext
    {
        $context = new ResponseContext();
        $this->responseContextAccessor->setResponseContext($context);

        return $context;
    }

    public function createContaoWebpageResponseContext(PageModel $pageModel): ContaoWebpageResponseContext
    {
        $context = new ContaoWebpageResponseContext($pageModel);
        $this->responseContextAccessor->setResponseContext($context);

        return $context;
    }
}
