<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener;

use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Exception\InsufficientAuthenticationException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Security;

/**
 * @internal
 */
class PageAccessListener
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly Security $security,
    ) {
    }

    /**
     * Resolve pageModel in request attributes and check permission to access the page.
     */
    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!$pageModel = $this->getPageModel($request)) {
            return;
        }

        $pageModel->loadDetails();
        $request->attributes->set('pageModel', $pageModel);

        if (!$pageModel->protected) {
            return;
        }

        // Do not check for logged in member if -1 (guest group) is allowed
        if (
            !$this->security->isGranted('ROLE_MEMBER')
            && !\in_array(-1, array_map('intval', $pageModel->groups), true)
        ) {
            throw new InsufficientAuthenticationException('Not authenticated');
        }

        if (!$this->security->isGranted(ContaoCorePermissions::MEMBER_IN_GROUPS, $pageModel->groups)) {
            throw new AccessDeniedException('Member does not have access to page ID '.$pageModel->id);
        }
    }

    private function getPageModel(Request $request): PageModel|null
    {
        if (!$request->attributes->has('pageModel')) {
            return null;
        }

        $pageModel = $request->attributes->get('pageModel');

        if (
            isset($GLOBALS['objPage'])
            && $GLOBALS['objPage'] instanceof PageModel
            && (
                ($pageModel instanceof PageModel && (int) $pageModel->id === $GLOBALS['objPage']->id)
                || (!$pageModel instanceof PageModel && $GLOBALS['objPage']->id === (int) $pageModel)
            )
        ) {
            return $GLOBALS['objPage'];
        }

        if ($pageModel instanceof PageModel) {
            return $pageModel;
        }

        $this->framework->initialize();

        return $this->framework->getAdapter(PageModel::class)->findByPk((int) $pageModel);
    }
}
