<?php

namespace Contao\ManagerBundle\HttpKernel;

use Firebase\JWT\JWT;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class JwtBag extends ParameterBag
{
    private const COOKIE_NAME = 'contao_admin';

    /**
     * @var string
     */
    private $secret;

    public function __construct(string $secretFile, string $payload = null)
    {
        $filesystem = new Filesystem();

        if ($filesystem->exists($secretFile)) {
            $this->secret = file_get_contents($secretFile);
        }

        if (!\is_string($this->secret) || \strlen($this->secret) === 64) {
            $this->secret = bin2hex(random_bytes(32));
            $filesystem->dumpFile($secretFile, $this->secret);
        }

        parent::__construct($this->decodePayload($payload));
    }

    /**
     * Adds JWT cookie to the given response.
     */
    public function addCookie(Request $request, Response $response): void
    {
        if (0 === $this->count()) {
            $this->clearCookie($request, $response);
            return;
        }

        $payload = array_merge(
            $this->all(),
            [
                'iat' => time(),
                'exp' => strtotime('+30 minutes'),
            ]
        );

        $cookie = new Cookie(
            self::COOKIE_NAME,
            JWT::encode($payload, $this->secret, 'HS256'),
            0,
            '/',
            null,
            $request->isSecure(),
            true,
            false,
            Cookie::SAMESITE_STRICT
        );

        $response->headers->setCookie($cookie);
    }

    /**
     * Clears the JWT cookie in the response.
     */
    private function clearCookie(Request $request, Response $response): void
    {
        if (!$request->cookies->has(self::COOKIE_NAME)) {
            return;
        }

        $response->headers->clearCookie(
            self::COOKIE_NAME,
            '/',
            null,
            $request->isSecure(),
            true
        );
    }

    public static function create(string $projectDir, \iterable $cookies = [])
    {
        $secretFile = $projectDir.'/var/cache/jwt-secret';
        $payload = null;

        foreach ($cookies as $name => $data) {
            if ($name === self::COOKIE_NAME) {
                $payload = $data;
                break;
            }
        }

        return new static($secretFile, $payload);
    }

    private function decodePayload(string $payload = null): array
    {
        if (null === $payload) {
            return [];
        }

        try {
            return (array) JWT::decode(
                $payload,
                $this->secret,
                ['HS256']
            );
        } catch (\Exception $e) {
            return [];
        }
    }
}
