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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\UriSigner;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(defaults={"_scope" = "frontend", "_allow_preview" = true})
 *
 * @internal
 */
class FrontendPreviewController
{
    private FrontendPreviewAuthenticator $previewAuthenticator;
    private UriSigner $uriSigner;
    private Connection $connection;

    public function __construct(FrontendPreviewAuthenticator $previewAuthenticator, UriSigner $uriSigner, Connection $connection)
    {
        $this->previewAuthenticator = $previewAuthenticator;
        $this->uriSigner = $uriSigner;
        $this->connection = $connection;
    }

    /**
     * @Route("/_contao/preview/{id}", name="contao_frontend_preview", requirements={"id"="\d+"})
     */
    public function __invoke(Request $request, int $id): Response
    {
        if (!$this->uriSigner->checkRequest($request)) {
            throw new AccessDeniedException();
        }

        $link = $this->connection->fetchAssociative(
            "SELECT * FROM tl_preview WHERE id=? AND published='1' AND validUntil>UNIX_TIMESTAMP()",
            [$id]
        );

        if (false === $link) {
            throw new NotFoundHttpException('Preview link not found.');
        }

        $this->previewAuthenticator->authenticateFrontendGuest((bool) $link['showUnpublished']);

        return new RedirectResponse($link['url']);
    }
}
