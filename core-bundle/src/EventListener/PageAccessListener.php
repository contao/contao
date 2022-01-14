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
    private ContaoFramework $framework;
    private Security $security;

    public function __construct(ContaoFramework $framework, Security $security)
    {
        $this->security = $security;
        $this->framework = $framework;
    }

    /**
     * Resolve pageModel in request attributes and check permission to access the page.
     */
    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $pageModel = $this->getPageModel($request);

        if (null === $pageModel) {
            return;
        }

        $pageModel->loadDetails();
        $request->attributes->set('pageModel', $pageModel);

        if (!$pageModel->protected) {
            return;
        }

        if (!$this->security->isGranted('ROLE_MEMBER')) {
            throw new InsufficientAuthenticationException('Not authenticated');
        }

        if (!$this->security->isGranted(ContaoCorePermissions::MEMBER_IN_GROUPS, $pageModel->groups)) {
            throw new AccessDeniedException('Member does not have access to page ID '.$pageModel->id);
        }
    }

    private function getPageModel(Request $request): ?PageModel
    {
        if (!$request->attributes->has('pageModel')) {
            return null;
        }

        $pageModel = $request->attributes->get('pageModel');

        if (
            isset($GLOBALS['objPage'])
            && $GLOBALS['objPage'] instanceof PageModel
            && (
                ($pageModel instanceof PageModel && (int) $pageModel->id === (int) $GLOBALS['objPage']->id)
                || (!$pageModel instanceof PageModel && (int) $GLOBALS['objPage']->id === (int) $pageModel)
            )
        ) {
            return $GLOBALS['objPage'];
        }

        $this->framework->initialize();

        return $this->framework->getAdapter(PageModel::class)->findByPk((int) $pageModel);
    }
}
