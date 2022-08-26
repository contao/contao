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

use Contao\CoreBundle\InsertTag\InsertTagParser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal Do not use this controller in your code
 *
 * It is supposed to be used within ESI requests that are protected by the
 * Symfony fragment URI signer. If you use it directly, make sure to add a
 * permission check, because insert tags can contain arbitrary data!
 */
class InsertTagsController
{
    private InsertTagParser $insertTagParser;

    public function __construct(InsertTagParser $insertTagParser)
    {
        $this->insertTagParser = $insertTagParser;
    }

    public function renderAction(Request $request, string $insertTag): Response
    {
        $response = new Response($this->insertTagParser->replaceInline($insertTag));
        $response->setPrivate(); // always private

        if ($clientCache = $request->query->getInt('clientCache')) {
            $response->setMaxAge($clientCache);
        } else {
            $response->headers->addCacheControlDirective('no-store');
        }

        // Special handling for the very common {{date::Y}} (e.g. in the website footer) case until
        // we have a new way to register insert tags and add that caching information to the tag itself
        if ('{{date::Y}}' === $insertTag) {
            $response->setPublic();
            $response->setExpires(new \DateTimeImmutable(date('Y').'-12-31 23:59:59'));
        }

        return $response;
    }
}
