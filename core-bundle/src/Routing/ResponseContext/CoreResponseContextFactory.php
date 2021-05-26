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

use Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag\HtmlHeadBag;
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

    public function createWebpageResponseContext(): ResponseContext
    {
        $context = $this->createResponseContext();
        $context->add(new HtmlHeadBag());

        return $context;
    }

    public function createContaoWebpageResponseContext(PageModel $pageModel): ResponseContext
    {
        $context = $this->createWebpageResponseContext();

        if (!$context->has(HtmlHeadBag::class)) {
            return $context;
        }

        /** @var HtmlHeadBag $htmlHeadBag */
        $htmlHeadBag = $context->get(HtmlHeadBag::class);

        $htmlHeadBag
            ->setTitle($pageModel->pageTitle ?: $pageModel->title ?: '')
            ->setMetaDescription(str_replace(["\n", "\r", '"'], [' ', '', ''], $pageModel->description ?: ''))
        ;

        if ($pageModel->robots) {
            $htmlHeadBag
                ->setMetaRobots($pageModel->robots)
            ;
        }

        return $context;
    }
}
