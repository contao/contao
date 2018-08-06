<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Security\Authentication;

use Contao\CoreBundle\Monolog\ContaoContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccountStatusException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationFailureHandler;

class AuthenticationFailureHandler extends DefaultAuthenticationFailureHandler
{
    /**
     * Logs the security exception to the Contao back end.
     *
     * @throws \RuntimeException
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        if (null === $this->logger) {
            return parent::onAuthenticationFailure($request, $exception);
        }

        if ($exception instanceof AccountStatusException && ($user = $exception->getUser()) instanceof UserInterface) {
            $username = $user->getUsername();
        } else {
            $username = $request->request->get('username');
        }

        $this->logger->info(
            $exception->getMessage(),
            ['contao' => new ContaoContext(__METHOD__, ContaoContext::ACCESS, $username)]
        );

        return parent::onAuthenticationFailure($request, $exception);
    }
}
