<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Controller;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal Do not use this controller in your code
 *
 * It is supposed to be used within ESI requests that are protected by the Symfony
 * fragment URI signer. If you use it directly, make sure to add a permission
 * check, because insert tags can contain arbitrary data!
 */
class InsertTagsController
{
    public function __construct(
        private readonly InsertTagParser $insertTagParser,
        private readonly ContaoFramework $framework,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function renderAction(Request $request, string $insertTag, PageModel|null $pageModel): Response
    {
        $this->framework->initialize();
        $pageModelBefore = $GLOBALS['objPage'] ?? null;

        $this->requestStack->push($request->duplicate([], [], null, null, [], array_merge($request->server->all(), ['REQUEST_URI' => '/'])));
        $GLOBALS['objPage'] = $pageModel;

        try {
            $response = new Response($this->insertTagParser->replaceInline($insertTag));
            $response->setPrivate(); // always private
        } finally {
            $GLOBALS['objPage'] = $pageModelBefore;
            $this->requestStack->pop();
        }

        return $response;
    }
}
