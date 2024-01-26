<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Twig\Global;

use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;

class SecurityVariable
{
    public function __construct(private readonly TokenChecker $tokenChecker)
    {
    }

    public function isBackendLoggedIn(): bool
    {
        return $this->tokenChecker->hasBackendUser();
    }

    public function isPreviewMode(): bool
    {
        return $this->tokenChecker->isPreviewMode();
    }
}
