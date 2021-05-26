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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class ContaoWebpageResponseContext implements ResponseContextInterface, HtmlHeadManagerProvidingInterface
{
    /**
     * @var ResponseContextInterface&HtmlHeadManagerProvidingInterface
     */
    private $inner;

    /**
     * @param ResponseContextInterface&HtmlHeadManagerProvidingInterface $inner
     */
    public function __construct($inner, PageModel $pageModel)
    {
        // So much looking forward to intersection types!
        if (!$inner instanceof ResponseContextInterface || !$inner instanceof HtmlHeadManagerProvidingInterface) {
            throw new \InvalidArgumentException('First argument must implement correct interfaces.!');
        }

        $this->inner = $inner;

        $this
            ->getHtmlHeadManager()
            ->setTitle($pageModel->pageTitle ?: $pageModel->title ?: '')
            ->setMetaDescription(str_replace(["\n", "\r", '"'], [' ', '', ''], $pageModel->description ?: ''))
        ;

        if ($pageModel->robots) {
            $this
                ->getHtmlHeadManager()
                ->setMetaRobots($pageModel->robots)
            ;
        }
    }

    public function getHtmlHeadManager(): HtmlHeadManager
    {
        return $this->inner->getHtmlHeadManager();
    }

    public function getHeaderBag(): ResponseHeaderBag
    {
        return $this->inner->getHeaderBag();
    }

    public function finalize(Response $response): ResponseContextInterface
    {
        $this->inner->finalize($response);

        return $this;
    }
}
