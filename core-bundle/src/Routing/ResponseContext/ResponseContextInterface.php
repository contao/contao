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

interface ResponseContextInterface
{
    public const REQUEST_ATTRIBUTE_NAME = '_contao_response_context';

    public function getHeaderBag(): PartialResponseHeaderBag;

    /**
     * Terminates a response context.
     * After calling this method, the response context SHOULD become immutable.
     * As long as PHP does not support immutable classes, this remains a SHOULD requirement.
     *
     * Services accessing the response context after it was terminated MUST be
     * able to still read from it for e.g. logging purposes.
     *
     * This is the place where you COULD dispatch final events and then apply
     * the context to the response.
     */
    public function terminate(Response $response): void;

    public function isTerminated(): bool;
}
