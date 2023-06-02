<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Event;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\Event;

class PreviewUrlConvertEvent extends Event
{
    private string|null $url = null;
    private Response|null $response = null;

    public function __construct(private readonly Request $request)
    {
    }

    public function getUrl(): string|null
    {
        return $this->url;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getResponse(): Response|null
    {
        return $this->response;
    }

    public function setResponse(Response $response): void
    {
        $this->response = $response;
        $this->stopPropagation();
    }
}
