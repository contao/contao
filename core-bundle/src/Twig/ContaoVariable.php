<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Twig;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\RequestStack;

class ContaoVariable
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly RequestStack $requestStack,
        private readonly TokenChecker $tokenChecker,
    ) {
    }

    public function page(): PageModel|null
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request || !$request->attributes->has('pageModel')) {
            return null;
        }

        $pageModel = $request->attributes->get('pageModel');

        if ($pageModel instanceof PageModel) {
            return $pageModel;
        }

        if (
            isset($GLOBALS['objPage'])
            && $GLOBALS['objPage'] instanceof PageModel
            && (int) $GLOBALS['objPage']->id === (int) $pageModel
        ) {
            return $GLOBALS['objPage'];
        }

        $this->framework->initialize();

        return $this->framework->getAdapter(PageModel::class)->findByPk((int) $pageModel);
    }

    public function tokenChecker(): TokenChecker
    {
        return $this->tokenChecker;
    }
}
