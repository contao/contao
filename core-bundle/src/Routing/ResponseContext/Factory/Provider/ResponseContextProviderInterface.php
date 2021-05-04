<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Routing\ResponseContext\Factory\Provider;

use Contao\CoreBundle\Routing\ResponseContext\ResponseContextInterface;

interface ResponseContextProviderInterface
{
    /**
     * @template T of ResponseContextInterface
     * @psalm-param class-string<T> $responseContextClassName
     */
    public function supports(string $responseContextClassName): bool;

    /**
     * @template T of ResponseContextInterface
     * @psalm-param class-string<T> $responseContextClassName
     *
     * @return T
     */
    public function create(string $responseContextClassName): ResponseContextInterface;
}
