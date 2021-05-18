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

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

interface ResponseContextInterface
{
    public const REQUEST_ATTRIBUTE_NAME = '_contao_response_context';

    public function getHeaderBag(): ResponseHeaderBag;

    /**
     * Every controller is free to call this method or not. After all, it's the
     * controller that specifies the response context and which parts of it it
     * wants to apply. The finalize() method is here to apply common tasks
     * which the response context finds useful from its perspective. However,
     * it's always the controller that makes the last call.
     */
    public function finalize(Response $response): self;
}
