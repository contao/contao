<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\HttpKernel;

use Contao\CoreBundle\Exception\RedirectResponseException;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class JwtManager
{
    public const REQUEST_ATTRIBUTE = '_jwtManager';
    public const COOKIE_NAME = '_contao_preview';

    /**
     * @var Signer
     */
    private $signer;

    /**
     * @var Builder
     */
    private $builder;

    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var string
     */
    private $secret;

    public function __construct(string $projectDir, Signer $signer = null, Builder $builder = null, Parser $parser = null, Filesystem $filesystem = null)
    {
        $this->signer = $signer ?: new Sha256();
        $this->builder = $builder ?: new Builder();
        $this->parser = $parser ?: new Parser();

        if (null === $filesystem) {
            $filesystem = new Filesystem();
        }

        $secretFile = $projectDir.'/var/jwt_secret';

        if ($filesystem->exists($secretFile)) {
            $this->secret = file_get_contents($secretFile);
        }

        if (!\is_string($this->secret) || 64 !== \strlen($this->secret)) {
            $this->secret = bin2hex(random_bytes(32));
            $filesystem->dumpFile($secretFile, $this->secret);
        }
    }

    public function parseRequest(Request $request): ?array
    {
        $request->attributes->set(self::REQUEST_ATTRIBUTE, $this);

        if ($request->cookies->has(self::COOKIE_NAME)) {
            try {
                return $this->parseCookie((string) $request->cookies->get(self::COOKIE_NAME));
            } catch (\Exception $e) {
                // do nothing
            }
        }

        if ('/contao/login' === $request->getPathInfo()) {
            return null;
        }

        $query = '';

        if (null !== ($qs = $request->getQueryString())) {
            $query = '?referer='.base64_encode($qs);
        }

        throw new RedirectResponseException('/preview.php/contao/login'.$query);
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
        $response->headers->clearCookie(self::COOKIE_NAME);

        return $response;
    }

    /**
     * Creates the JWT cookie for the preview entry point.
     */
    public function createCookie(array $payload = []): Cookie
    {
        foreach ($payload as $k => $v) {
            $this->builder->set($k, $v);
        }

        $token = $this->builder
            ->setIssuedAt(time())
            ->setExpiration(strtotime('+30 minutes'))
            ->sign($this->signer, $this->secret)
            ->getToken()
        ;

        return Cookie::create(self::COOKIE_NAME, (string) $token);
    }

    public function parseCookie(string $data): ?array
    {
        $token = $this->parser->parse($data);

        if ($token->isExpired() || !$token->verify($this->signer, $this->secret)) {
            return null;
        }

        return array_map('strval', $token->getClaims());
    }

    /**
     * Returns whether the response has a cookie with that name.
     */
    private function hasCookie(Response $response): bool
    {
        /** @var Cookie[] $cookies */
        $cookies = $response->headers->getCookies();

        foreach ($cookies as $cookie) {
            if (self::COOKIE_NAME === $cookie->getName()) {
                return true;
            }
        }

        return false;
    }
}
