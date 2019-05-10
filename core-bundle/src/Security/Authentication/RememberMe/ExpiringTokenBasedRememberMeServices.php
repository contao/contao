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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type as DoctrineType;
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
     * @var Connection
     */
    private $connection;

    /**
     * @var string
     */
    private $secret;

    public function __construct(Connection $connection, array $userProviders, string $secret, string $providerKey, array $options = [], LoggerInterface $logger = null)
    {
        parent::__construct($userProviders, $secret, $providerKey, $options, $logger);

        $this->connection = $connection;
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
            $this->connection->delete('tl_remember_me', ['series' => $series]);
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

        [$series, $tokenValue] = $cookieParts;

        try {
            $this->connection->exec('LOCK TABLES tl_remember_me WRITE');

            $rows = $this->loadTokens($series);

            $currentToken = null === $rows[0]->expires ? $rows[0] : null;
            $matchedToken = $this->findValidToken($rows, $tokenValue);

            $this->cleanupExpiredTokens();

            if (null === $currentToken || $currentToken->value === $matchedToken->value || null !== $matchedToken->expires) {
                $tokenValue = $this->migrateToken($matchedToken);
            } else {
                $tokenValue = $currentToken->value;
            }
        } catch (\Exception $e) {
            if ($e instanceof AuthenticationException) {
                throw $e;
            }

            throw new AuthenticationException('Rememberme services failed.', 0, $e);
        } finally {
            $this->connection->exec('UNLOCK TABLES');
        }

        $request->attributes->set(self::COOKIE_ATTR_NAME, $this->createCookie($request, $series, $tokenValue));

        return $this->getUserProvider($matchedToken->class)->loadUserByUsername($matchedToken->username);
    }

    /**
     * {@inheritdoc}
     */
    protected function onLoginSuccess(Request $request, Response $response, TokenInterface $token): void
    {
        $series = base64_encode(random_bytes(64));
        $tokenValue = base64_encode(random_bytes(64));

        $this->insertToken(
            \get_class($user = $token->getUser()),
            $user->getUsername(),
            $series,
            $tokenValue
        );

        $response->headers->setCookie($this->createCookie($request, $series, $tokenValue));
    }

    private function loadTokens(string $series): array
    {
        try {
            $rows = $this->connection->executeQuery(
                'SELECT * FROM tl_remember_me WHERE series=:series AND (expires IS NULL OR expires>=:expires) ORDER BY expires IS NULL DESC',
                [
                    'series' => $series,
                    'expires' => new \DateTime(),
                ],
                [
                    'series' => \PDO::PARAM_STR,
                    'expires' => DoctrineType::DATETIME,
                ]
            )->fetchAll(\PDO::FETCH_OBJ);
        } catch (\Exception $e) {
            $rows = [];
        }

        if (empty($rows)) {
            throw new TokenNotFoundException('No token found.');
        }

        return $rows;
    }

    private function migrateToken($token): string
    {
        $tokenValue = base64_encode(random_bytes(64));

        $this->connection->update(
            'tl_remember_me',
            ['expires' => (new \DateTime())->add(new \DateInterval('PT60S'))],
            ['expires' => DoctrineType::DATETIME]
        );

        $this->insertToken($token->class, $token->username, $token->series, $tokenValue);

        return $tokenValue;
    }

    /**
     * Adds a new rememberme token to the database.
     */
    private function insertToken(string $class, string $username, string $series, string $tokenValue): void
    {
        $this->connection->insert(
            'tl_remember_me',
            [
                'class' => $class,
                'username' => $username,
                'series' => $series,
                'value' => hash_hmac('sha256', $tokenValue, $this->secret),
                'lastUsed' => new \DateTime(),
            ],
            [
                'class' => \PDO::PARAM_STR,
                'username' => \PDO::PARAM_STR,
                'series' => \PDO::PARAM_STR,
                'value' => \PDO::PARAM_STR,
                'lastUsed' => DoctrineType::DATETIME,
            ]
        );
    }

    private function findValidToken(array $rows, string $tokenValue)
    {
        $tokenValue = hash_hmac('sha256', $tokenValue, $this->secret);

        while ($token = array_shift($rows)) {
            try {
                if (!hash_equals($token->value, $tokenValue)) {
                    throw new CookieTheftException(
                        'This token was already used. The account is possibly compromised.'
                    );
                }

                if ((new \DateTime($token->lastUsed))->getTimestamp() + $this->options['lifetime'] < time()) {
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
    private function createCookie(Request $request, string $series, string $tokenValue): Cookie
    {
        return new Cookie(
            $this->options['name'],
            $this->encodeCookie([$series, $tokenValue]),
            time() + $this->options['lifetime'],
            $this->options['path'],
            $this->options['domain'],
            $this->options['secure'] ?? $request->isSecure(),
            $this->options['httponly'],
            false,
            $this->options['samesite'] ?? null
        );
    }

    /**
     * Clean up expired tokens on successful login (lazy behavior).
     * This will also clean other series that have expired, e.g. if a cookie was never used again within the lifetime.
     */
    private function cleanupExpiredTokens(): void
    {
        try {
            $this->connection->executeUpdate(
                'DELETE FROM tl_remember_me WHERE lastUsed<:lastUsed OR expires<:expires',
                [
                    'lastUsed' => (new \DateTime())->sub(new \DateInterval('PT'.$this->options['lifetime'].'S')),
                    'expires' => (new \DateTime())->sub(new \DateInterval('PT3600S')),
                ],
                [
                    'lastUsed' => DoctrineType::DATETIME,
                    'expires' => DoctrineType::DATETIME,
                ]
            );
        } catch (\Exception $e) {
            // Do nothing
        }
    }
}
