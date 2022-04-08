<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\HttpKernel;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
class JwtManager
{
    final public const COOKIE_NAME = 'contao_settings';

    private Configuration $config;

    public function __construct(string $projectDir, Filesystem $filesystem = null, Configuration $config = null)
    {
        $secret = null;
        $filesystem ??= new Filesystem();
        $secretFile = Path::join($projectDir, 'var/jwt_secret');

        if ($filesystem->exists($secretFile)) {
            $secret = file_get_contents($secretFile);
        }

        if (!\is_string($secret) || 64 !== \strlen($secret)) {
            $secret = bin2hex(random_bytes(32));
            $filesystem->dumpFile($secretFile, $secret);
        }

        $this->config = $config ?: Configuration::forSymmetricSigner(new Sha256(), InMemory::plainText($secret));
        $this->config->setValidationConstraints(new SignedWith($this->config->signer(), $this->config->signingKey()));
    }

    public function parseRequest(Request $request): array|null
    {
        if ($request->cookies->has(self::COOKIE_NAME)) {
            try {
                return $this->parseCookie((string) $request->cookies->get(self::COOKIE_NAME));
            } catch (\Exception) {
                // do nothing
            }
        }

        return null;
    }

    /**
     * Adds the JWT cookie to the given response.
     */
    public function addResponseCookie(Response $response, array $payload = []): void
    {
        if ($this->hasCookie($response)) {
            return;
        }

        $response->headers->setCookie($this->createCookie($payload));
    }

    /**
     * Clears the JWT cookie in the response.
     */
    public function clearResponseCookie(Response $response): Response
    {
        if ($this->hasCookie($response)) {
            return $response;
        }

        $response->headers->clearCookie(self::COOKIE_NAME);

        return $response;
    }

    /**
     * Creates the JWT cookie for the preview entry point.
     */
    public function createCookie(array $payload = []): Cookie
    {
        $builder = $this->config->builder();

        foreach ($payload as $k => $v) {
            $builder = $builder->withClaim($k, $v);
        }

        $token = $builder
            ->issuedAt(new \DateTimeImmutable())
            ->expiresAt(new \DateTimeImmutable('now +30 minutes'))
            ->getToken($this->config->signer(), $this->config->signingKey())
        ;

        return Cookie::create(self::COOKIE_NAME, $token->toString());
    }

    public function parseCookie(string $data): array|null
    {
        $token = $this->config->parser()->parse($data);

        if (
            $token->isExpired(new \DateTimeImmutable())
            || !$this->config->validator()->validate($token, ...$this->config->validationConstraints())
        ) {
            return null;
        }

        return array_map(
            static function ($value) {
                if ($value instanceof \DateTimeInterface) {
                    return $value->format('U');
                }

                return (string) $value;
            },
            $token->claims()->all()
        );
    }

    /**
     * Returns whether the response has a cookie with that name.
     */
    private function hasCookie(Response $response): bool
    {
        $cookies = $response->headers->getCookies();

        foreach ($cookies as $cookie) {
            if (self::COOKIE_NAME === $cookie->getName()) {
                return true;
            }
        }

        return false;
    }
}
