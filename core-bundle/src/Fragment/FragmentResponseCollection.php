<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Fragment;

use Symfony\Component\HttpFoundation\Response;

class FragmentResponseCollection
{
    /**
     * @var array<Response>
     */
    private array $responses = [];

    public function add(Response $response): void
    {
        $this->responses[] = $response;
    }

    /**
     * @return array<Response>
     */
    public function get(): array
    {
        return $this->responses;
    }
}
