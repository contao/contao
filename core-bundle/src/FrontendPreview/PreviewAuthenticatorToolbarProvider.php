<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\FrontendPreview;

use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Twig\Environment;
use Twig\Error\Error as TwigError;

class PreviewAuthenticatorToolbarProvider implements ToolbarProviderInterface
{
    private $tokenChecker;

    /**
     * @var Security
     */
    private $security;

    private $twig;

    private $router;

    private $tokenManager;

    private $csrfTokenName;

    public function __construct(TokenChecker $tokenChecker, Security $security, Environment $twig, RouterInterface $router, CsrfTokenManagerInterface $tokenManager, string $csrfTokenName)
    {
        $this->tokenChecker = $tokenChecker;
        $this->security = $security;
        $this->twig = $twig;
        $this->router = $router;
        $this->tokenManager = $tokenManager;
        $this->csrfTokenName = $csrfTokenName;
    }

    public function getName(): string
    {
        return 'authenticator';
    }

    public function renderToolbarSection(): ?string
    {
        $canSwitchUser = $this->security->isGranted('ROLE_ALLOWED_TO_SWITCH_MEMBER');
        $frontendUsername = $this->tokenChecker->getFrontendUsername();
        $showUnpublished = $this->tokenChecker->isPreviewMode();

        try {
            return $this->twig->loadTemplate('@ContaoCore/FrontendPreview/authenticator.html.twig')->renderBlock(
                'toolbar',
                [
                    'request_token' => $this->tokenManager->getToken($this->csrfTokenName)->getValue(),
                    'action' => $this->router->generate('contao_backend_switch'),
                    'canSwitchUser' => $canSwitchUser,
                    'user' => $frontendUsername,
                    'show' => $showUnpublished,
                ]
            );
        } catch (TwigError $e) {
            return 'Error while rendering twig template: '.$e->getMessage();
        }
    }

    public function getToolbarScripts(): ?string
    {
        try {
            return $this->twig->loadTemplate('@ContaoCore/FrontendPreview/authenticator.html.twig')->renderBlock(
                'scripts',
                []
            );
        } catch (TwigError $e) {
            return 'Error while rendering twig template: '.$e->getMessage();
        }
    }
}
