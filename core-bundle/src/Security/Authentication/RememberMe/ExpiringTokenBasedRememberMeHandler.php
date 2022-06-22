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
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\RememberMe\PersistentToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CookieTheftException;
use Symfony\Component\Security\Core\Exception\TokenNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\RememberMe\AbstractRememberMeHandler;
use Symfony\Component\Security\Http\RememberMe\RememberMeDetails;
use Symfony\Component\Security\Http\RememberMe\ResponseListener;

class ExpiringTokenBasedRememberMeHandler extends AbstractRememberMeHandler
{
    /**
     * This should be a firewall configuration, but we would have to override
     * the firewall factory for that.
     */
    final public const EXPIRATION = 60;

    /**
     * @internal Do not inherit from this class; decorate the "contao.security.expiring_token_based_remember_me_handler" service instead
     */
    public function __construct(
        private RememberMeRepository $repository,
        UserProviderInterface $userProvider,
        RequestStack $requestStack,
        private string $secret,
        array $options = [],
        LoggerInterface $logger = null,
    ) {
        parent::__construct($userProvider, $requestStack, $options, $logger);
    }

    public function createRememberMeCookie(UserInterface $user): void
    {
        $series = base64_encode(random_bytes(64));
        $tokenValue = $this->generateHash(base64_encode(random_bytes(64)));
        $token = new PersistentToken(\get_class($user), $user->getUserIdentifier(), $series, $tokenValue, new \DateTime());
        $rememberMe = new RememberMe($user, $series);

        $this->repository->persist($rememberMe);

        $this->createCookie(RememberMeDetails::fromPersistentToken($token, time() + $this->options['lifetime']));
    }

    protected function createCookie(?RememberMeDetails $rememberMeDetails): void
    {
        $request = $this->requestStack->getMainRequest();

        if (!$request) {
            throw new \LogicException('Cannot create the remember-me cookie; no master request available.');
        }

        $series = base64_encode(random_bytes(64));

        // the ResponseListener configures the cookie saved in this attribute on the final response object
        $request->attributes->set(ResponseListener::COOKIE_ATTR_NAME, new Cookie(
            $this->options['name'],
            $this->encodeCookie([$series, $rememberMeDetails?->toString()]),
            time() + $this->options['lifetime'],
            $this->options['path'],
            $this->options['domain'],
            $this->options['secure'] ?? $request->isSecure(),
            $this->options['httponly'],
            false,
            $this->options['samesite'] ?? null
        ));
    }

    protected function encodeCookie(array $cookieParts): string
    {
        return implode('-', array_map('base64_encode', $cookieParts));
    }

    private function generateHash(string $tokenValue): string
    {
        return hash_hmac('sha256', $tokenValue, $this->secret, true);
    }

    protected function processRememberMe(RememberMeDetails $rememberMeDetails, UserInterface $user): void
    {
        [$series, $tokenValue] = explode(':', $rememberMeDetails->getValue());

        try {
            $this->repository->lockTable();
            $this->repository->deleteExpired((int) $this->options['lifetime'], self::EXPIRATION);
            $rows = $this->repository->findBySeries($this->generateHash($series));

            if (0 === \count($rows)) {
                throw new TokenNotFoundException('No token found');
            }

            $matchedToken = $this->findValidToken($rows, $tokenValue);
            $currentToken = $rows[0];

            if ($currentToken === $matchedToken) {
                $tokenValue = $this->migrateToken($matchedToken)->getValue();
            } else {
                $tokenValue = $currentToken->getValue();
            }
        } finally {
            $this->repository->unlockTable();
        }

        $entity = new RememberMe($user, $this->generateHash($series));
        $this->repository->persist($entity);

        $this->createCookie($rememberMeDetails->withValue($series.':'.$tokenValue));
    }

    public function clearRememberMeCookie(): void
    {
        parent::clearRememberMeCookie();

        $cookie = $this->requestStack->getMainRequest()->cookies->get($this->options['name']);

        if (null === $cookie) {
            return;
        }

        $rememberMeDetails = RememberMeDetails::fromRawCookie($cookie);
        [$series, ] = explode(':', $rememberMeDetails->getValue());
        $this->repository->deleteBySeries($series);
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
    private function findValidToken(array $rows, string $cookieValue): RememberMe|null
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
}
