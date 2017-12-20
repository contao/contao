<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Security\Authentication;

use Contao\FrontendUser;
use Contao\PageModel;
use Contao\StringUtil;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\ParameterBagUtils;

class FrontendAuthenticationSuccessHandler extends AuthenticationSuccessHandler
{
    /**
     * @var UserInterface
     */
    private $user;

    /**
     * {@inheritdoc}
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token): RedirectResponse
    {
        $this->user = $token->getUser();

        return parent::onAuthenticationSuccess($request, $token);
    }

    /**
     * {@inheritdoc}
     */
    protected function determineTargetUrl(Request $request)
    {
        if (!$this->user instanceof FrontendUser) {
            return parent::determineTargetUrl($request);
        }

        if (ParameterBagUtils::getRequestParameterValue($request, '_always_use_target_path')
            && ($targetUrl = ParameterBagUtils::getRequestParameterValue($request, '_target_path'))
        ) {
            return $targetUrl;
        }

        /** @var PageModel $pageModelAdapter */
        $pageModelAdapter = $this->framework->getAdapter(PageModel::class);
        $groups = StringUtil::deserialize($this->user->groups, true);
        $groupPage = $pageModelAdapter->findFirstActiveByMemberGroups($groups);

        if ($groupPage instanceof PageModel) {
            return $groupPage->getAbsoluteUrl();
        }

        return parent::determineTargetUrl($request);
    }
}
