<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Security\Authenticator;

use Contao\CoreBundle\Exception\ResponseException;
use Contao\CoreBundle\Routing\Page\PageRegistry;
use Contao\CoreBundle\Routing\PageFinder;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\PageModel;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Security\Http\Authenticator\Passport\Credentials\TwoFactorCodeCredentials;
use Scheb\TwoFactorBundle\Security\Http\Authenticator\TwoFactorAuthenticator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\InteractiveAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\PasswordUpgradeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Http\ParameterBagUtils;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class ContaoLoginAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface, InteractiveAuthenticatorInterface
{
    use TargetPathTrait;

    private readonly array $options;

    /**
     * @param UserProviderInterface<UserInterface> $userProvider
     */
    public function __construct(
        private readonly UserProviderInterface $userProvider,
        private readonly AuthenticationSuccessHandlerInterface $successHandler,
        private readonly AuthenticationFailureHandlerInterface $failureHandler,
        private readonly ScopeMatcher $scopeMatcher,
        private readonly RouterInterface $router,
        private readonly UriSigner $uriSigner,
        private readonly PageFinder $pageFinder,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly PageRegistry $pageRegistry,
        private readonly HttpKernelInterface $httpKernel,
        private readonly RequestStack $requestStack,
        private readonly TwoFactorAuthenticator $twoFactorAuthenticator,
        array $options,
    ) {
        $this->options = [
            'username_parameter' => 'username',
            'password_parameter' => 'password',
            'check_path' => '/login_check',
            'post_only' => true,
            'enable_csrf' => false,
            'csrf_parameter' => '_csrf_token',
            'csrf_token_id' => 'authenticate',
            ...$options,
        ];
    }

    public function start(Request $request, AuthenticationException|null $authException = null): RedirectResponse|Response
    {
        if ($this->scopeMatcher->isBackendRequest($request)) {
            return $this->redirectToBackend($request);
        }

        $errorPage = $this->pageFinder->findFirstPageOfTypeForRequest($request, 'error_401');

        if (!$errorPage) {
            throw new UnauthorizedHttpException('', 'No error_401 page found.', $authException);
        }

        $errorPage->loadDetails();
        $errorPage->protected = false;

        $route = $this->pageRegistry->getRoute($errorPage);
        $subRequest = $request->duplicate(null, null, $route->getDefaults());

        try {
            return $this->httpKernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST, false);
        } catch (ResponseException $e) {
            return $e->getResponse();
        }
    }

    public function supports(Request $request): bool|null
    {
        return $request->isMethod('POST')
            && $request->request->has('FORM_SUBMIT')
            && \is_string($request->request->get('FORM_SUBMIT'))
            && preg_match('/^tl_login(_\d+)?$/', $request->request->get('FORM_SUBMIT'));
    }

    public function authenticate(Request $request): Passport
    {
        // When the firewall is lazy, the token is not initialized in the "supports"
        // stage, so this check does only work within the "authenticate" stage.
        $currentToken = $this->tokenStorage->getToken();

        if ($currentToken instanceof TwoFactorTokenInterface) {
            return $this->twoFactorAuthenticator->authenticate($request);
        }

        $credentials = $this->getCredentials($request);

        $passport = new Passport(
            new UserBadge($credentials['username'], $this->userProvider->loadUserByIdentifier(...)),
            new PasswordCredentials($credentials['password']),
            [new RememberMeBadge()],
        );

        if ($this->options['enable_csrf']) {
            $passport->addBadge(new CsrfTokenBadge($this->options['csrf_token_id'], $credentials['csrf_token']));
        }

        if ($this->userProvider instanceof PasswordUpgraderInterface) {
            $passport->addBadge(new PasswordUpgradeBadge($credentials['password'], $this->userProvider));
        }

        return $passport;
    }

    public function createToken(Passport $passport, string $firewallName): TokenInterface
    {
        $credentialsBadge = $passport->getBadge(TwoFactorCodeCredentials::class);

        if (!$credentialsBadge instanceof TwoFactorCodeCredentials) {
            return parent::createToken($passport, $firewallName);
        }

        $twoFactorToken = $credentialsBadge->getTwoFactorToken();

        if ($twoFactorToken->allTwoFactorProvidersAuthenticated()) {
            $authenticatedToken = $twoFactorToken->getAuthenticatedToken(); // Authentication complete, unwrap the token
            $authenticatedToken->setAttribute(TwoFactorAuthenticator::FLAG_2FA_COMPLETE, true);

            return $authenticatedToken;
        }

        return $twoFactorToken;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): Response|null
    {
        return $this->successHandler->onAuthenticationSuccess($request, $token);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response|null
    {
        return $this->failureHandler->onAuthenticationFailure($request, $exception);
    }

    public function isInteractive(): bool
    {
        if (!$request = $this->requestStack->getCurrentRequest()) {
            return false;
        }

        $page = $request->attributes->get('pageModel');

        return $page instanceof PageModel;
    }

    private function getCredentials(Request $request): array
    {
        $credentials = [
            'csrf_token' => ParameterBagUtils::getRequestParameterValue($request, $this->options['csrf_parameter']),
            'username' => ParameterBagUtils::getParameterBagValue($request->request, $this->options['username_parameter']),
            'password' => ParameterBagUtils::getParameterBagValue($request->request, $this->options['password_parameter']) ?? '',
        ];

        if (!\is_string($credentials['username']) && (!\is_object($credentials['username']) || !method_exists($credentials['username'], '__toString'))) {
            throw new BadRequestHttpException(\sprintf('The key "%s" must be a string, "%s" given.', $this->options['username_parameter'], \gettype($credentials['username'])));
        }

        $credentials['username'] = trim($credentials['username']);

        if (\strlen($credentials['username']) > UserBadge::MAX_USERNAME_LENGTH) {
            throw new BadCredentialsException('Invalid username.');
        }

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $credentials['username']);

        return $credentials;
    }

    private function redirectToBackend(Request $request): RedirectResponse
    {
        // No redirect parameter required if the 'contao_backend' route was requested
        // without any parameters.
        if ('contao_backend' === $request->attributes->get('_route') && [] === $request->query->all()) {
            $loginParams = [];
        } else {
            $loginParams = ['redirect' => $request->getUri()];
        }

        $url = $this->router->generate(
            'contao_backend_login',
            $loginParams,
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        // No URL signing required if we do not have any parameters.
        if ([] !== $loginParams) {
            $url = $this->uriSigner->sign($url);
        }

        // Our back end login controller will redirect based on the 'redirect' parameter,
        // ignoring Symfony's target path session value. Thus, we remove the session
        // variable here in order to not send an unnecessary session cookie.
        if ($request->hasSession()) {
            $this->removeTargetPath($request->getSession(), 'contao_backend');
        }

        return new RedirectResponse($url);
    }
}
