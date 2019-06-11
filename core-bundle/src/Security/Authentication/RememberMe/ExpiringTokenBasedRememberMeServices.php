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
     * This should be a firewall configuration, but that means we would need to override the firewall factory.
     *
     * @var int
     */
    public const EXPIRATION = 60;

    /**
     * @var RememberMeRepository
     */
    private $repository;

    /**
     * @var string
     */
    private $secret;

    public function __construct(RememberMeRepository $repository, array $userProviders, string $secret, string $providerKey, array $options = [], LoggerInterface $logger = null)
    {
        parent::__construct($userProviders, $secret, $providerKey, $options, $logger);

        $this->repository = $repository;
        $this->secret = $secret;
    }

    /**
     * {@inheritdoc}
     */
    protected function cancelCookie(Request $request): void
    {
        // Delete cookie on the client
        parent::cancelCookie($request);

        // Delete cookie from the tokenProvider
        if (null !== ($cookie = $request->cookies->get($this->options['name']))
            && 2 === \count($parts = $this->decodeCookie($cookie))
        ) {
            [$series] = $parts;
            $this->repository->deleteBySeries($this->encodeSeries($series));
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function processAutoLoginCookie(array $cookieParts, Request $request): UserInterface
    {
        if (2 !== \count($cookieParts)) {
            throw new AuthenticationException('The cookie is invalid.');
        }

        [$series, $cookieValue] = $cookieParts;

        try {
            $this->repository->lockTable();

            $tokens = $this->loadTokens($series);

            $currentToken = null === $tokens[0]->getExpires() ? $tokens[0] : null;
            $matchedToken = $this->findValidToken($tokens, $cookieValue);

            $this->repository->deleteExpired((int) $this->options['lifetime'], self::EXPIRATION);

            if (null === $currentToken || null !== $matchedToken->getExpires() || $currentToken->getValue() === $matchedToken->getValue()) {
                $cookieValue = $this->migrateToken($matchedToken)->getValue();
            } else {
                $cookieValue = $currentToken->getValue();
            }
        } catch (\Exception $e) {
            if ($e instanceof AuthenticationException) {
                throw $e;
            }

            throw new AuthenticationException('Rememberme services failed.', 0, $e);
        } finally {
            $this->repository->unlockTable();
        }

        $request->attributes->set(self::COOKIE_ATTR_NAME, $this->createCookie($request, $series, $cookieValue));

        return $this->getUserProvider($matchedToken->getClass())->loadUserByUsername($matchedToken->getUsername());
    }

    /**
     * {@inheritdoc}
     */
    protected function onLoginSuccess(Request $request, Response $response, TokenInterface $token): void
    {
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return;
        }

        $series = base64_encode(random_bytes(64));

        $entity = new RememberMe($user, $this->encodeSeries($series));
        $this->repository->persist($entity);

        $response->headers->setCookie($this->createCookie($request, $series, $entity->getValue()));
    }

    /**
     * @return RememberMe[]
     */
    private function loadTokens(string $series): array
    {
        try {
            $this->repository->findBySeries($this->encodeSeries($series));
        } catch (\Exception $e) {
            $rows = [];
        }

        if (0 === \count($rows)) {
            throw new TokenNotFoundException('No token found.');
        }

        return $rows;
    }

    private function migrateToken(RememberMe $token): RememberMe
    {
        $token->setExpiresInSeconds(self::EXPIRATION);
        $newToken = clone $token;

        $this->repository->persist($token, $newToken);

        return $newToken;
    }

    /**
     * @param RememberMe[]  $rows
     */
    private function findValidToken(array $rows, string $cookieValue): RememberMe
    {
        while ($token = array_shift($rows)) {
            try {
                if ($token->getValue() !== $cookieValue) {
                    throw new CookieTheftException(
                        'This token was already used. The account is possibly compromised.'
                    );
                }

                if ((new \DateTime($token->getLastUsed()))->getTimestamp() + $this->options['lifetime'] < time()) {
                    throw new AuthenticationException('The cookie has expired.');
                }

                return $token;
            } catch (AuthenticationException $e) {
                $lastException = $e;
            }
        }

        throw $lastException;
    }

    /**
     * Creates a rememberme cookie.
     */
    private function createCookie(Request $request, string $series, string $cookieValue): Cookie
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

    private function encodeSeries(string $series)
    {
        return hash_hmac('sha256', $series, $this->secret);
    }
}
