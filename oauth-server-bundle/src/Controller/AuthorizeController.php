<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\OAuthServerBundle\Controller;

use Contao\BackendUser;
use Contao\OAuthServerBundle\AuthorizationServer\AuthorizationServerFactory;
use Contao\OAuthServerBundle\AuthorizationServer\Entity\Client;
use Contao\OAuthServerBundle\AuthorizationServer\Entity\Scope;
use Contao\OAuthServerBundle\AuthorizationServer\Entity\User;
use Contao\OAuthServerBundle\Http\PsrMessageConverter;
use Contao\OAuthServerBundle\ResourceContext;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;
use League\OAuth2\Server\RequestTypes\AuthorizationRequestInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Twig\Environment;

/**
 * @internal
 */
class AuthorizeController
{
    private const string SESSION_PREFIX = 'contao_oauth_request.';

    public function __construct(
        private readonly AuthorizationServerFactory $serverFactory,
        private readonly PsrMessageConverter $psrMessageConverter,
        private readonly ResourceContext $context,
        private readonly Security $security,
        private readonly Environment $twig,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly string $csrfTokenName,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $user = $this->security->getUser();

        if (!$user instanceof BackendUser) {
            throw new AccessDeniedException();
        }

        $server = $this->serverFactory->create();

        if ($request->isMethod('POST')) {
            return $this->handleConsent($request, $server, $user);
        }

        try {
            $authRequest = $server->validateAuthorizationRequest($this->psrMessageConverter->toPsr($request));
        } catch (OAuthServerException $e) {
            // League does not redirect in case of invalid client or redirect_uri
            return $this->addIssuer($this->psrMessageConverter->toSymfony($e->generateHttpResponse($this->psrMessageConverter->newResponse())));
        }

        // League silently falls back to "plain" if no method is given
        if ('S256' !== $authRequest->getCodeChallengeMethod()) {
            return $this->errorRedirect($authRequest, 'invalid_request', 'PKCE with S256 is required.');
        }

        $resource = $request->query->get('resource');

        if (null !== $resource && !$this->context->matchesResource((string) $resource)) {
            return $this->errorRedirect($authRequest, 'invalid_target', 'Unknown resource.');
        }

        $authRequest->setUser(new User($user->getUserIdentifier()));

        $key = bin2hex(random_bytes(32));
        $request->getSession()->set(self::SESSION_PREFIX.$key, serialize($authRequest));

        $client = $authRequest->getClient();

        return new Response($this->twig->render('@ContaoOAuthServer/backend/oauth_consent.html.twig', [
            'client_name' => $client->getName(),
            'client_host' => parse_url($client->getIdentifier(), PHP_URL_HOST),
            'redirect_host' => parse_url($this->redirectUri($authRequest), PHP_URL_HOST),
            'request_key' => $key,
            'request_token' => $this->csrfTokenManager->getToken($this->csrfTokenName)->getValue(),
            'user' => $user,
        ]));
    }

    private function handleConsent(Request $request, AuthorizationServer $server, BackendUser $user): Response
    {
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken($this->csrfTokenName, (string) $request->request->get('REQUEST_TOKEN')))) {
            throw new AccessDeniedException('Invalid request token.');
        }

        $serialized = $request->getSession()->remove(self::SESSION_PREFIX.$request->request->get('request_key'));

        if (!\is_string($serialized)) {
            return new Response('The authorization request has expired, please start again.', Response::HTTP_BAD_REQUEST);
        }

        $authRequest = unserialize($serialized, ['allowed_classes' => [AuthorizationRequest::class, Client::class, Scope::class, User::class]]);

        if (!$authRequest instanceof AuthorizationRequestInterface || $authRequest->getUser()?->getIdentifier() !== $user->getUserIdentifier()) {
            throw new AccessDeniedException('The authorization request does not belong to the current user.');
        }

        $authRequest->setAuthorizationApproved('allow' === $request->request->get('decision'));

        try {
            $psrResponse = $server->completeAuthorizationRequest($authRequest, $this->psrMessageConverter->newResponse());
        } catch (OAuthServerException $e) {
            // e.g. access_denied, already contains redirect_uri and state
            $psrResponse = $e->generateHttpResponse($this->psrMessageConverter->newResponse());
        }

        return $this->addIssuer($this->psrMessageConverter->toSymfony($psrResponse));
    }

    private function errorRedirect(AuthorizationRequestInterface $authRequest, string $error, string $description): Response
    {
        $redirectUri = $this->redirectUri($authRequest);
        $params = array_filter(['error' => $error, 'error_description' => $description, 'state' => $authRequest->getState()]);

        return new RedirectResponse($this->withIssuer($redirectUri.(str_contains($redirectUri, '?') ? '&' : '?').http_build_query($params)));
    }

    private function redirectUri(AuthorizationRequestInterface $authRequest): string
    {
        $uris = (array) $authRequest->getClient()->getRedirectUri();

        return $authRequest->getRedirectUri() ?? (string) ($uris[0] ?? '');
    }

    /**
     * RFC 9207 requires the "iss" parameter in all authorization responses.
     */
    private function addIssuer(Response $response): Response
    {
        if ($response->headers->has('Location')) {
            $response->headers->set('Location', $this->withIssuer((string) $response->headers->get('Location')));
        }

        return $response;
    }

    private function withIssuer(string $location): string
    {
        return $location.(str_contains($location, '?') ? '&' : '?').'iss='.rawurlencode($this->context->getIssuer());
    }
}
