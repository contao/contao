<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Csrf;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Core\Exception\RuntimeException;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Csrf\Exception\TokenNotFoundException;

class CsrfTokenManager implements CsrfTokenManagerInterface
{
    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var string
     */
    private $secret;

    public function __construct(SessionInterface $session, RequestStack $requestStack, string $secret)
    {
        $this->session = $session;
        $this->requestStack = $requestStack;
        $this->secret = $secret;
    }

    /**
     * {@inheritdoc}
     */
    public function getToken($tokenId)
    {
        return new CsrfToken(
            $tokenId,
            $this->generateHash($tokenId, $this->getUserId())
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isTokenValid(CsrfToken $token)
    {
        return hash_equals(
            $this->generateHash($token->getId(), $this->getUserId(true)),
            $token->getValue()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function refreshToken($tokenId)
    {
        throw new RuntimeException('Cookieless CSRF tokens cannot be refreshed.');
    }

    /**
     * {@inheritdoc}
     */
    public function removeToken($tokenId)
    {
        throw new RuntimeException('Cookieless CSRF tokens cannot be removed.');
    }

    /**
     * @param bool $fromRequest Use the session ID from the current request
     *                          instead of the currently running session
     */
    private function getUserId(bool $fromRequest = false): string
    {
        $id = [];
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            throw new TokenNotFoundException('No current request available to generate the token from.');
        }

        if ($fromRequest && $request->hasPreviousSession()) {
            $id[] = $request->cookies->get($this->session->getName());
        } elseif ($this->session->isStarted() || $request->hasPreviousSession() || session_id() !== '') {
            $id[] = $this->session->getId();
        } else {
            $id[] = $request->getClientIp();
        }

        if ($request->getUser()) {
            $id[] = $request->getUser();
        }

        return implode("\0", $id);
    }

    private function generateHash(string $tokenId, string $userId): string
    {
        $bytes = hash_hmac('sha256', $tokenId."\0".$userId, $this->secret, true);

        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }
}
