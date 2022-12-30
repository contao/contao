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

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/_oauth/check', name: 'contao_oauth_check', defaults: ['_scope' => 'frontend'])]
class OAuthCheckController
{  
    public function __invoke(): Response
    {
        // Handled by the authenticator
        return new Response();
    }
}
