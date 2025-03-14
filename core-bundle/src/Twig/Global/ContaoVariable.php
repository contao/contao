<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Twig\Global;

use Contao\BackendUser;
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\PageModel;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class ContaoVariable
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly TokenChecker $tokenChecker,
        private readonly ContaoCsrfTokenManager $tokenManager,
        private readonly Security $security,
    ) {
    }

    public function getPage(): PageModel|null
    {
        $pageModel = $this->requestStack->getCurrentRequest()?->attributes->get('pageModel');

        if ($pageModel instanceof PageModel) {
            return $pageModel;
        }

        return null;
    }

    public function getHas_backend_user(): bool
    {
        return $this->tokenChecker->hasBackendUser();
    }

    public function getIs_preview_mode(): bool
    {
        return $this->tokenChecker->isPreviewMode();
    }

    public function getRequest_token(): string
    {
        return $this->tokenManager->getDefaultTokenValue();
    }

    public function backend_user(): BackendUser|null
    {
        $user = $this->security->getUser();

        return $user instanceof BackendUser ? $user : null;
    }
}
