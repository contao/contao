<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\OAuthBundle\Controller;

use Contao\OAuthBundle\ClientGenerator;;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\UriSigner;
use Symfony\Component\Routing\Annotation\Route;

class OAuthController
{
    public function __construct(private readonly Connection $db, private readonly ClientGenerator $clientGenerator, private readonly UriSigner $uriSigner)
    {
    }

    #[Route('/_oauth/connect/{moduleId}/{clientId}', name: 'contao_oauth_connect', requirements: ['module' => '\d+', 'client' => '\d+'])]
    public function connectAction(Request $request, int $moduleId, int $clientId): Response
    {
        $session = $request->getSession();

        $session->set('_oauth_module_id', $moduleId);
        $session->set('_oauth_client_id', $clientId);

        if ($request->query->has('redirect') && $this->uriSigner->check($request->getUri())) {
            $session->set('_oauth_redirect', $request->query->get('redirect'));
        }

        return $this->clientGenerator->getClientById($clientId)->redirect();
    }

    #[Route('/_oauth/check', name: 'contao_oauth_check', defaults: ['_scope' => 'frontend'])]
    public function checkAction(): Response
    {
        // Handled by the authenticator
        return new Response();
    }
}
