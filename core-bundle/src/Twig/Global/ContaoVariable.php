<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Twig\Global;

use Contao\BackendUser;
use Contao\Config;
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\Framework\ContaoFramework;
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
        private readonly ContaoFramework $framework,
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

    public function getDatim_format(): string
    {
        if ($pageFormat = $this->getPage()?->datimFormat) {
            return $pageFormat;
        }

        $this->framework->initialize();

        return $this->framework->getAdapter(Config::class)->get('datimFormat') ?? 'Y-m-d H:i';
    }

    public function getDate_format(): string
    {
        if ($pageFormat = $this->getPage()?->dateFormat) {
            return $pageFormat;
        }

        $this->framework->initialize();

        return $this->framework->getAdapter(Config::class)->get('dateFormat') ?? 'Y-m-d';
    }

    public function getTime_format(): string
    {
        if ($pageFormat = $this->getPage()?->timeFormat) {
            return $pageFormat;
        }

        $this->framework->initialize();

        return $this->framework->getAdapter(Config::class)->get('timeFormat') ?? 'H:i';
    }

    public function backend_user(): BackendUser|null
    {
        $user = $this->security->getUser();

        return $user instanceof BackendUser ? $user : null;
    }
}
