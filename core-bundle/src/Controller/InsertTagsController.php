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
use Contao\CoreBundle\InsertTag\OutputType;
use Contao\StringUtil;
use FOS\HttpCache\ResponseTagger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @internal Do not use this controller in your code
 *
 * It is supposed to be used within ESI requests that are protected by the
 * Symfony fragment URI signer. If you use it directly, make sure to add a
 * permission check, because insert tags can contain arbitrary data!
 */
class InsertTagsController
{
    public function __construct(
        private readonly InsertTagParser $insertTagParser,
        private readonly ResponseTagger|null $responseTagger,
    ) {
    }

    public function renderAction(Request $request, string $insertTag): Response
    {
        if (!str_starts_with($insertTag, '{{') || !str_ends_with($insertTag, '}}')) {
            throw new BadRequestHttpException(sprintf('Invalid insert tag "%s"', $insertTag));
        }

        $result = $this->insertTagParser->renderTag(substr($insertTag, 2, -2));

        if (OutputType::html === $result->getOutputType()) {
            $response = new Response($result->getValue());
        } else {
            $response = new Response(StringUtil::specialchars($result->getValue()));
        }

        $response->setPrivate(); // always private

        if ($clientCache = $request->query->getInt('clientCache')) {
            $response->setMaxAge($clientCache);
        } else {
            $response->headers->addCacheControlDirective('no-store');
        }

        if ($result->getExpiresAt()) {
            $response->setPublic();
            $response->setExpires($result->getExpiresAt());
            $response->headers->removeCacheControlDirective('no-store');
        }

        $this->responseTagger?->addTags($result->getCacheTags());

        return $response;
    }
}
