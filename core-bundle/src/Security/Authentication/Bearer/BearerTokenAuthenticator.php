<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Security\Authentication\Bearer;

use Contao\BackendUser;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Security\Jwt\Jwt;
use Contao\CoreBundle\Security\User\ContaoUserProvider;
use Contao\FrontendUser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

class BearerTokenAuthenticator extends AbstractGuardAuthenticator
{
    /**
     * @var ContaoFramework
     */
    private $framework;

    private static $prefix = 'Bearer';
    private static $name = 'Authorization';

    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;
    }

    public function supports(Request $request): ?bool
    {
        if ('bearerFrontend' === $request->attributes->get('_scope') || 'bearerBackend' === $request->attributes->get('_scope')) {
            return true;
        }

        return false;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $providerKey): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse($exception->getMessage(), Response::HTTP_FORBIDDEN);
    }

    public function start(Request $request, AuthenticationException $authException = null): JsonResponse
    {
        return new JsonResponse('Bearer Auth required', Response::HTTP_UNAUTHORIZED);
    }

    public function getCredentials(Request $request): array
    {
        $this->framework->initialize();

        if (!$request->headers->has(self::$name)) {
            throw new AuthenticationException(self::$name . ' not found in Header');
        }

        $authorizationHeader = $request->headers->get(self::$name);
        if (empty($authorizationHeader)) {
            throw new AuthenticationException(self::$name . ' empty in Header');
        }

        $headerParts = explode(' ', $authorizationHeader);
        if (!(2 === count($headerParts) && 0 === strcasecmp($headerParts[0], self::$prefix))) {
            throw new AuthenticationException('no valid value for ' . self::$name . ' in Header');
        }

        return ['token' => $headerParts[1]];
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        if ($userProvider instanceof ContaoUserProvider) {

            $username = Jwt::getClaim($credentials['token'], 'username');

            if ($username == null || $username == '') {
                throw new AuthenticationException('invalid username in token');
            }

            return $userProvider->loadUserByUsername($username);

        } else {
            throw new AuthenticationException('invalid provider');
        }

    }

    public function checkCredentials($credentials, UserInterface $user): bool
    {
        try {

            if ($user instanceof FrontendUser || $user instanceof BackendUser) {

                $currentToken = (string)$credentials['token'];
                $userToken = (string)$user->bearerToken;
                $username = $user->username;

                if ($currentToken === null || $currentToken === '' || $currentToken !== $userToken || Jwt::validateAndVerify($currentToken, \base64_encode($username)) === false) {
                    return false;
                }

                return true;

            } else {
                throw new AuthenticationException('invalid user instance');
            }

        } catch (\Exception $ex) {
            throw new AuthenticationException($ex->getMessage());
        }

    }

    public function supportsRememberMe(): bool
    {
        return false;
    }
}
