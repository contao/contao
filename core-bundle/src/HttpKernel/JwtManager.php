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
use Firebase\JWT\JWT;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class JwtManager
{
    public const ATTRIBUTE = '_jwtManager';
    public const COOKIE_NAME = '_contao_preview';

    /**
     * @var string
     */
    private $secret;

    public function __construct(string $projectDir)
    {
        $filesystem = new Filesystem();
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
        $request->attributes->set(self::ATTRIBUTE, $this);

        if ($request->cookies->has(self::COOKIE_NAME)) {
            try {
                return $this->decodeJwt((string) $request->cookies->get(self::COOKIE_NAME));
            } catch (\Exception $e) {
                // do nothing
            }
        }

        if ('/contao/login' === $request->getPathInfo()) {
            return null;
        }

        if (null !== $qs = $request->getQueryString()) {
            $qs = '?'.$qs;
        }

        throw new RedirectResponseException(
            '/preview.php/contao/login?_target_path='.rawurlencode($request->getPathInfo().$qs)
        );
    }

    /**
     * Adds the JWT cookie to the given response.
     */
    public function addResponseCookie(Response $response, array $payload = []): void
    {
        if ($this->hasCookie($response)) {
            return;
        }

        $payload['iat'] = time();
        $payload['exp'] = strtotime('+30 minutes');

        if (method_exists(Cookie::class, 'create')) {
            $cookie = Cookie::create(self::COOKIE_NAME, JWT::encode($payload, $this->secret));
        } else {
            // Backwards compatibility with symfony/http-foundation <4.2
            $cookie = new Cookie(self::COOKIE_NAME, JWT::encode($payload, $this->secret));
        }

        $response->headers->setCookie($cookie);
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

    private function decodeJwt(string $data): array
    {
        $jwt = JWT::decode($data, $this->secret, ['HS256']);

        // Recursively decode the data as array instead of object
        return json_decode(json_encode($jwt), true);
    }
}
