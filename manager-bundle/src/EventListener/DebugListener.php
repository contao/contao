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
use Symfony\Component\HttpFoundation\Response;
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

    public function onEnable(): Response
    {
        return $this->updateJwtCookie(true);
    }

    public function onDisable(): Response
    {
        return $this->updateJwtCookie(false);
    }

    private function updateJwtCookie(bool $debug): Response
    {
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Access Denied.');
        }

        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            throw new \RuntimeException('Request stack is empty.');
        }

        $referer = $request->query->has('referer') ? '?' . base64_decode($request->query->get('referer')) : '';
        $response = new RedirectResponse($request->getPathInfo() . $referer);

        $this->jwtManager->addResponseCookie($response, ['debug' => $debug]);

        return $response;
    }
}
