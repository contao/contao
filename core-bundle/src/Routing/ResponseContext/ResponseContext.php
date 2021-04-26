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

class ResponseContext implements ResponseContextInterface
{
    /**
     * @var ResponseHeaderBag|null
     */
    private $headerBag;

    public function getHeaderBag(): ResponseHeaderBag
    {
        if (null === $this->headerBag) {
            $this->headerBag = new ResponseHeaderBag();
        }

        return $this->headerBag;
    }

    public function mapToResponse(Response $response): void
    {
        foreach ($this->getHeaderBag()->all() as $name => $values) {
            $response->headers->set($name, $values, false); // Do not replace but add
        }
    }
}
