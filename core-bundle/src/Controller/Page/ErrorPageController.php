<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Controller\Page;

use Contao\CoreBundle\DependencyInjection\Attribute\AsPage;
use Contao\CoreBundle\Exception\ForwardPageNotFoundException;
use Contao\CoreBundle\Routing\Page\ContentCompositionInterface;
use Contao\PageModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @internal
 */
#[AsPage('error_401', path: false)]
#[AsPage('error_403', path: false)]
#[AsPage('error_404', path: false)]
#[AsPage('error_503', path: false)]
class ErrorPageController extends AbstractPageController implements ContentCompositionInterface
{
    public function __construct(
        private readonly UriSigner $uriSigner,
        private readonly LoggerInterface|null $logger = null,
    ) {
    }

    public function __invoke(PageModel $pageModel, Request $request): Response
    {
        // Handle redirect for 401, 403, 404
        if ('error_503' !== $pageModel->type && $pageModel->autoforward && $pageModel->jumpTo) {
            $pageAdapter = $this->getContaoAdapter(PageModel::class);

            if (!$target = $pageAdapter->findById($pageModel->jumpTo)) {
                $this->logger?->error(\sprintf('Forward page ID "%s" does not exist', $pageModel->jumpTo));

                throw new ForwardPageNotFoundException('Forward page not found');
            }

            $url = $this->generateContentUrl($target, [], UrlGeneratorInterface::ABSOLUTE_URL);

            // Add the referrer so the login module can redirect back
            if ('error_401' === $pageModel->type) {
                $url .= '?'.http_build_query(['redirect' => $request->getUri()]);
                $url = $this->uriSigner->sign($url);
            }

            return new RedirectResponse($url, Response::HTTP_SEE_OTHER);
        }

        return $this->renderPage($pageModel)->setStatusCode((int) substr($pageModel->type, -3));
    }

    public function supportsContentComposition(PageModel $pageModel): bool
    {
        return 'error_503' === $pageModel->type || !$pageModel->autoforward;
    }

    protected function setCacheHeaders(Response $response, PageModel $pageModel): Response
    {
        // Never cache error pages
        $response->headers->set('Cache-Control', 'no-cache, no-store');

        return $response->setPrivate();
    }
}
