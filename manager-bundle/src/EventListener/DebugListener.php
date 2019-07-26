<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\EventListener;

use Contao\ManagerBundle\HttpKernel\JwtManager;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;

class DebugListener
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var JwtManager
     */
    private $jwtManager;

    /**
     * @var Security
     */
    private $security;

    public function __construct(Security $security, RequestStack $requestStack, JwtManager $jwtManager)
    {
        $this->security = $security;
        $this->requestStack = $requestStack;
        $this->jwtManager = $jwtManager;
    }

    public function onEnable(): RedirectResponse
    {
        return $this->updateJwtCookie(true);
    }

    public function onDisable(): RedirectResponse
    {
        return $this->updateJwtCookie(false);
    }

    private function updateJwtCookie(bool $debug): RedirectResponse
    {
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException();
        }

        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            throw new \RuntimeException('The request stack is empty.');
        }

        $referer = $request->query->has('referer') ? '?'.base64_decode($request->query->get('referer'), true) : '';
        $response = new RedirectResponse($request->getSchemeAndHttpHost().$request->getPathInfo().$referer);

        $this->jwtManager->addResponseCookie($response, ['debug' => $debug]);

        return $response;
    }
}
