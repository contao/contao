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

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\FrontendUser;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Contao\User;
use Psr\Log\LoggerInterface;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\TrustedDeviceManagerInterface;
use Symfony\Bundle\SecurityBundle\Security\FirewallConfig;
use Symfony\Bundle\SecurityBundle\Security\FirewallMap;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class AuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    use TargetPathTrait;

    private ContaoFramework $framework;
    private TrustedDeviceManagerInterface $trustedDeviceManager;
    private FirewallMap $firewallMap;
    private ?LoggerInterface $logger;
    private ?User $user = null;

    /**
     * @internal
     */
    public function __construct(ContaoFramework $framework, TrustedDeviceManagerInterface $trustedDeviceManager, FirewallMap $firewallMap, LoggerInterface $logger = null)
    {
        $this->framework = $framework;
        $this->trustedDeviceManager = $trustedDeviceManager;
        $this->firewallMap = $firewallMap;
        $this->logger = $logger;
    }

    /**
     * Redirects the authenticated user.
     *
     * @return RedirectResponse
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return new RedirectResponse($this->determineTargetUrl($request));
        }

        $this->user = $user;

        // Reset login attempts and locked values
        $this->user->loginAttempts = 0;
        $this->user->locked = 0;

        if ($token instanceof TwoFactorTokenInterface) {
            $this->user->save();

            $response = new RedirectResponse($request->getUri());

            // Used by the TwoFactorListener to redirect a user back to the authentication page
            if ($request->hasSession() && $request->isMethodSafe() && !$request->isXmlHttpRequest()) {
                $this->saveTargetPath($request->getSession(), $token->getFirewallName(), $request->getUri());
            }

            return $response;
        }

        $this->user->lastLogin = $this->user->currentLogin;
        $this->user->currentLogin = time();
        $this->user->save();

        if ($request->request->has('trusted')) {
            /** @var FirewallConfig $firewallConfig */
            $firewallConfig = $this->firewallMap->getFirewallConfig($request);

            if (!$this->trustedDeviceManager->isTrustedDevice($user, $firewallConfig->getName())) {
                $this->trustedDeviceManager->addTrustedDevice($token->getUser(), $firewallConfig->getName());
            }
        }

        $response = new RedirectResponse($this->determineTargetUrl($request));

        if (null !== $this->logger) {
            $this->logger->info(
                sprintf('User "%s" has logged in', $this->user->username),
                ['contao' => new ContaoContext(__METHOD__, ContaoContext::ACCESS, $this->user->username)]
            );
        }

        $this->triggerPostLoginHook();

        if ($request->hasSession() && method_exists($token, 'getFirewallName')) {
            $this->removeTargetPath($request->getSession(), $token->getFirewallName());
        }

        return $response;
    }

    protected function determineTargetUrl(Request $request): string
    {
        if (!$this->user instanceof FrontendUser || $request->request->get('_always_use_target_path')) {
            return $this->decodeTargetPath($request);
        }

        $pageModelAdapter = $this->framework->getAdapter(PageModel::class);
        $groups = StringUtil::deserialize($this->user->groups, true);
        $groupPage = $pageModelAdapter->findFirstActiveByMemberGroups($groups);

        if ($groupPage instanceof PageModel) {
            return $groupPage->getAbsoluteUrl();
        }

        return $this->decodeTargetPath($request);
    }

    private function triggerPostLoginHook(): void
    {
        $this->framework->initialize();

        if (empty($GLOBALS['TL_HOOKS']['postLogin']) || !\is_array($GLOBALS['TL_HOOKS']['postLogin'])) {
            return;
        }

        trigger_deprecation('contao/core-bundle', '4.5', 'Using the "postLogin" hook has been deprecated and will no longer work in Contao 5.0.');

        $system = $this->framework->getAdapter(System::class);

        foreach ($GLOBALS['TL_HOOKS']['postLogin'] as $callback) {
            $system->importStatic($callback[0])->{$callback[1]}($this->user);
        }
    }

    private function decodeTargetPath(Request $request): string
    {
        $targetPath = $request->request->get('_target_path');

        if (!\is_string($targetPath)) {
            throw new BadRequestHttpException('Missing form field "_target_path". You probably need to adjust your custom login template.');
        }

        return base64_decode($targetPath, true);
    }
}
