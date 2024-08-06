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
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\FrontendUser;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\User;
use Psr\Log\LoggerInterface;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Security\Http\Authenticator\TwoFactorAuthenticator;
use Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\TrustedDeviceManagerInterface;
use Symfony\Bundle\SecurityBundle\Security\FirewallMap;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class AuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    use TargetPathTrait;

    private User|null $user = null;

    /**
     * @internal
     */
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly TrustedDeviceManagerInterface $trustedDeviceManager,
        private readonly FirewallMap $firewallMap,
        private readonly ContentUrlGenerator $urlGenerator,
        private readonly UriSigner $uriSigner,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly LoggerInterface|null $logger = null,
    ) {
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

        if ($token instanceof TwoFactorTokenInterface) {
            if ($this->uriSigner->checkRequest($request) && $request->query->getBoolean(TwoFactorAuthenticator::FLAG_2FA_COMPLETE)) {
                $authenticatedToken = $token->getAuthenticatedToken();
                $authenticatedToken->setAttribute(TwoFactorAuthenticator::FLAG_2FA_COMPLETE, true);

                $this->tokenStorage->setToken($authenticatedToken);
            } else {
                $this->user->save();

                $response = new RedirectResponse($request->getUri());

                // Used by the TwoFactorListener to redirect a user back to the authentication page
                if ($request->hasSession() && $request->isMethodSafe() && !$request->isXmlHttpRequest()) {
                    $this->saveTargetPath($request->getSession(), $token->getFirewallName(), $request->getUri());
                }

                return $response;
            }
        }

        $this->user->lastLogin = $this->user->currentLogin;
        $this->user->currentLogin = time();
        $this->user->save();

        if ($request->request->has('trusted')) {
            $firewallConfig = $this->firewallMap->getFirewallConfig($request);

            if (!$this->trustedDeviceManager->isTrustedDevice($user, $firewallConfig->getName())) {
                $this->trustedDeviceManager->addTrustedDevice($token->getUser(), $firewallConfig->getName());
            }
        }

        $response = new RedirectResponse($this->determineTargetUrl($request));

        $this->logger?->info(
            \sprintf('User "%s" has logged in', $this->user->username),
            ['contao' => new ContaoContext(__METHOD__, ContaoContext::ACCESS, $this->user->username)],
        );

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
            return $this->urlGenerator->generate($groupPage, [], UrlGeneratorInterface::ABSOLUTE_URL);
        }

        return $this->decodeTargetPath($request);
    }

    private function decodeTargetPath(Request $request): string
    {
        $targetPath = $request->request->get('_target_path');

        if (!\is_string($targetPath) && $this->uriSigner->checkRequest($request)) {
            $targetPath = $request->query->get('_target_path');
        }

        if (!\is_string($targetPath)) {
            throw new BadRequestHttpException('Missing form field "_target_path". You probably need to adjust your custom login template.');
        }

        return base64_decode($targetPath, true);
    }
}
