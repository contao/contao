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
    ) {
    }

    public function renderAction(string $insertTag): Response
    {
        $response = new Response($this->insertTagParser->replace($insertTag));
        $response->setPrivate(); // always private

        return $response;
    }
}
