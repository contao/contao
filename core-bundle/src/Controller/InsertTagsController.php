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

use Contao\CoreBundle\EventListener\SubrequestCacheSubscriber;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

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
        private readonly HttpKernelInterface $kernel,
    ) {
    }

    public function renderAction(Request $request, string $insertTag, PageModel|null $pageModel): Response
    {
        $this->framework->initialize();
        $pageModelBefore = $GLOBALS['objPage'] ?? null;
        $GLOBALS['objPage'] = $pageModel;

        $subRequest = $request->duplicate(
            [],
            [],
            ['_controller' => self::class.'::renderForwardedAction', 'insertTag' => $insertTag, 'pageModel' => $pageModel],
            null,
            [],
            array_merge($request->server->all(), ['REQUEST_URI' => '/']),
        );

        try {
            $response = $this->kernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
        } finally {
            if (null === $pageModelBefore) {
                unset($GLOBALS['objPage']);
            } else {
                $GLOBALS['objPage'] = $pageModelBefore;
            }
        }

        return $response;
    }

    /**
     * The main action forwards to this one with a modified request that has an empty
     * request URI. This ensures that the internal fragment URL does not get used by
     * any nested insert tags.
     */
    public function renderForwardedAction(string $insertTag): Response
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/html');
        $response->headers->set(SubrequestCacheSubscriber::MERGE_CACHE_HEADER, '1');
        $response->setPublic();
        $response->setContent($this->insertTagParser->replaceInline($insertTag));

        return $response;
    }
}
