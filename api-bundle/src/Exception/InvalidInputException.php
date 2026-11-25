<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

final class InvalidInputException extends \InvalidArgumentException implements HttpExceptionInterface
{
    public function getStatusCode(): int
    {
        return Response::HTTP_UNPROCESSABLE_ENTITY;
    }

    public function getHeaders(): array
    {
        return [];
    }
}
