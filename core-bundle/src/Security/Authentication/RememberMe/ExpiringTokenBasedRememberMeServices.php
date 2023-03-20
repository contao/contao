<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Security\Authentication\RememberMe;

use Contao\CoreBundle\Entity\RememberMe;
use Contao\CoreBundle\Repository\RememberMeRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CookieTheftException;
use Symfony\Component\Security\Core\Exception\TokenNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\RememberMe\AbstractRememberMeServices;

class ExpiringTokenBasedRememberMeServices extends AbstractRememberMeServices
{
    /**
     * This should be a firewall configuration, but we would have to override
     * the firewall factory for that.
     */
    public const EXPIRATION = 60;

    private RememberMeRepository $repository;
    private string $secret;

    /**
     * @internal
     */
    public function __construct(RememberMeRepository $repository, iterable $userProviders, string $secret, string $providerKey, array $options = [], LoggerInterface $logger = null)
    {
        parent::__construct($userProviders, $secret, $providerKey, $options, $logger);

        $this->repository = $repository;
        $this->secret = $secret;
    }

    protected function cancelCookie(Request $request): void
    {
        // Delete the cookie on the client
        parent::cancelCookie($request);

        // Delete the cookie from the tokenProvider
        if (
            null !== ($cookie = $request->cookies->get($this->options['name']))
            && 2 === \count($parts = $this->decodeCookie((string) $cookie))
        ) {
            $this->repository->deleteBySeries($this->encodeSeries($parts[0]));
        }
    }

    protected function processAutoLoginCookie(array $cookieParts, Request $request): UserInterface
    {
        if (2 !== \count($cookieParts)) {
            throw new AuthenticationException('The cookie is invalid');
        }

        $matchedToken = null;
        [$series, $cookieValue] = $cookieParts;

        try {
            $this->repository->lockTable();
            $this->repository->deleteExpired((int) $this->options['lifetime'], self::EXPIRATION);
            $rows = $this->repository->findBySeries($this->encodeSeries($series));

            if (0 === \count($rows)) {
                throw new TokenNotFoundException('No token found');
            }

            $matchedToken = $this->findValidToken($rows, $cookieValue);
            $currentToken = $rows[0];

            if ($currentToken === $matchedToken) {
                $cookieValue = $this->migrateToken($matchedToken)->getValue();
            } else {
                $cookieValue = $currentToken->getValue();
            }
        } finally {
            $this->repository->unlockTable();
        }

        $request->attributes->set(
            self::COOKIE_ATTR_NAME,
            $this->createRememberMeCookie($request, $series, $cookieValue)
        );

        return $this->getUserProvider($matchedToken->getClass())->loadUserByIdentifier($matchedToken->getUsername());
    }

    protected function onLoginSuccess(Request $request, Response $response, TokenInterface $token): void
    {
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return;
        }

        $series = random_bytes(64);

        $entity = new RememberMe($user, $this->encodeSeries($series));
        $this->repository->persist($entity);

        $response->headers->setCookie($this->createRememberMeCookie($request, $series, $entity->getValue()));
    }

    protected function decodeCookie(string $rawCookie): array
    {
        return array_map('base64_decode', explode('-', $rawCookie));
    }

    protected function encodeCookie(array $cookieParts): string
    {
        return implode('-', array_map('base64_encode', $cookieParts));
    }

    private function migrateToken(RememberMe $token): RememberMe
    {
        $this->repository->deleteSiblings($token);

        $token->setExpiresInSeconds(self::EXPIRATION);
        $newToken = $token->cloneWithNewValue();

        $this->repository->persist($token, $newToken);

        return $newToken;
    }

    /**
     * @param array<RememberMe> $rows
     */
    private function findValidToken(array $rows, string $cookieValue): ?RememberMe
    {
        $lastException = null;

        while ($token = array_shift($rows)) {
            try {
                if ($token->getValue() !== $cookieValue) {
                    throw new CookieTheftException('This token was already used; the account is possibly compromised');
                }

                if ($token->getLastUsed()->getTimestamp() + $this->options['lifetime'] < time()) {
                    throw new AuthenticationException('The cookie has expired');
                }

                return $token;
            } catch (AuthenticationException $e) {
                $lastException = $e;
            }
        }

        if (null !== $lastException) {
            throw $lastException;
        }

        return null;
    }

    private function createRememberMeCookie(Request $request, string $series, string $cookieValue): Cookie
    {
        return new Cookie(
            $this->options['name'],
            $this->encodeCookie([$series, $cookieValue]),
            time() + $this->options['lifetime'],
            $this->options['path'],
            $this->options['domain'],
            $this->options['secure'] ?? $request->isSecure(),
            $this->options['httponly'],
            false,
            $this->options['samesite'] ?? null
        );
    }

    private function encodeSeries(string $series): string
    {
        return hash_hmac('sha256', $series, $this->secret, true);
    }
}
