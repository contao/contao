<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Controller;

use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Security\Authentication\FrontendPreviewAuthenticator;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\UriSigner;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @internal
 */
#[Route(defaults: ['_scope' => 'frontend', '_allow_preview' => true])]
class PreviewLinkController
{
    public function __construct(
        private readonly FrontendPreviewAuthenticator $previewAuthenticator,
        private readonly UriSigner $uriSigner,
        private readonly Connection $connection,
    ) {
    }

    #[Route('/_contao/preview/{id}', name: 'contao_preview_link', requirements: ['id' => '\d+'])]
    public function __invoke(Request $request, int $id): RedirectResponse
    {
        if (!$this->uriSigner->checkRequest($request)) {
            throw new AccessDeniedException();
        }

        $link = $this->connection->fetchAssociative(
            'SELECT * FROM tl_preview_link WHERE id=? AND published=1 AND expiresAt>UNIX_TIMESTAMP()',
            [$id],
        );

        if (false === $link) {
            throw new NotFoundHttpException('Preview link not found.');
        }

        $this->previewAuthenticator->authenticateFrontendGuest((bool) $link['showUnpublished'], $id);

        return new RedirectResponse($link['url']);
    }
}
