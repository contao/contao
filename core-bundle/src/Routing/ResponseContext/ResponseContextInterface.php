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

    public function mapHeaderBagToResponse(Response $response): void;
}
