<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Event;

use Contao\CoreBundle\Routing\ResponseContext\ResponseContext;
use Contao\LayoutModel;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\Event;

class RenderPageEvent extends Event
{
    private Response|null $response = null;

    public function __construct(
        private readonly PageModel $pageModel,
        private readonly ResponseContext|null $responseContext,
        private LayoutModel|null $layoutModel,
    ) {
    }

    public function getPage(): PageModel
    {
        return $this->pageModel;
    }

    public function getResponseContext(): ResponseContext|null
    {
        return $this->responseContext;
    }

    public function getResponse(): Response|null
    {
        return $this->response;
    }

    public function getLayout(): LayoutModel|null
    {
        return $this->layoutModel;
    }

    public function setLayout(LayoutModel|null $layoutModel): void
    {
        $this->layoutModel = $layoutModel;
    }

    public function setResponse(Response|null $response): void
    {
        $this->response = $response;

        $this->stopPropagation();
    }
}
