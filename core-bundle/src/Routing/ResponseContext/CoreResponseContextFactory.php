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
use Contao\StringUtil;

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
        $context->addLazy(HtmlHeadBag::class, static function () { return new HtmlHeadBag(); });

        return $context;
    }

    public function createContaoWebpageResponseContext(PageModel $pageModel): ResponseContext
    {
        $context = $this->createWebpageResponseContext();

        /** @var HtmlHeadBag $htmlHeadBag */
        $htmlHeadBag = $context->get(HtmlHeadBag::class);

        $title = $pageModel->pageTitle ?: StringUtil::inputEncodedToPlainText($pageModel->title ?: '');

        $htmlHeadBag
            ->setTitle($title ?: '')
            ->setMetaDescription(StringUtil::inputEncodedToPlainText($pageModel->description ?: ''))
        ;

        if ($pageModel->robots) {
            $htmlHeadBag->setMetaRobots($pageModel->robots);
        }

        return $context;
    }
}
