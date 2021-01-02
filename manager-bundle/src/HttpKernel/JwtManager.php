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

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class JwtManager
{
    public const COOKIE_NAME = 'contao_settings';

    /**
     * @var Configuration
     */
    private $config;

    public function __construct(string $projectDir, Signer $signer = null, Builder $builder = null, Parser $parser = null, Filesystem $filesystem = null, Configuration $config = null)
    {
        $secret = null;

        if (null === $filesystem) {
            $filesystem = new Filesystem();
        }

        $secretFile = $projectDir.'/var/jwt_secret';

        if ($filesystem->exists($secretFile)) {
            $secret = file_get_contents($secretFile);
        }

        if (!\is_string($secret) || 64 !== \strlen($secret)) {
            $secret = bin2hex(random_bytes(32));
            $filesystem->dumpFile($secretFile, $secret);
        }

        if (null !== $signer) {
            @trigger_error('Second argument ($signer) of JwtManager::__construct(...) is deprecated since Contao 4.9, to be removed in Contao 5.0. Use the Configuration object instead.', E_USER_DEPRECATED);
        }
        $this->config = $config ?: Configuration::forSymmetricSigner(
            $signer ?: new Sha256(),
            InMemory::file($secretFile)
        );

        if (null !== $builder) {
            @trigger_error('Third argument ($builder) of JwtManager::__construct(...) is deprecated since Contao 4.9, to be removed in Contao 5.0. Use the Configuration object instead.', E_USER_DEPRECATED);

            $this->config->setBuilderFactory(
                static function () use ($builder): Builder {
                    return $builder;
                }
            );
        }

        if (null !== $parser) {
            @trigger_error('Fourth argument ($parser) of JwtManager::__construct(...) is deprecated since Contao 4.9, to be removed in Contao 5.0. Use the Configuration object instead.', E_USER_DEPRECATED);

            $this->config->setParser($parser);
        }

        $this->config->setValidationConstraints(new SignedWith($this->config->signer(), $this->config->signingKey()));
    }

    public function parseRequest(Request $request): ?array
    {
        if ($request->cookies->has(self::COOKIE_NAME)) {
            try {
                return $this->parseCookie((string) $request->cookies->get(self::COOKIE_NAME));
            } catch (\Exception $e) {
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

    public function parseCookie(string $data): ?array
    {
        $token = $this->config->parser()->parse($data);

        if ($token->isExpired(new \DateTimeImmutable()) || !$this->config->validator()->validate($token, ...$this->config->validationConstraints())) {
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
