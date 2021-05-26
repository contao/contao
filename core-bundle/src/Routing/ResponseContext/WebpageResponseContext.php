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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class WebpageResponseContext implements ResponseContextInterface, HtmlHeadManagerProvidingInterface
{
    /**
     * @var ResponseContextInterface
     */
    private $inner;

    /**
     * @var HtmlHeadManager
     */
    private $htmlHeadManager;

    public function __construct(ResponseContextInterface $inner, HtmlHeadManager $htmlHeadManager)
    {
        $this->inner = $inner;
        $this->htmlHeadManager = $htmlHeadManager;
    }

    public function getHtmlHeadManager(): HtmlHeadManager
    {
        return $this->htmlHeadManager;
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
