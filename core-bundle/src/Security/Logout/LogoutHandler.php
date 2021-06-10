<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Security\Logout;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\System;
use Contao\User;
use Psr\Log\LoggerInterface;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Logout\LogoutHandlerInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LogoutHandler implements LogoutHandlerInterface
{
    use TargetPathTrait;

    /**
     * @var ContaoFramework
     */
    private $framework;

    /**
     * @var LoggerInterface|null
     */
    private $logger;

    /**
     * @internal Do not inherit from this class; decorate the "contao.security.logout_handler" service instead
     */
    public function __construct(ContaoFramework $framework, LoggerInterface $logger = null)
    {
        $this->framework = $framework;
        $this->logger = $logger;
    }

    public function logout(Request $request, ?Response $response, TokenInterface $token): void
    {
        if ($request->hasSession()) {
            // Backwards compatibility with symfony/security <5.2
            if (method_exists($token, 'getFirewallName')) {
                $this->removeTargetPath($request->getSession(), $token->getFirewallName());
            } elseif (method_exists($token, 'getProviderKey')) {
                $this->removeTargetPath($request->getSession(), $token->getProviderKey());
            }
        }

        $user = $token->getUser();

        if (!$user instanceof User || $token instanceof TwoFactorTokenInterface) {
            return;
        }

        if (null !== $this->logger) {
            $this->logger->info(
                sprintf('User "%s" has logged out', $user->username),
                ['contao' => new ContaoContext(__METHOD__, ContaoContext::ACCESS, $user->username)]
            );
        }

        $this->triggerPostLogoutHook($user);
    }

    private function triggerPostLogoutHook(User $user): void
    {
        $this->framework->initialize();

        if (empty($GLOBALS['TL_HOOKS']['postLogout']) || !\is_array($GLOBALS['TL_HOOKS']['postLogout'])) {
            return;
        }

        trigger_deprecation('contao/core-bundle', '4.5', 'Using the "postLogout" hook has been deprecated and will no longer work in Contao 5.0.');

        /** @var System $system */
        $system = $this->framework->getAdapter(System::class);

        $GLOBALS['TL_USERNAME'] = $user->getUsername();

        foreach ($GLOBALS['TL_HOOKS']['postLogout'] as $callback) {
            $system->importStatic($callback[0])->{$callback[1]}($user);
        }
    }
}
